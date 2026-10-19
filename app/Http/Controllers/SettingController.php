<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(SettingService $settings): View
    {
        return view('settings.index', [
            'settings' => $settings->all(),
        ]);
    }

    public function update(Request $request, SettingService $settings, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'app_info' => ['nullable', 'string', 'max:1000'],
            'logo' => [
                'nullable',
                Rule::prohibitedIf($request->boolean('remove_logo')),
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $settings->updateApplication($data, $request->file('logo'), $request->boolean('remove_logo'), $logger);

        return redirect()->route('settings.index')->with('status', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
