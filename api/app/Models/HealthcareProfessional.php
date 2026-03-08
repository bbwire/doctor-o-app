<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthcareProfessional extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'institution_id',
        'speciality',
        'license_number',
        'registration_date',
        'regulatory_council',
        'consultation_charge',
        'bio',
        'availability_start_time',
        'availability_end_time',
        'qualifications',
        'is_active',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'availability_start_time' => 'datetime:H:i',
            'availability_end_time' => 'datetime:H:i',
            'qualifications' => 'array',
            'consultation_charge' => 'decimal:2',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    /**
     * Get the user that owns this healthcare professional profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the institution this healthcare professional belongs to
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicDocuments()
    {
        return $this->hasMany(AcademicDocument::class);
    }
}
