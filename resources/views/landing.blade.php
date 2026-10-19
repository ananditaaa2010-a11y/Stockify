@extends('layouts.guest')

@section('title', \App\Models\Setting::valueFor('app_name', 'Stockify') . ' — Kelola stok dengan hati tenang')

@section('content')
@php
    $applicationName = \App\Models\Setting::valueFor('app_name', 'Stockify');
    $applicationLogo = \App\Models\Setting::valueFor('logo');
@endphp
<div class="relative isolate min-h-screen overflow-hidden bg-[#faf9ff] text-[#302d4a]">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[760px] overflow-hidden bg-[linear-gradient(115deg,#f1eaff_0%,#faf8ff_43%,#e7fbf3_100%)]">
        <div class="absolute -left-32 top-24 h-96 w-96 rounded-full bg-[#dfd1ff]/60 blur-[100px]"></div>
        <div class="absolute -right-24 top-8 h-[440px] w-[440px] rounded-full bg-[#bff0dd]/55 blur-[110px]"></div>
        <div class="absolute left-1/2 top-80 h-48 w-72 -translate-x-1/2 rounded-full bg-[#ffe9d7]/55 blur-[90px]"></div>
    </div>

    <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-lg font-bold tracking-tight text-[#34304f]">
            @if ($applicationLogo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($applicationLogo) }}" alt="" class="h-10 w-10 rounded-2xl object-cover shadow-sm">
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-[#a991f7] to-[#7865da] text-white shadow-md shadow-violet-200/70">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10"/></svg>
                </span>
            @endif
            {{ $applicationName }}
            <span class="hidden rounded-full bg-white/75 px-2.5 py-1 text-[10px] font-semibold tracking-wide text-[#8177a6] sm:inline-flex">INVENTARIS CERIA</span>
        </a>
        <nav class="hidden items-center gap-7 text-sm font-medium text-[#68647f] md:flex" aria-label="Navigasi landing page">
            <a href="#fitur" class="transition hover:text-[#735fd1]">Fitur</a>
            <a href="#peran" class="transition hover:text-[#735fd1]">Ruang kerja</a>
            <a href="#cara-kerja" class="transition hover:text-[#735fd1]">Cara kerja</a>
        </nav>
        <a href="{{ route('register') }}" data-loading-link class="inline-flex items-center gap-2 rounded-full bg-[#7964dc] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-200/70 transition hover:-translate-y-0.5 hover:bg-[#6854c9] focus:outline-none focus:ring-2 focus:ring-[#7964dc] focus:ring-offset-2">
            Daftar Customer
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </a>
    </header>

    <main>
        <section class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-20 pt-14 sm:px-8 sm:pt-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-10 lg:pb-28 lg:pt-24">
            <div class="relative z-10 max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/90 bg-white/75 px-4 py-2 text-xs font-semibold text-[#716594] shadow-sm shadow-violet-100/70 backdrop-blur">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#e5faee] text-[#519874]">
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.5 7.563a1 1 0 0 1-1.42.003l-3.5-3.5a1 1 0 1 1 1.414-1.414l2.79 2.79 6.794-6.85a1 1 0 0 1 1.416-.006Z" clip-rule="evenodd"/></svg>
                    </span>
                    Biar urusan stok terasa lebih ringan
                </div>
                <h1 class="mt-7 max-w-xl text-5xl font-extrabold leading-[1.06] tracking-[-0.055em] text-[#30294e] sm:text-6xl lg:text-[4.5rem]">
                    Stok tertata,<br>
                    <span class="bg-gradient-to-r from-[#8e72df] via-[#a478d1] to-[#66b89a] bg-clip-text text-transparent">hati ikut lega.</span>
                </h1>
                <p class="mt-6 max-w-lg text-base leading-7 text-[#716d86] sm:text-lg sm:leading-8">
                    {{ \App\Models\Setting::valueFor('app_info', 'Kelola produk, pantau stok, dan bantu tim gudang bekerja lebih kompak—semuanya dalam satu tempat yang nyaman dipakai.') }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" data-loading-link class="inline-flex items-center justify-center gap-2 rounded-full bg-[#7964dc] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-violet-200/80 transition hover:-translate-y-0.5 hover:bg-[#6854c9] focus:outline-none focus:ring-2 focus:ring-[#7964dc] focus:ring-offset-2">
                        Daftar & mulai belanja
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    <a href="#fitur" data-smooth-scroll class="inline-flex items-center justify-center rounded-full border border-white bg-white/70 px-6 py-3.5 text-sm font-semibold text-[#625d78] shadow-sm transition hover:bg-white">Lihat fitur-fitur</a>
                </div>
                <div class="mt-8 flex flex-wrap gap-x-5 gap-y-3 text-xs font-medium text-[#77738a] sm:text-sm">
                    <span class="inline-flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#e3f7ec] text-[#55a27c]">✓</span> Stok minimum terpantau</span>
                    <span class="inline-flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#eee8ff] text-[#8870d6]">✓</span> Akses sesuai peran</span>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-[650px] lg:justify-self-end">
                <div class="absolute -inset-8 -z-10 rounded-[3rem] bg-gradient-to-br from-[#ddcdff]/80 via-white/40 to-[#bcefdc]/80 blur-2xl"></div>
                <div class="absolute -right-2 -top-5 z-20 hidden rotate-2 rounded-2xl border border-white/80 bg-white/90 px-4 py-3 shadow-lg shadow-violet-100/70 sm:block">
                    <div class="flex items-center gap-2.5"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#eee9ff] text-[#7865d3]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-4m4 4V8m4 8v-6m4 6V4"/></svg></span><div><p class="text-[11px] text-[#88839a]">Semua terpantau</p><p class="text-xs font-bold text-[#48435f]">Kerja jadi tenang</p></div></div>
                </div>
                <div class="overflow-hidden rounded-[1.75rem] border border-white/90 bg-white/90 shadow-[0_30px_90px_-35px_rgba(102,83,161,0.35)] backdrop-blur">
                    <div class="flex items-center justify-between border-b border-[#f0eef7] bg-white/80 px-4 py-3.5 sm:px-6">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-[#ab96ef] to-[#7563d4] text-white"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7"/></svg></span>
                            <span class="text-sm font-bold text-[#48435f]">{{ $applicationName }}</span>
                        </div>
                        <div class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#a9dfbd]"></span><span class="text-[10px] font-medium text-[#88839a] sm:text-xs">Ruang kerja Admin</span></div>
                    </div>
                    <div class="bg-[#fbfaff] p-4 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-medium text-[#908ba2]">Halo, tim gudang</p><h2 class="mt-1 text-lg font-bold tracking-tight text-[#383451] sm:text-xl">Ringkasan persediaan</h2></div>
                            <span class="rounded-xl bg-white px-3 py-2 text-[10px] font-semibold text-[#827ca0] shadow-sm">Hari ini ▾</span>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <article class="rounded-2xl border border-[#f0edf8] bg-white p-3 shadow-sm shadow-violet-100/30"><div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#f0ebff] text-[#836bd5]"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7"/></svg></div><p class="mt-3 text-[10px] text-[#918ca1]">Produk</p><p class="mt-0.5 text-lg font-extrabold text-[#45405e]">Katalog</p></article>
                            <article class="rounded-2xl border border-[#f0edf8] bg-white p-3 shadow-sm shadow-violet-100/30"><div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#e8f8ef] text-[#5ca981]"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg></div><p class="mt-3 text-[10px] text-[#918ca1]">Barang masuk</p><p class="mt-0.5 text-lg font-extrabold text-[#45405e]">Tercatat</p></article>
                            <article class="rounded-2xl border border-[#f0edf8] bg-white p-3 shadow-sm shadow-violet-100/30"><div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#fff3e4] text-[#d39b4d]"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg></div><p class="mt-3 text-[10px] text-[#918ca1]">Barang keluar</p><p class="mt-0.5 text-lg font-extrabold text-[#45405e]">Terkelola</p></article>
                            <article class="rounded-2xl border border-[#f0edf8] bg-white p-3 shadow-sm shadow-violet-100/30"><div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#e7f5fb] text-[#5a9cbc]"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 0 0 3.2 21h17.6a1.6 1.6 0 0 0 1.4-2.4L13.7 3.9a1.9 1.9 0 0 0-3.4 0Z"/></svg></div><p class="mt-3 text-[10px] text-[#918ca1]">Stok minimum</p><p class="mt-0.5 text-lg font-extrabold text-[#45405e]">Terjaga</p></article>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-[1.4fr_0.9fr]">
                            <article class="rounded-2xl border border-[#f0edf8] bg-white p-4 shadow-sm shadow-violet-100/30">
                                <div class="flex items-center justify-between"><div><h3 class="text-xs font-bold text-[#55516e]">Aktivitas stok</h3><p class="mt-1 text-[10px] text-[#9691a5]">Barang masuk dan keluar</p></div><span class="rounded-full bg-[#f1edff] px-2 py-1 text-[9px] font-semibold text-[#8570cf]">Ringkasan</span></div>
                                <div class="mt-5 flex h-24 items-end gap-2" aria-label="Ilustrasi grafik aktivitas stok">
                                    <span class="h-[35%] flex-1 rounded-t-md bg-[#dcd3fb]"></span><span class="h-[58%] flex-1 rounded-t-md bg-[#c8baf5]"></span><span class="h-[45%] flex-1 rounded-t-md bg-[#e5defc]"></span><span class="h-[78%] flex-1 rounded-t-md bg-[#9982e4]"></span><span class="h-[54%] flex-1 rounded-t-md bg-[#d4c9fa]"></span><span class="h-[68%] flex-1 rounded-t-md bg-[#b8a5ef]"></span><span class="h-[40%] flex-1 rounded-t-md bg-[#e5defc]"></span><span class="h-[88%] flex-1 rounded-t-md bg-[#7967d7]"></span><span class="h-[60%] flex-1 rounded-t-md bg-[#c8baf5]"></span><span class="h-[74%] flex-1 rounded-t-md bg-[#a794eb]"></span>
                                </div>
                                <div class="mt-2 flex justify-between text-[9px] text-[#a19caf]"><span>Sen</span><span>Rab</span><span>Jum</span><span>Min</span></div>
                            </article>
                            <article class="rounded-2xl border border-[#f0edf8] bg-white p-4 shadow-sm shadow-violet-100/30">
                                <div class="flex items-center justify-between"><h3 class="text-xs font-bold text-[#55516e]">Ruang kerja tim</h3><svg class="h-4 w-4 text-[#9586cf]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-4m4 4V8m4 8v-6m4 6V4"/></svg></div>
                                <div class="mt-4 space-y-3">
                                    <div class="flex items-center gap-2.5 rounded-xl bg-[#faf8ff] p-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#eee8ff] text-[10px] font-bold text-[#806bd0]">A</span><div><p class="text-[10px] font-bold text-[#5a5570]">Admin</p><p class="text-[9px] text-[#a09aae]">Kelola aplikasi</p></div><span class="ml-auto h-1.5 w-1.5 rounded-full bg-[#9dd8b2]"></span></div>
                                    <div class="flex items-center gap-2.5 rounded-xl bg-[#faf8ff] p-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#e6f7ee] text-[10px] font-bold text-[#5caa82]">M</span><div><p class="text-[10px] font-bold text-[#5a5570]">Manajer</p><p class="text-[9px] text-[#a09aae]">Atur persediaan</p></div><span class="ml-auto h-1.5 w-1.5 rounded-full bg-[#9dd8b2]"></span></div>
                                    <div class="flex items-center gap-2.5 rounded-xl bg-[#faf8ff] p-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#fff1df] text-[10px] font-bold text-[#c4934d]">S</span><div><p class="text-[10px] font-bold text-[#5a5570]">Staff gudang</p><p class="text-[9px] text-[#a09aae]">Selesaikan tugas</p></div><span class="ml-auto h-1.5 w-1.5 rounded-full bg-[#9dd8b2]"></span></div>
                                </div>
                            </article>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-[#f0eef7] bg-white px-4 py-3 text-[10px] text-[#89849b] sm:px-6"><span>Preview ruang kerja Stockify</span><span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-[#9dd8b2]"></span>Desain nyaman, kerja tetap fokus</span></div>
                </div>
                <div class="absolute -bottom-6 -left-4 z-20 hidden -rotate-3 rounded-2xl border border-white/80 bg-white/95 px-4 py-3 shadow-lg shadow-emerald-100/70 sm:block">
                    <div class="flex items-center gap-2.5"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#e4f8ec] text-[#58a87e]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10"/></svg></span><div><p class="text-[11px] text-[#88839a]">Stok makin rapi</p><p class="text-xs font-bold text-[#48435f]">Tim makin kompak</p></div></div>
                </div>
            </div>
        </section>

        <section id="fitur" class="scroll-mt-6 bg-white/70 px-5 py-20 sm:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="inline-flex rounded-full bg-[#f0ebff] px-4 py-2 text-xs font-bold text-[#7e69cf]">SEMUA YANG DIBUTUHKAN, ADA DI SINI</span>
                    <h2 class="mt-5 text-3xl font-extrabold tracking-tight text-[#383451] sm:text-4xl">Banyak yang bisa dikerjakan,<br class="hidden sm:block"> tapi tetap simpel dipakai.</h2>
                    <p class="mt-4 text-sm leading-7 text-[#77738a] sm:text-base">Dari data produk sampai laporan, setiap kebutuhan punya tempatnya sendiri.</p>
                </div>
                <div class="mt-12 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="rounded-3xl border border-[#eeeaf8] bg-[#fbfaff] p-6 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-violet-100/70"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eee8ff] text-2xl">📦</span><h3 class="mt-5 font-bold text-[#49445f]">Produk & katalog</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Kelola produk, kategori, supplier, SKU, harga, atribut seperti warna dan ukuran, serta stok minimum.</p></article>
                    <article class="rounded-3xl border border-[#e7f3ec] bg-[#f7fcf8] p-6 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-100/70"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e5f7ec] text-2xl">🧺</span><h3 class="mt-5 font-bold text-[#49445f]">Stok & pergerakan</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Catat barang masuk dan keluar, konfirmasi penerimaan, pantau riwayat, serta sesuaikan stok dengan stock opname.</p></article>
                    <article class="rounded-3xl border border-[#faeee1] bg-[#fffbf5] p-6 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-100/70"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fff0dc] text-2xl">👩‍👩‍👧‍👦</span><h3 class="mt-5 font-bold text-[#49445f]">Tim & hak akses</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Atur akun, peran, dan ruang kerja Admin, Manajer Gudang, Staff Gudang, serta Customer.</p></article>
                    <article class="rounded-3xl border border-[#e7f1f8] bg-[#f7fbfe] p-6 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-100/70"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e5f3fb] text-2xl">📊</span><h3 class="mt-5 font-bold text-[#49445f]">Laporan & insight</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Lihat posisi stok, barang masuk/keluar, aktivitas pengguna, dan rekap per periode maupun kategori.</p></article>
                    <article class="rounded-3xl border border-[#f3eaf8] bg-[#fdf9ff] p-6 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-violet-100/70"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f2e8fa] text-2xl">🛍️</span><h3 class="mt-5 font-bold text-[#49445f]">Belanja customer</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Beli produk, kirim pembayaran manual, lacak pesanan, berikan feedback, dan ajukan refund.</p></article>
                </div>
            </div>
        </section>

        <section id="peran" class="relative overflow-hidden px-5 py-20 sm:px-8 lg:py-24">
            <div class="pointer-events-none absolute -left-40 top-0 -z-10 h-96 w-96 rounded-full bg-[#e9ddff]/70 blur-[100px]"></div>
            <div class="pointer-events-none absolute -right-32 bottom-0 -z-10 h-96 w-96 rounded-full bg-[#d8f4e6]/75 blur-[100px]"></div>
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center"><span class="inline-flex rounded-full bg-white/80 px-4 py-2 text-xs font-bold text-[#7e69cf] shadow-sm">SATU SISTEM, EMPAT RUANG KERJA</span><h2 class="mt-5 text-3xl font-extrabold tracking-tight text-[#383451] sm:text-4xl">Semua tahu apa yang perlu dilakukan.</h2><p class="mt-4 text-sm leading-7 text-[#77738a] sm:text-base">Hak akses dan menu menyesuaikan tugas masing-masing. Data stok tetap tersambung untuk seluruh tim.</p></div>
                <div class="mt-12 grid gap-5 lg:grid-cols-4">
                    <article class="rounded-3xl border border-white bg-white/85 p-7 shadow-lg shadow-violet-100/60"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eee8ff] text-lg font-extrabold text-[#806bd0]">A</span><h3 class="mt-5 text-lg font-bold text-[#49445f]">Admin</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Punya kendali penuh untuk mengatur produk, kategori, atribut, supplier, pengguna, dan pengaturan aplikasi.</p><ul class="mt-5 space-y-2.5 text-xs font-medium text-[#706b82]"><li>✓ Dashboard & grafik pergerakan stok</li><li>✓ CRUD katalog dan manajemen stok</li><li>✓ Kelola akun, role, laporan & pengaturan</li></ul></article>
                    <article class="rounded-3xl border border-white bg-white/85 p-7 shadow-lg shadow-emerald-100/60"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e5f7ec] text-lg font-extrabold text-[#5ca981]">M</span><h3 class="mt-5 text-lg font-bold text-[#49445f]">Manajer Gudang</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Memantau barang dan mengatur kelancaran persediaan setiap hari.</p><ul class="mt-5 space-y-2.5 text-xs font-medium text-[#706b82]"><li>✓ Ringkasan stok menipis dan aktivitas hari ini</li><li>✓ Lihat katalog produk dan detailnya</li><li>✓ Catat transaksi, stock opname & lihat laporan</li></ul></article>
                    <article class="rounded-3xl border border-white bg-white/85 p-7 shadow-lg shadow-amber-100/60"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fff0dc] text-lg font-extrabold text-[#c4934d]">S</span><h3 class="mt-5 text-lg font-bold text-[#49445f]">Staff Gudang</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Fokus pada tugas operasional dan konfirmasi barang yang ditangani.</p><ul class="mt-5 space-y-2.5 text-xs font-medium text-[#706b82]"><li>✓ Lihat daftar tugas penerimaan barang</li><li>✓ Siapkan dan konfirmasi barang keluar</li><li>✓ Pantau ringkasan pekerjaan hari ini</li></ul></article>
                    <article class="rounded-3xl border border-white bg-white/85 p-7 shadow-lg shadow-sky-100/60"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e5f3fb] text-lg font-extrabold text-[#5a91b8]">C</span><h3 class="mt-5 text-lg font-bold text-[#49445f]">Customer</h3><p class="mt-2 text-sm leading-6 text-[#817d91]">Belanja produk dengan riwayat transaksi yang tersambung ke stok.</p><ul class="mt-5 space-y-2.5 text-xs font-medium text-[#706b82]"><li>✓ Bayar manual dan pantau status pesanan</li><li>✓ Beri feedback pada produk yang dibeli</li><li>✓ Ajukan refund dan lihat riwayatnya</li></ul></article>
                </div>
            </div>
        </section>

        <section id="cara-kerja" class="px-5 pb-20 sm:px-8 lg:pb-24">
            <div class="mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-gradient-to-r from-[#8270df] via-[#9278da] to-[#70b99b] p-[1px] shadow-xl shadow-violet-200/50">
                <div class="rounded-[calc(2rem-1px)] bg-white/95 px-6 py-10 text-center sm:px-12 sm:py-14">
                    <svg class="mx-auto h-8 w-8 text-[#7964dc]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v13H4zM8 7V5a4 4 0 0 1 8 0v2m-4 5v3"/></svg>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-[#383451] sm:text-3xl">Siap bikin urusan stok lebih santai?</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-[#77738a]">Masuk ke ruang kerja sesuai peranmu dan lanjutkan mengelola persediaan bersama tim.</p>
                    <a href="{{ route('login') }}" data-loading-link class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#7964dc] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-violet-200 transition hover:-translate-y-0.5 hover:bg-[#6854c9]">Masuk ke Stockify <span aria-hidden="true">→</span></a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-[#eeebf5] bg-white/70 px-5 py-6 text-center text-xs text-[#89849b] sm:px-8">
        <span class="font-semibold text-[#66617e]">{{ $applicationName }}</span> <span aria-hidden="true">·</span> Persediaan tertata, operasional lebih ringan &hearts; By Anandita Galuh SK
    </footer>
</div>
@endsection
