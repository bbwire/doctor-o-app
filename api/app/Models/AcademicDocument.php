<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'healthcare_professional_id',
        'type',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
    ];

    public function healthcareProfessional(): BelongsTo
    {
        return $this->belongsTo(HealthcareProfessional::class);
    }
}

