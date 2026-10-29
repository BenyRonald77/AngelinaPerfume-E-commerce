@php
    use App\Enums\OrderStatus;
    use App\Support\Rupiah;
    use App\Support\Waktu;
    use App\Support\WhatsApp;

    $status = $order->status;
    $shipment = $order->shipment;
    $stopped = in_array($status, [OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan, OrderStatus::Retur], true);
    // Langkah normal pesanan; waktu kosong berarti belum sampai di langkah itu
    $steps = [
        ['label' => 'Pesanan dibuat', 'at' => $order->created_at],
        ['label' => 'Pembayaran diterima', 'at' => $order->paid_at],
        ['label' => 'Diproses', 'at' => $order->processed_at],
        ['label' => 'Dikirim', 'at' => $order->shipped_at],
        ['label' => 'Selesai', 'at' => $order->completed_at],
    ];
@endphp

<x-layouts.akun :title="'Pesanan '.$order->code" heading="Detail pesanan">
    <a href="{{ route('akun.pesanan') }}" class="-mt-2 inline-flex min-h-[44px] items-center self-start text-[15px] font-semibold underline underline-offset-4">Semua pesanan</a>

    <div class="grid gap-6 lg:grid-cols-[1fr_360px] lg:items-start">
        <div class="flex flex-col gap-6">
            <section aria-labelledby="judul-status" class="flex flex-col gap-5 rounded-3xl bg-white p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="judul-status" class="text-xl font-bold tracking-wide">{{ $order->code }}</h2>
                    <x-order-status :status="$status" />
                </div>

                @if ($stopped)
                    <p class="text-[15px]">
                        @if ($status === OrderStatus::Retur)
                            Pesanan ini dicatat sebagai retur. Admin akan menghubungimu bila ada yang perlu dikonfirmasi.
                        @else
                            {{ $order->cancel_reason ?: 'Pesanan dibatalkan.' }}
                            @if ($order->cancelled_at) ({{ Waktu::lengkap($order->cancelled_at) }}) @endif
                        @endif
                    </p>
                @else
                    <ol class="flex flex-col gap-0">
                        @foreach ($steps as $i => $step)
                            @php($done = (bool) $step['at'])
                            <li class="relative flex gap-4 pb-5 last:pb-0">
                                @unless ($loop->last)
                                    <span aria-hidden="true" @class(['absolute left-[11px] top-6 h-[calc(100%-24px)] w-0.5', 'bg-ink' => $done && $steps[$i + 1]['at'], 'bg-dusty-pink/50' => ! ($done && $steps[$i + 1]['at'])])></span>
                                @endunless
                                <span aria-hidden="true" @class(['relative mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full', 'bg-ink text-white' => $done, 'border-2 border-dusty-pink bg-white' => ! $done])>
                                    @if ($done)
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                                    @endif
                                </span>
                                <span class="flex flex-col">
                                    <span @class(['text-[15px] font-semibold', 'text-ink-soft' => ! $done])>{{ $step['label'] }}<span class="sr-only">{{ $done ? ', sudah' : ', belum' }}</span></span>
                                    @if ($done)
                                        <span class="text-sm text-ink-soft">{{ Waktu::lengkap($step['at']) }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if ($status === OrderStatus::MenungguPembayaran)
                    <div class="flex flex-wrap items-center gap-4 rounded-2xl bg-blush/45 p-4">
                        <p class="flex-1 text-[15px]">Bayar sebelum <strong>{{ Waktu::lengkap($order->payment_due_at) }}</strong>.</p>
                        <a href="{{ route('pesanan.bayar', $order) }}" class="btn-primary">Bayar sekarang</a>
                    </div>
                @endif
            </section>

            @if ($shipment)
                <section aria-labelledby="judul-kirim" class="flex flex-col gap-4 rounded-3xl bg-white p-6 sm:p-8">
                    <h2 id="judul-kirim" class="text-xl font-bold">Pengiriman</h2>
                    <dl class="grid gap-x-6 gap-y-3 text-[15px] sm:grid-cols-[140px_1fr]">
                        <dt class="text-ink-soft">Kurir</dt>
                        <dd class="font-semibold">{{ $shipment->courier }}@if ($shipment->service) <span class="font-normal text-ink-soft">· {{ $shipment->service }}</span>@endif</dd>
                        <dt class="text-ink-soft">Nomor resi</dt>
                        <dd>
                            @if ($shipment->tracking_number)
                                <span class="font-semibold tracking-wide">{{ $shipment->tracking_number }}</span>
                            @else
                                <span class="text-ink-soft">Tampil di sini begitu paket dikirim</span>
                            @endif
                        </dd>
                        <dt class="text-ink-soft">Penerima</dt>
                        <dd>{{ $order->recipient_name }} · {{ $order->phone }}<br><span class="text-ink-soft">{{ $order->addressOneLine() }}</span></dd>
                    </dl>

                    @if ($shipment->trackingUrl() || $status === OrderStatus::Dikirim)
                        <div class="flex flex-wrap gap-3 pt-1">
                            @if ($url = $shipment->trackingUrl())
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn-outline">Lacak paket</a>
                            @endif
                            @if ($status === OrderStatus::Dikirim)
                                <form method="post" action="{{ route('akun.pesanan.diterima', $order) }}"
                                      x-data @submit="if (! confirm('Paket sudah kamu terima dalam keadaan baik?')) $event.preventDefault()">
                                    @csrf
                                    <button type="submit" class="btn-primary">Pesanan diterima</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @if ($status === OrderStatus::Dikirim)
                        <p class="text-sm text-ink-soft">Rekam video saat membuka paket. Video unboxing dibutuhkan bila ada klaim botol pecah atau isi tidak sesuai.</p>
                    @endif
                </section>
            @endif
        </div>

        <aside aria-labelledby="judul-barang" class="flex flex-col gap-5 rounded-3xl bg-white p-6">
            <h2 id="judul-barang" class="text-xl font-bold">Barang</h2>
            <ul class="flex flex-col gap-3 text-[15px]">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-4">
                        <span class="min-w-0">
                            <span class="block font-semibold">{{ $item->product_name }}</span>
                            <span class="block text-ink-soft">{{ $item->aroma_name }}@if ($item->size_label) · {{ $item->size_label }}@endif · {{ $item->quantity }}×@if ($item->is_gift) · Hadiah @endif</span>
                        </span>
                        <span class="shrink-0 font-semibold">{{ Rupiah::format($item->price * $item->quantity) }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="flex flex-col gap-2.5 border-t border-dusty-pink/40 pt-4 text-[15px]">
                <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd>{{ Rupiah::format($order->subtotal) }}</dd></div>
                @if ($order->packaging_fee)
                    <div class="flex justify-between gap-4"><dt>Extra dus</dt><dd>{{ Rupiah::format($order->packaging_fee) }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt>Ongkir</dt><dd>{{ Rupiah::format($order->shipping_cost) }}</dd></div>
                <div class="flex justify-between gap-4 pt-2 text-xl font-bold"><dt>Total</dt><dd>{{ Rupiah::format($order->total) }}</dd></div>
            </dl>

            @if ($order->customer_note)
                <p class="rounded-2xl bg-cream p-4 text-[15px]"><span class="font-semibold">Catatanmu:</span> {{ $order->customer_note }}</p>
            @endif

            @unless ($order->isAwaitingPayment())
                <form method="post" action="{{ route('akun.pesanan.beli-lagi', $order) }}">
                    @csrf
                    <button type="submit" class="btn-outline w-full">Beli lagi</button>
                </form>
            @endunless

            @if ($wa = WhatsApp::link("Halo Angelia Perfume, saya mau tanya pesanan {$order->code}."))
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center justify-center text-[15px] font-semibold underline underline-offset-4">Tanya soal pesanan ini</a>
            @endif
        </aside>
    </div>
</x-layouts.akun>
