<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f6ff">
    @php($applicationName = \App\Models\Setting::valueFor('app_name', 'Stockify'))
    <title>@yield('title', $applicationName . ' - Manajemen Stok')</title>
    @vite(['resources/css/app.css', 'resources/js/stockify.js'])
</head>
<body class="min-h-screen bg-[#faf9ff] text-slate-800 antialiased">
    @if (request()->routeIs('home', 'login'))
        <div id="page-loader" class="fixed inset-0 z-[100] flex items-center justify-center bg-[#faf9ff]/95 backdrop-blur-sm transition-opacity duration-300" aria-live="polite" aria-label="Memuat halaman">
            <div class="flex flex-col items-center gap-4">
                <span class="loader-mark" aria-hidden="true">
                    <svg viewBox="0 0 48 48" fill="none"><path d="m5 13 19-9 19 9-19 10-19-10Z" fill="currentColor" opacity=".28"/><path d="m5 13 19 10v21L5 34V13Z" fill="currentColor"/><path d="m24 23 19-10v21L24 44V23Z" fill="currentColor" opacity=".65"/><path d="M15 18v12m8-7v12m9-14v12" stroke="white" stroke-width="2" stroke-linecap="round" opacity=".8"/></svg>
                </span>
                <div class="text-center">
                    <p class="text-sm font-bold tracking-wide text-[#48435f]">{{ $applicationName }}</p>
                    <p class="mt-1 text-xs text-[#89849b]">Menyiapkan ruang kerja persediaan...</p>
                </div>
                <span class="loader-progress" aria-hidden="true"><span></span></span>
            </div>
        </div>
    @endif
    @yield('content')
</body>
</html>
