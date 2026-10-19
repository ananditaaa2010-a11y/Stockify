@extends('layouts.guest')

@section('title', 'Masuk - Stockify')

@section('content')
<main class="relative isolate flex min-h-screen items-center justify-center overflow-hidden bg-[linear-gradient(135deg,#f2eaff_0%,#fbf9ff_48%,#e6f8ef_100%)] px-4 py-6">
    <div class="pointer-events-none absolute -left-24 top-10 -z-10 h-72 w-72 rounded-full bg-[#dfd2ff]/70 blur-[90px]"></div>
    <div class="pointer-events-none absolute -right-20 bottom-0 -z-10 h-80 w-80 rounded-full bg-[#c9f0dd]/70 blur-[100px]"></div>
    <div class="grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/90 bg-white/90 shadow-[0_30px_90px_-35px_rgba(102,83,161,0.32)] backdrop-blur lg:grid-cols-[1fr_0.9fr]">
        <section class="hidden flex-col justify-between bg-gradient-to-br from-[#eee7ff] via-[#f7f3ff] to-[#e5f7ee] p-6 text-[#48415f] lg:flex">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg font-semibold tracking-tight">
                @if ($logo = \App\Models\Setting::valueFor('logo'))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/80 text-[#7964dc] shadow-sm"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10"/></svg></span>
                @endif
                {{ \App\Models\Setting::valueFor('app_name', 'Stockify') }}
            </a>
            <div class="py-16">
                <p class="text-sm font-semibold text-[#806bd0]">Senang bertemu lagi</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight tracking-tight">Yuk, lanjut rapikan persediaan.</h1>
                <p class="mt-5 max-w-sm leading-7 text-[#77718d]">Masuk ke ruang kerjamu untuk melihat kabar stok dan aktivitas tim hari ini.</p>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-white/80 bg-white/60 p-4 text-xs leading-5 text-[#77718d]"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/80 text-[#7964dc]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-4m4 4V8m4 8v-6m4 6V4"/></svg></span><span>Semua tetap tersambung, setiap orang fokus pada tugasnya.</span></div>
        </section>
        <section class="p-5 sm:p-6 lg:p-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full border border-[#e8e2f4] bg-white/80 px-4 py-2 text-sm font-semibold text-[#7964dc] shadow-sm transition hover:border-[#d8cff1] hover:bg-white hover:text-[#5947b0]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6 6-6-6 6-6"/></svg>
                Kembali ke beranda
            </a>
            <div class="mt-3">
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-[#806bd0]">Ruang kerja {{ \App\Models\Setting::valueFor('app_name', 'Stockify') }}</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-[#383451]">Selamat datang!</h2>
                <p class="mt-2 text-sm text-[#817d91]">Masukkan email dan kata sandi untuk melanjutkan.</p>
            </div>
            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif
            <form class="mt-5 space-y-3" action="{{ route('login.store') }}" method="POST" data-loading-form>
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-[#55516e]">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="block w-full rounded-xl border border-[#e5e1ef] bg-[#fdfcff] px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#9683df] focus:ring-2 focus:ring-[#eee9ff]" placeholder="nama@stockify.test">
                    @error('email')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-[#55516e]">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full rounded-xl border border-[#e5e1ef] bg-[#fdfcff] px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#9683df] focus:ring-2 focus:ring-[#eee9ff]" placeholder="Masukkan kata sandi">
                    @error('password')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2.5 text-sm text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-[#7964dc] focus:ring-[#7964dc]">
                    Ingat saya di perangkat ini
                </label>
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#7964dc] px-4 py-3 text-sm font-bold text-white shadow-md shadow-violet-200 transition hover:bg-[#6854c9] focus:outline-none focus:ring-2 focus:ring-[#7964dc] focus:ring-offset-2">
                    Masuk
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </button>
            </form>
            <div class="mt-5 rounded-2xl border border-[#eeeaf8] bg-[#faf9ff] p-3 text-xs leading-5 text-[#77738a]">
                <p class="font-bold text-[#514c69]">Setiap orang punya ruang kerja sesuai perannya.</p>
                <p class="mt-1">Admin, Manajer Gudang, Staff Gudang, dan Customer melihat menu sesuai hak aksesnya.</p>
            </div>
            <p class="mt-5 text-center text-sm text-[#77738a]">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-[#7964dc] hover:text-[#5947b0]">Daftar sebagai Customer</a></p>
        </section>
    </div>
</main>
@endsection
