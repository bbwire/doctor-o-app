<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationReceipt extends Model
{
    protected $fillable = [
        'consultation_settlement_id',
        'patient_email',
        'status',
        'file_path',
        'receipt_number',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(ConsultationSettlement::class, 'consultation_settlement_id');
    }
}

