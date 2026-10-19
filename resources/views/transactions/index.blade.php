@extends('layouts.stockify')

@section('title', 'Stok & transaksi - Stockify')

@section('content')
<div><p class="text-sm font-medium text-teal-800">Operasional gudang</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Stok & transaksi</h1><p class="mt-1 text-sm text-slate-500">Catat pergerakan barang, konfirmasi pekerjaan, dan sesuaikan hasil stock opname.</p></div>
@if (session('status'))<p class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p>@endif
@if ($errors->any())<p class="mt-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">{{ $errors->first() }}</p>@endif

@if ($canCreate || $canOpname)
<section class="mt-6 grid gap-5 xl:grid-cols-2">
    @if ($canCreate)
    <form action="{{ route('transactions.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">@csrf
        <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">Catat barang masuk / keluar</h2><p class="mt-1 text-sm text-slate-500">Transaksi baru menunggu pemeriksaan dan konfirmasi Staff Gudang.</p></div>@if (auth()->user()->role === 'Staff Gudang')<a href="{{ route('products.create') }}" class="shrink-0 text-xs font-semibold text-teal-800 hover:text-teal-950">Produk belum terdaftar?</a>@endif</div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="text-xs font-medium text-slate-600 sm:col-span-2">Produk<select name="product_id" required class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Pilih produk</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} (stok {{ number_format((int) ($stockBalances[$product->id] ?? 0)) }})</option>@endforeach</select></label>
            <label class="text-xs font-medium text-slate-600 sm:col-span-2">Supplier barang masuk (opsional; default mengikuti supplier produk)
                <select name="supplier_id" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">Gunakan supplier produk</option>
                    @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-medium text-slate-600">Jenis<select name="type" required class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option>Masuk</option><option>Keluar</option></select></label>
            <label class="text-xs font-medium text-slate-600">Jumlah<input name="quantity" type="number" min="1" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></label>
            <label class="text-xs font-medium text-slate-600">Tanggal<input name="date" type="date" value="{{ now()->toDateString() }}" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></label>
            <label class="text-xs font-medium text-slate-600">Catatan<input name="notes" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Opsional"></label>
        </div>
        <button class="mt-4 rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-900">Simpan transaksi</button>
    </form>
    @endif
    @if ($canOpname)
    <form action="{{ route('opnames.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">@csrf
        <h2 class="font-semibold text-slate-900">Stock opname</h2><p class="mt-1 text-sm text-slate-500">Masukkan hasil hitung fisik; selisih akan langsung menjadi penyesuaian stok.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="text-xs font-medium text-slate-600 sm:col-span-2">Produk<select name="product_id" required class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Pilih produk</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} (sistem {{ number_format((int) ($stockBalances[$product->id] ?? 0)) }})</option>@endforeach</select></label>
            <label class="text-xs font-medium text-slate-600">Jumlah fisik<input name="counted_quantity" type="number" min="0" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></label>
            <label class="text-xs font-medium text-slate-600">Tanggal<input name="date" type="date" value="{{ now()->toDateString() }}" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></label>
            <label class="text-xs font-medium text-slate-600 sm:col-span-2">Catatan<textarea name="notes" rows="2" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></textarea></label>
        </div>
        <button class="mt-4 rounded-xl border border-teal-800 px-4 py-2.5 text-sm font-semibold text-teal-900 hover:bg-teal-50">Simpan stock opname</button>
    </form>
    @endif
</section>
@endif

@if ($canConfirm)
<section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
    <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">Tugas konfirmasi stok</h2><p class="mt-1 text-sm text-slate-600">Periksa barang masuk atau siapkan barang keluar sebelum mengonfirmasi.</p></div><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">{{ $transactions->getCollection()->where('status', 'Pending')->count() }} menunggu di halaman ini</span></div>
</section>
@endif

<section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Riwayat transaksi</h2><p class="mt-1 text-sm text-slate-500">Transaksi yang menunggu konfirmasi belum mengubah saldo stok.</p></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-medium">Tanggal</th><th class="px-5 py-3 font-medium">Produk</th><th class="px-5 py-3 font-medium">Supplier</th><th class="px-5 py-3 font-medium">Jenis</th><th class="px-5 py-3 font-medium">Jumlah</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Dicatat oleh</th>@if ($canConfirm || auth()->user()->role === 'Admin')<th class="px-5 py-3 font-medium">Tindakan</th>@endif</tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse ($transactions as $transaction)
            <tr><td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $transaction->date->locale('id')->translatedFormat('d M Y') }}</td><td class="px-5 py-3.5 font-medium text-slate-800">{{ $transaction->product->name ?? 'Produk dihapus' }}<div class="text-xs font-normal text-slate-400">{{ $transaction->notes }}</div></td><td class="px-5 py-3.5 text-slate-600">{{ $transaction->supplier->name ?? $transaction->product->supplier->name ?? '—' }}</td><td class="px-5 py-3.5 text-slate-600">{{ $transaction->type }}</td><td class="px-5 py-3.5 font-medium">{{ number_format($transaction->quantity) }} unit</td><td class="px-5 py-3.5"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ in_array($transaction->status, ['Diterima', 'Dikeluarkan'], true) ? 'bg-emerald-50 text-emerald-800' : ($transaction->status === 'Pending' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-600') }}">{{ $transaction->status }}</span>@if ($transaction->staff_action)<div class="mt-1 text-xs text-slate-500">{{ $transaction->staff_action }} oleh Staff</div>@endif</td><td class="px-5 py-3.5 text-slate-600">{{ $transaction->user->name ?? '—' }}</td>
                @if ($canConfirm || auth()->user()->role === 'Admin')<td class="whitespace-nowrap px-5 py-3.5">
                    @if ($canConfirm && $transaction->status === 'Pending')
                        @if (! $transaction->staff_action)
                            <form action="{{ route('transactions.prepare', $transaction) }}" method="POST" onsubmit="return confirm('{{ $transaction->type === 'Masuk' ? 'Tandai barang sudah diperiksa?' : 'Tandai barang sudah disiapkan?' }}')">@csrf<button class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100">{{ $transaction->type === 'Masuk' ? 'Tandai diperiksa' : 'Tandai disiapkan' }}</button></form>
                        @else
                            <form action="{{ route('transactions.confirm', $transaction) }}" method="POST" onsubmit="return confirm('Konfirmasi transaksi {{ strtolower($transaction->type) }} ini?')">@csrf<button class="rounded-lg bg-teal-800 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-900">Konfirmasi {{ $transaction->type === 'Masuk' ? 'penerimaan' : 'pengeluaran' }}</button></form>
                        @endif
                    @elseif (auth()->user()->role === 'Admin' && $transaction->status === 'Pending')
                        <form action="{{ route('transactions.reject', $transaction) }}" method="POST" onsubmit="return confirm('Tolak transaksi pending ini?')">@csrf<button class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Tolak</button></form>
                    @else<span class="text-xs text-slate-400">{{ $transaction->status === 'Pending' ? 'Menunggu staff' : 'Sudah diproses' }}</span>@endif
                </td>@endif
            </tr>
        @empty<tr><td colspan="{{ $canConfirm || auth()->user()->role === 'Admin' ? 8 : 7 }}" class="px-5 py-12 text-center text-slate-500">Belum ada transaksi stok.</td></tr>@endforelse
        </tbody>
    </table></div>
    @if ($transactions->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $transactions->links() }}</div>@endif
</section>

@if ($canOpname)
<section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Riwayat stock opname</h2></div>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Produk</th><th class="px-5 py-3">Stok sistem</th><th class="px-5 py-3">Stok fisik</th><th class="px-5 py-3">Selisih</th><th class="px-5 py-3">Petugas</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse ($opnames as $opname)<tr><td class="px-5 py-3 text-slate-600">{{ $opname->date->format('d M Y') }}</td><td class="px-5 py-3 font-medium">{{ $opname->product->name }}</td><td class="px-5 py-3">{{ number_format($opname->system_quantity) }}</td><td class="px-5 py-3">{{ number_format($opname->counted_quantity) }}</td><td class="px-5 py-3 font-semibold {{ $opname->difference < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $opname->difference > 0 ? '+' : '' }}{{ number_format($opname->difference) }}</td><td class="px-5 py-3">{{ $opname->user->name }}</td></tr>
        @empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada stock opname.</td></tr>@endforelse
    </tbody></table></div>
    @if ($opnames->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $opnames->links() }}</div>@endif
</section>
@endif
@endsection
