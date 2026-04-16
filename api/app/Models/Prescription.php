<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'doctor_id',
        'patient_id',
        'prescription_number',
        'medications',
        'instructions',
        'issued_at',
        'status',
        'patient_received_at',
    ];

    protected function casts(): array
    {
        return [
            'medications' => 'array',
            'issued_at' => 'datetime',
            'patient_received_at' => 'datetime',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
