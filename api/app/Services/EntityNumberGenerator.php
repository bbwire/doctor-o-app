<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Generates human-readable entity numbers with:
 * - Prefix: entity type (e.g. DR, CL, CN)
 * - Year: 2-digit registration year (YY)
 * - Sequence: 5-digit per-year counter (NNNNN)
 * - Check digit: Luhn check digit computed on (YY + NNNNN)
 *
 * Format: {PREFIX}-{YY}-{NNNNN}-{C}
 */
class EntityNumberGenerator
{
    private const MAX_SEQUENCE = 99_999;

    public function generate(string $prefix, ?DateTimeInterface $registeredAt = null): string
    {
        $registeredAt = $registeredAt ? Carbon::parse($registeredAt) : Carbon::now();
        $yy = $registeredAt->format('y');

        $prefix = trim($prefix);
        if ($prefix === '') {
            throw new RuntimeException('Entity number prefix cannot be empty.');
        }

        return DB::transaction(function () use ($prefix, $yy) {
            $row = DB::table('entity_number_sequences')
                ->where('prefix', $prefix)
                ->where('year_short', $yy)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('entity_number_sequences')->insert([
                    'prefix' => $prefix,
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
                throw new RuntimeException("Entity number sequence exhausted for prefix {$prefix} and year {$yy}.");
            }

            $next = $last + 1;

            DB::table('entity_number_sequences')
                ->where('prefix', $prefix)
                ->where('year_short', $yy)
                ->update([
                    'last_sequence' => $next,
                    'updated_at' => now(),
                ]);

            $nnnnn = str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $luhnPayload = $yy . $nnnnn;
            $check = $this->luhnCheckDigit($luhnPayload);

            return sprintf('%s-%s-%s-%d', $prefix, $yy, $nnnnn, $check);
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
            if ($this->luhnIsValid($digitPayload . (string) $c)) {
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

