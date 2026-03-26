<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'services',
        'institution_number',
        'practicing_certificate_path',
        'address',
        'location',
        'phone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the healthcare professionals for this institution
     */
    public function healthcareProfessionals(): HasMany
    {
        return $this->hasMany(HealthcareProfessional::class);
    }

    /**
     * Get the payments made by this institution to the platform
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InstitutionPayment::class);
    }
}
