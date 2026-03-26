<?php

namespace App\Mail;

use App\Models\ConsultationReceipt;
use App\Models\ConsultationSettlement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ConsultationReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ConsultationReceipt $receipt,
        public ConsultationSettlement $settlement,
        public ?string $filePath
    ) {}

    public function build(): static
    {
        $settlementId = $this->settlement->id;

        return $this->subject('Consultation receipt #' . $settlementId)
            ->view('emails.consultation-receipt')
            ->with([
                'receipt' => $this->receipt,
                'settlement' => $this->settlement,
            ])
            ->when(
                ! empty($this->filePath),
                function ($mail) {
                    $absolute = Storage::disk('local')->path($this->filePath);
                    return $mail->attach($absolute, [
                        'as' => basename($this->filePath),
                        'mime' => 'application/pdf',
                    ]);
                }
            );
    }
}

