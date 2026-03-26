<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\PatientNumberGenerator;
use Illuminate\Database\Seeder;

/**
 * Assigns patient numbers to existing users with role `patient` and a null `patient_number`.
 *
 * Numbers use the same generator as new registrations (DRO-YY-NNNNN-C), with the sequence
 * year derived from each user’s `created_at`.
 *
 * Run: php artisan db:seed --class=BackfillPatientNumbersSeeder
 */
class BackfillPatientNumbersSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(PatientNumberGenerator::class);

        $query = User::query()
            ->where('role', 'patient')
            ->whereNull('patient_number')
            ->orderBy('created_at')
            ->orderBy('id');

        if (! $query->exists()) {
            $this->command?->info('No patients without a patient number.');

            return;
        }

        $done = 0;

        $query->chunk(100, function ($users) use ($generator, &$done) {
            foreach ($users as $user) {
                $user->patient_number = $generator->generate($user->created_at);
                $user->saveQuietly();
                $done++;
            }
        });

        $this->command?->info("Assigned patient numbers to {$done} patient(s).");
    }
}
