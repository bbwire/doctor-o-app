<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'date_of_birth',
        'profile_photo_path',
        'preferred_language',
        'wallet_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'wallet_balance' => 'decimal:2',
        ];
    }

    /**
     * Check if user is a patient
     */
    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    /**
     * Check if user is a doctor
     */
    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the healthcare professional profile
     */
    public function healthcareProfessional(): HasOne
    {
        return $this->hasOne(HealthcareProfessional::class);
    }

    public function patientConsultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'patient_id');
    }

    public function doctorConsultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'doctor_id');
    }

    public function patientPrescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    public function doctorPrescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    public function dependants(): HasMany
    {
        return $this->hasMany(Dependant::class, 'patient_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
