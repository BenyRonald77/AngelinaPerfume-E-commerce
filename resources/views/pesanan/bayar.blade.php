@php
    use App\Enums\OrderStatus;
    use App\Support\Rupiah;
    use App\Support\Waktu;
    use App\Support\WhatsApp;

    $status = $order->status;
@endphp

<x-layouts.shop :title="'Pembayaran '.$order->code">
    <div class="mx-auto flex max-w-2xl flex-col gap-6 px-4 pb-20 pt-10 sm:px-6">
        <div class="flex flex-col gap-6 rounded-3xl bg-white p-6 sm:p-10">
            <div class="flex flex-col gap-2">
                <p class="text-[15px] text-ink-soft">Kode pesanan <span class="font-bold tracking-wide text-ink">{{ $order->code }}</span></p>
                <h1 class="font-display text-[30px] leading-tight sm:text-[36px]">{{ match ($status) {
                    OrderStatus::MenungguPembayaran => 'Selesaikan pembayaran',
                    OrderStatus::Kedaluwarsa => 'Batas bayar terlewat',
                    OrderStatus::Dibatalkan => 'Pesanan dibatalkan',
                    default => 'Pembayaran diterima',
                } }}</h1>
            </div>

            <dl class="flex flex-col gap-2 rounded-2xl bg-cream p-5 text-[15px]">
                <div class="flex justify-between gap-4"><dt>Total bayar</dt><dd class="text-2xl font-bold">{{ Rupiah::format($order->total) }}</dd></div>
                @if ($status === OrderStatus::MenungguPembayaran && $order->payment_due_at)
                    <div class="flex flex-wrap justify-between gap-x-4"><dt>Bayar sebelum</dt><dd class="font-semibold">{{ Waktu::lengkap($order->payment_due_at) }}</dd></div>
                @endif
            </dl>

            @if ($status === OrderStatus::MenungguPembayaran)
                @if ($xenditEnabled)
                    <form method="post" action="{{ route('pesanan.bayar.mulai', $order) }}" class="flex flex-col gap-3">
                        @csrf
                        <button type="submit" class="btn-primary h-14 w-full">Bayar sekarang</button>
                        <p class="text-sm text-ink-soft">Kamu diarahkan ke halaman pembayaran Xendit: virtual account bank, e-wallet, atau QRIS. Setelah bayar, status pesanan berubah otomatis dalam beberapa menit.</p>
                    </form>
                @else
                    {{-- Kunci Xendit belum dipasang: pembayaran dikonfirmasi admin secara manual --}}
                    <div class="card-dashed flex flex-col items-start gap-3 p-5">
                        <p class="text-[15px]">Pembayaran online sedang disiapkan. Hubungi admin via WhatsApp dan sebutkan kode pesanan untuk mendapatkan cara pembayaran. Stok sudah kami simpan untukmu sampai batas bayar.</p>
                        @if ($wa = WhatsApp::link("Halo Angelia Perfume, saya mau bayar pesanan {$order->code} sebesar ".Rupiah::format($order->total).'.'))
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-primary">Hubungi admin via WhatsApp</a>
                        @else
                            <p class="text-[15px] font-semibold">WhatsApp {{ WhatsApp::display() }}</p>
                        @endif
                    </div>
                @endif
            @elseif ($status->releasesStock())
                <p class="text-[15px]">
                    {{ $order->cancel_reason ?: 'Pesanan ini dibatalkan.' }} Barang sudah dikembalikan ke stok; silakan pesan lagi bila masih berminat.
                </p>
                <form method="post" action="{{ route('akun.pesanan.beli-lagi', $order) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Masukkan lagi ke keranjang</button>
                </form>
            @else
                <p class="text-[15px]">Terima kasih. Pesananmu kami siapkan; nomor resi dikirim lewat email dan tampil di halaman pesanan begitu paket dikirim.</p>
            @endif

            <a href="{{ route('akun.pesanan.show', $order) }}" class="inline-flex min-h-[44px] items-center self-start text-[15px] font-semibold underline underline-offset-4">Lihat detail pesanan</a>
        </div>
    </div>
</x-layouts.shop>
