@extends('layouts.guest')

@section('title', 'Daftar Customer - Stockify')

@section('content')
<main class="relative isolate flex min-h-screen items-center justify-center overflow-hidden bg-[linear-gradient(135deg,#f2eaff_0%,#fbf9ff_48%,#e6f8ef_100%)] px-4 py-6">
    <div class="pointer-events-none absolute -left-24 top-10 -z-10 h-72 w-72 rounded-full bg-[#dfd2ff]/70 blur-[90px]"></div>
    <div class="pointer-events-none absolute -right-20 bottom-0 -z-10 h-80 w-80 rounded-full bg-[#c9f0dd]/70 blur-[100px]"></div>
    <section class="w-full max-w-xl rounded-[2rem] border border-white/90 bg-white/90 p-6 shadow-[0_30px_90px_-35px_rgba(102,83,161,0.32)] backdrop-blur sm:p-9">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full border border-[#e8e2f4] bg-white/80 px-4 py-2 text-sm font-semibold text-[#7964dc] shadow-sm transition hover:border-[#d8cff1] hover:bg-white hover:text-[#5947b0]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6 6-6-6 6-6"/></svg>
            Kembali ke beranda
        </a>

        <div class="mt-8">
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-[#806bd0]">Akun customer {{ \App\Models\Setting::valueFor('app_name', 'Stockify') }}</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#383451]">Buat akun baru</h1>
            <p class="mt-2 text-sm text-[#817d91]">Daftar untuk mulai belanja, melacak pesanan, dan memberi feedback produk.</p>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="mt-5 rounded-xl border border-[#e8e2f4] bg-[#faf9ff] px-4 py-3 text-xs leading-5 text-[#69647d]">
            <p class="font-semibold text-[#514c69]">Syarat pendaftaran</p>
            <ul class="mt-1 list-inside list-disc">
                <li>Nama wajib diisi.</li>
                <li>Email harus valid dan belum pernah terdaftar.</li>
                <li>Kata sandi minimal 8 karakter.</li>
                <li>Konfirmasi kata sandi harus sama persis.</li>
            </ul>
        </div>

        <form class="mt-6 space-y-4" action="{{ route('register.store') }}" method="POST" data-loading-form>
            @csrf
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-[#55516e]">Nama lengkap</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus class="block w-full rounded-xl border border-[#e5e1ef] bg-[#fdfcff] px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#9683df] focus:ring-2 focus:ring-[#eee9ff]" placeholder="Nama Anda">
                @error('name')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-[#55516e]">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required class="block w-full rounded-xl border border-[#e5e1ef] bg-[#fdfcff] px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#9683df] focus:ring-2 focus:ring-[#eee9ff]" placeholder="nama@email.com">
                @error('email')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-[#55516e]">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required aria-describedby="password-requirement" class="block w-full rounded-xl border border-[#e5e1ef] bg-[#fdfcff] px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#9683df] focus:ring-2 focus:ring-[#eee9ff]" placeholder="Minimal 8 karakter">
                <p id="password-requirement" class="mt-1.5 text-xs text-[#817d91]">Gunakan minimal 8 karakter. Huruf, angka, dan simbol boleh digunakan.</p>
                @error('password')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#55516e]">Konfirmasi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required aria-describedby="password-confirmation-help" class="block w-full rounded-xl border border-[#e5e1ef] bg-[#fdfcff] px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#9683df] focus:ring-2 focus:ring-[#eee9ff]" placeholder="Ulangi kata sandi">
                <p id="password-confirmation-help" class="mt-1.5 text-xs text-[#817d91]">Ketik ulang kata sandi yang sama persis.</p>
            </div>
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#7964dc] px-4 py-3 text-sm font-bold text-white shadow-md shadow-violet-200 transition hover:bg-[#6854c9] focus:outline-none focus:ring-2 focus:ring-[#7964dc] focus:ring-offset-2">
                Daftar sebagai Customer
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-[#77738a]">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-[#7964dc] hover:text-[#5947b0]">Masuk</a></p>
        <p class="mt-4 rounded-2xl border border-[#eeeaf8] bg-[#faf9ff] p-3 text-xs leading-5 text-[#77738a]">Pendaftaran publik hanya membuat akun Customer. Role lain hanya dapat dikelola melalui database oleh pihak yang berwenang.</p>
    </section>
</main>
@endsection
