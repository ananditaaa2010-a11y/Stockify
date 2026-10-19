<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingRepository
{
    public function all(): Collection
    {
        return Setting::query()->pluck('value', 'key');
    }

    public function value(string $key): ?string
    {
        return Setting::valueFor($key);
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
