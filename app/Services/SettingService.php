<?php

namespace App\Services;

use App\Repositories\SettingRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SettingService
{
    public function __construct(private SettingRepository $settings)
    {
    }

    public function all(): Collection
    {
        return $this->settings->all();
    }

    public function updateApplication(array $data, ?UploadedFile $logo, bool $removeLogo, ActivityLogger $logger): void
    {
        $oldLogo = $this->settings->value('logo');
        $newLogo = $logo?->store('settings', 'public');
        if ($logo !== null && $newLogo === false) {
            throw new \RuntimeException('Logo aplikasi gagal disimpan.');
        }

        try {
            DB::transaction(function () use ($data, $removeLogo, $newLogo, $logger) {
                $this->settings->set('app_name', $data['app_name']);
                $this->settings->set('app_info', $data['app_info'] ?? null);

                if ($newLogo !== null) {
                    $this->settings->set('logo', $newLogo);
                } elseif ($removeLogo) {
                    $this->settings->set('logo', null);
                }

                $logger->log('settings.updated', 'Memperbarui identitas dan informasi aplikasi.');
            });
        } catch (Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete($newLogo);
            }

            throw $exception;
        }

        Cache::forget('stockify.settings');

        if (($newLogo !== null || $removeLogo) && $oldLogo !== null) {
            Storage::disk('public')->delete($oldLogo);
        }
    }
}
