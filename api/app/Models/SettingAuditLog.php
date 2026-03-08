<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingAuditLog extends Model
{
    protected $table = 'setting_audit_logs';

    protected $fillable = [
        'user_id',
        'key',
        'old_value',
        'new_value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
