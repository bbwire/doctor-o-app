<?php

namespace Tests\Unit;

use App\Services\PatientNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PatientNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function luhn_validates_known_algorithm_examples(): void
    {
        $gen = new PatientNumberGenerator;

        $this->assertTrue($gen->luhnIsValid('79927398713'));
        $this->assertFalse($gen->luhnIsValid('79927398714'));
    }

    #[Test]
    public function luhn_check_digit_matches_full_number_validity(): void
    {
        $gen = new PatientNumberGenerator;
        $payload = '2600001';
        $c = $gen->luhnCheckDigit($payload);
        $this->assertTrue($gen->luhnIsValid($payload.(string) $c));
    }

    #[Test]
    public function generated_numbers_match_format_and_luhn(): void
    {
        $gen = new PatientNumberGenerator;
        $n = $gen->generate();

        $this->assertMatchesRegularExpression('/^DRO-\d{2}-\d{5}-\d$/', $n);
        $this->assertSame(1, preg_match('/^DRO-(\d{2})-(\d{5})-(\d)$/', $n, $m));
        $payload = $m[1].$m[2];
        $this->assertTrue($gen->luhnIsValid($payload.$m[3]));
    }

    #[Test]
    public function sequence_increments_per_registration_year(): void
    {
        $gen = new PatientNumberGenerator;
        $a = $gen->generate(now()->setDate(2026, 3, 1));
        $b = $gen->generate(now()->setDate(2026, 6, 1));

        $this->assertMatchesRegularExpression('/^DRO-26-00001-\d$/', $a);
        $this->assertMatchesRegularExpression('/^DRO-26-00002-\d$/', $b);
    }
}
