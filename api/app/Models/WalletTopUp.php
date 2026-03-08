<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTopUp extends Model
{
    use HasFactory;

    protected $table = 'wallet_topups';

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'provider',
        'phone_number',
        'status',
        'provider_reference',
        'provider_metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

