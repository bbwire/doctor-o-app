<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationSettlement extends Model
{
    protected $fillable = [
        'consultation_id',
        'patient_id',
        'doctor_id',
        'invoice_number',
        'amount_paid',
        'platform_fee_percentage',
        'platform_fee',
        'doctor_earning',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'platform_fee_percentage' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'doctor_earning' => 'decimal:2',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
