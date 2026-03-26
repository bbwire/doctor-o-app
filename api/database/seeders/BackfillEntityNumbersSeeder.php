<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\ConsultationReceipt;
use App\Models\ConsultationSettlement;
use App\Models\HealthcareProfessional;
use App\Models\Institution;
use App\Models\Prescription;
use App\Services\EntityNumberGenerator;
use App\Support\IdSystem;
use Illuminate\Database\Seeder;

class BackfillEntityNumbersSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(EntityNumberGenerator::class);

        $done = [
            'professionals' => 0,
            'institutions' => 0,
            'consultations' => 0,
            'referrals' => 0,
            'prescriptions' => 0,
            'invoices' => 0,
            'receipts' => 0,
        ];

        HealthcareProfessional::query()
            ->whereNull('professional_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($generator, &$done): void {
                foreach ($rows as $hp) {
                    $prefix = IdSystem::professionalPrefix($hp->speciality);
                    $hp->professional_number = $generator->generate($prefix, $hp->created_at);
                    $hp->saveQuietly();
                    $done['professionals']++;
                }
            });

        Institution::query()
            ->whereNull('institution_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($generator, &$done): void {
                foreach ($rows as $inst) {
                    $prefix = IdSystem::institutionPrefix($inst->type);
                    if ($prefix === null) {
                        continue;
                    }
                    $inst->institution_number = $generator->generate($prefix, $inst->created_at);
                    $inst->saveQuietly();
                    $done['institutions']++;
                }
            });

        Consultation::query()
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($generator, &$done): void {
                foreach ($rows as $consultation) {
                    if (! $consultation->consultation_number) {
                        $consultation->consultation_number = $generator->generate('CN', $consultation->created_at);
                        $consultation->saveQuietly();
                        $done['consultations']++;
                    }

                    if (! $consultation->referral_number) {
                        $notes = $consultation->clinical_notes ?? [];
                        $referrals = $notes['management_plan']['referrals'] ?? null;
                        if (is_string($referrals) && trim($referrals) !== '') {
                            $consultation->referral_number = $generator->generate('RF', $consultation->created_at);
                            $consultation->saveQuietly();
                            $done['referrals']++;
                        }
                    }
                }
            });

        Prescription::query()
            ->whereNull('prescription_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($generator, &$done): void {
                foreach ($rows as $p) {
                    $p->prescription_number = $generator->generate('RX', $p->created_at);
                    $p->saveQuietly();
                    $done['prescriptions']++;
                }
            });

        ConsultationSettlement::query()
            ->whereNull('invoice_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($generator, &$done): void {
                foreach ($rows as $s) {
                    $s->invoice_number = $generator->generate('IN', $s->created_at);
                    $s->saveQuietly();
                    $done['invoices']++;
                }
            });

        ConsultationReceipt::query()
            ->whereNull('receipt_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($generator, &$done): void {
                foreach ($rows as $r) {
                    $r->receipt_number = $generator->generate('RT', $r->created_at);
                    $r->saveQuietly();
                    $done['receipts']++;
                }
            });

        $this->command?->info('Backfilled entity numbers: ' . json_encode($done));
    }
}

