<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'string',
    ];

    public static function getValue(string $key): ?string
    {
        $row = self::query()->where('key', $key)->first();

        return $row?->value;
    }

    public static function setValue(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value]
        );
    }
}
