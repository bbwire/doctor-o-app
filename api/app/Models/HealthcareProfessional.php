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
        'bio',
        'qualifications',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'qualifications' => 'array',
            'is_active' => 'boolean',
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
