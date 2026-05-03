<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'consultation_number',
        'referral_number',
        'scheduled_at',
        'consultation_type',
        'status',
        'reason',
        'notes',
        'metadata',
        'clinical_notes',
        'patient_last_read_message_id',
        'doctor_last_read_message_id',
        'patient_typing_at',
        'doctor_typing_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'metadata' => 'array',
            'clinical_notes' => 'array',
            'patient_typing_at' => 'datetime',
            'doctor_typing_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConsultationMessage::class)->orderBy('created_at');
    }

    public function settlement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ConsultationSettlement::class);
    }
}
