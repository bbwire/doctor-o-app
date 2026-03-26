<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Assigns human-readable patient numbers: DRO-YY-NNNNN-C
 * (Dr. O, 2-digit registration year, 5-digit sequence, Luhn check digit on YY+NNNNN).
 */
class PatientNumberGenerator
{
    private const MAX_SEQUENCE = 99_999;

    /**
     * @throws RuntimeException When the yearly sequence is exhausted.
     */
    public function generate(?DateTimeInterface $registeredAt = null): string
    {
        $registeredAt = $registeredAt ? Carbon::parse($registeredAt) : Carbon::now();
        $yy = $registeredAt->format('y');

        return DB::transaction(function () use ($yy) {
            $row = DB::table('patient_number_sequences')
                ->where('year_short', $yy)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('patient_number_sequences')->insert([
                    'year_short' => $yy,
                    'last_sequence' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $last = 0;
            } else {
                $last = (int) $row->last_sequence;
            }

            if ($last >= self::MAX_SEQUENCE) {
                throw new RuntimeException('Patient number sequence exhausted for registration year '.$yy.'.');
            }

            $next = $last + 1;

            DB::table('patient_number_sequences')
                ->where('year_short', $yy)
                ->update([
                    'last_sequence' => $next,
                    'updated_at' => now(),
                ]);

            $nnnnn = str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $luhnPayload = $yy.$nnnnn;
            $check = $this->luhnCheckDigit($luhnPayload);

            return sprintf('DRO-%s-%s-%d', $yy, $nnnnn, $check);
        });
    }

    /**
     * Check digit for an all-digit payload (YY + NNNNN = 7 digits) so the 8-digit
     * string payload+C passes the Luhn algorithm.
     */
    public function luhnCheckDigit(string $digitPayload): int
    {
        if (! preg_match('/^\d+$/', $digitPayload)) {
            throw new RuntimeException('Luhn payload must be numeric digits only.');
        }

        for ($c = 0; $c <= 9; $c++) {
            if ($this->luhnIsValid($digitPayload.(string) $c)) {
                return $c;
            }
        }

        throw new RuntimeException('Could not derive Luhn check digit.');
    }

    public function luhnIsValid(string $number): bool
    {
        if (! preg_match('/^\d+$/', $number)) {
            return false;
        }

        $sum = 0;
        $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int) $number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = ! $alt;
        }

        return $sum % 10 === 0;
    }
}
