<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        $settings = cache()->remember('stockify.settings', 3600, fn () => static::query()->pluck('value', 'key')->all());

        return $settings[$key] ?? $default;
    }
}
