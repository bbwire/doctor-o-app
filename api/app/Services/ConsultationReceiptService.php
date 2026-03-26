<?php

namespace App\Services;

use App\Mail\ConsultationReceiptMail;
use App\Models\ConsultationReceipt;
use App\Models\ConsultationSettlement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ConsultationReceiptService
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    /**
     * Send receipt email for the given settlement.
     *
     * Idempotent: if a receipt for this settlement is already marked as sent,
     * it will not send again.
     */
    public function sendForSettlement(ConsultationSettlement $settlement): ConsultationReceipt
    {
        try {
            $patientEmail = (string) ($settlement->patient?->email ?? '');

            $receipt = ConsultationReceipt::query()
                ->firstOrCreate(
                    ['consultation_settlement_id' => $settlement->id],
                    ['patient_email' => $patientEmail, 'status' => 'pending']
                );

            if (! $receipt->receipt_number) {
                $receipt->receipt_number = app(EntityNumberGenerator::class)->generate('RT', $receipt->created_at);
                $receipt->saveQuietly();
            }

            if ($receipt->status === 'sent') {
                return $receipt;
            }

            // Update email in case it changed.
            if ($patientEmail !== '' && $receipt->patient_email !== $patientEmail) {
                $receipt->update(['patient_email' => $patientEmail]);
            }

            if (! $receipt->patient_email) {
                $receipt->update([
                    'status' => 'failed',
                    'error_message' => 'Patient email is missing.',
                ]);

                return $receipt;
            }

            // Avoid expensive PDF generation in automated tests.
            $filePath = null;
            if (! app()->environment('testing')) {
                $pdf = Pdf::loadView('consultation-receipt', [
                    'settlement' => $settlement,
                    'consultation' => $settlement->consultation,
                    'patient' => $settlement->patient,
                    'doctor' => $settlement->doctor,
                    'platformFeePercent' => $settlement->platform_fee_percentage,
                    'amountPaid' => $settlement->amount_paid,
                    'doctorEarning' => $settlement->doctor_earning,
                ])->setPaper('a4');

                $pdfBytes = (string) $pdf->output();
                $filePath = 'receipts/consultations/' . $settlement->id . '/receipt.pdf';

                Storage::disk('local')->put($filePath, $pdfBytes);
            }

            $receipt->update([
                'file_path' => $filePath,
                'status' => 'pending',
                'error_message' => null,
            ]);

            // Send email even if PDF generation was skipped in tests.
            Mail::to($receipt->patient_email)->send(
                new ConsultationReceiptMail(
                    receipt: $receipt,
                    settlement: $settlement,
                    filePath: $filePath
                )
            );

            $receipt->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return $receipt;
        } catch (\Throwable $e) {
            // Best-effort: record failure, but never break the billing request.
            $settlementId = $settlement->id;

            ConsultationReceipt::query()
                ->updateOrCreate(
                    ['consultation_settlement_id' => $settlementId],
                    [
                        'patient_email' => (string) ($settlement->patient?->email ?? ''),
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]
                );

            report($e);

            return ConsultationReceipt::query()
                ->where('consultation_settlement_id', $settlementId)
                ->firstOrFail();
        }
    }
}

