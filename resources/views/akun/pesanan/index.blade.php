@php
    use App\Support\Rupiah;
    use App\Support\Waktu;
@endphp

<x-layouts.akun title="Pesanan saya">
    @if ($orders->isEmpty())
        <div class="card-dashed flex flex-col items-start gap-4 p-8">
            <p class="text-[17px]">Belum ada pesanan. Pesanan yang kamu buat akan tampil di sini beserta status dan nomor resinya.</p>
            <a href="{{ route('produk.index') }}" class="btn-primary">Lihat katalog</a>
        </div>
    @else
        <ul class="flex flex-col gap-4">
            @foreach ($orders as $order)
                @php
                    $items = $order->items->where('is_gift', false);
                    $first = $items->first();
                    $more = $items->count() - 1;
                @endphp
                <li class="flex flex-col gap-4 rounded-3xl bg-white p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="font-bold tracking-wide">{{ $order->code }}</span>
                            <x-order-status :status="$order->status" />
                        </div>
                        <p class="text-[15px]">
                            {{ $first?->product_name }} {{ $first?->aroma_name }}@if ($first?->size_label) · {{ $first->size_label }}@endif
                            @if ($more > 0) <span class="text-ink-soft">dan {{ $more }} barang lain</span>@endif
                        </p>
                        <p class="text-sm text-ink-soft">
                            {{ Waktu::tanggal($order->created_at) }} · {{ Rupiah::format($order->total) }}
                            @if ($order->shipment?->tracking_number) · Resi {{ $order->shipment->tracking_number }}@endif
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-3">
                        @if ($order->isAwaitingPayment())
                            <a href="{{ route('pesanan.bayar', $order) }}" class="btn-primary">Bayar</a>
                        @endif
                        <a href="{{ route('akun.pesanan.show', $order) }}" class="btn-outline">Detail</a>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($orders->hasPages())
            <nav aria-label="Halaman pesanan" class="flex flex-wrap items-center justify-center gap-3">
                @if ($orders->onFirstPage())
                    <span class="btn-outline min-w-[132px] border-ink-soft/40 text-ink-soft" aria-disabled="true">Sebelumnya</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}" class="btn-outline min-w-[132px]">Sebelumnya</a>
                @endif
                <span class="px-2 text-[15px]">Halaman {{ $orders->currentPage() }} dari {{ $orders->lastPage() }}</span>
                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" class="btn-outline min-w-[132px]">Berikutnya</a>
                @else
                    <span class="btn-outline min-w-[132px] border-ink-soft/40 text-ink-soft" aria-disabled="true">Berikutnya</span>
                @endif
            </nav>
        @endif
    @endif
</x-layouts.akun>
