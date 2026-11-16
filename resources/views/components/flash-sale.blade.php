{{-- Flash sale tanggal kembar di Beranda (contoh SOCO dari klien). Diatur admin di Promo → Flash sale.
     Sebelum mulai: hitung mundur + harga yang akan berlaku. Saat berlangsung: harga flash berlaku di keranjang dan checkout.
     Harga sengaja tampil di sini walau kartu produk biasa tanpa harga: inti flash sale adalah potongannya. --}}
@props(['sale'])

@php
    use App\Models\Setting;
    use App\Support\Rupiah;

    $running = $sale->isRunning();
    $target = $running ? $sale->ends_at : $sale->starts_at;
    $remaining = max(0, $target->getTimestamp() - now()->getTimestamp());
    $items = $sale->items->filter(fn ($i) => $i->variant?->is_active && $i->variant->product?->is_visible && $i->sale_price < $i->variant->price)->values();
    $start = $sale->starts_at->locale('id');
    $when = $sale->starts_at->isSameDay($sale->ends_at)
        ? $start->translatedFormat('l, j F Y').', '.$sale->starts_at->format('H.i').'–'.$sale->ends_at->format('H.i').' WIB'
        : $start->translatedFormat('j F, H.i').' sampai '.$sale->ends_at->locale('id')->translatedFormat('j F Y, H.i').' WIB';
    $lowStock = Setting::int('low_stock_threshold');
    $parts = ['hari' => intdiv($remaining, 86400), 'jam' => intdiv($remaining % 86400, 3600), 'menit' => intdiv($remaining % 3600, 60), 'detik' => $remaining % 60];
    $box = 'inline-flex min-w-[46px] flex-col items-center rounded-xl bg-ink px-2 py-1.5 text-cream';
@endphp

@if ($items->isNotEmpty())
    <section aria-labelledby="judul-flash" class="mx-auto max-w-7xl px-4 pt-14 sm:px-6 lg:px-12"
             x-data="{
                 sisa: {{ $remaining }},
                 init() {
                     // Hitung mundur memakai sisa detik dari server, jadi tidak bergantung pada jam HP pembeli
                     const akhir = Date.now() + this.sisa * 1000;
                     this.t = setInterval(() => {
                         this.sisa = Math.max(0, Math.round((akhir - Date.now()) / 1000));
                         if (this.sisa === 0) { clearInterval(this.t); setTimeout(() => location.reload(), 1500); }
                     }, 1000);
                 },
                 get hari() { return Math.floor(this.sisa / 86400); },
                 dua(n) { return String(n).padStart(2, '0'); },
                 geser(arah) {
                     const halus = ! matchMedia('(prefers-reduced-motion: reduce)').matches;
                     this.$refs.track.scrollBy({ left: arah * this.$refs.track.clientWidth * 0.9, behavior: halus ? 'smooth' : 'auto' });
                 },
             }">
        <div class="flex flex-col gap-6 rounded-3xl bg-petal px-4 py-6 sm:px-8 sm:py-8">
            <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
                <div class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 id="judul-flash" class="inline-flex items-center gap-2 font-display text-[28px] leading-none sm:text-4xl">
                            {{-- Petir: penanda "flash" --}}
                            <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" class="shrink-0"><path d="M13.2 2 4.5 13.4h6.1L9.8 22l8.7-11.5h-6.1z"/></svg>
                            {{ $sale->name }}
                        </h2>
                        <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold">{{ $running ? 'Sedang berlangsung' : 'Segera dimulai' }}</span>
                    </div>
                    <p class="text-[15px] text-ink-soft">{{ $when }}. {{ $running ? 'Harga khusus berlaku sampai waktu habis.' : 'Harga khusus baru berlaku saat flash sale dimulai.' }}</p>
                </div>

                {{-- Hitung mundur --}}
                <div class="flex flex-wrap items-center gap-2" role="timer" aria-label="{{ $running ? 'Berakhir dalam' : 'Mulai dalam' }}">
                    <span class="text-sm font-semibold">{{ $running ? 'Berakhir dalam' : 'Mulai dalam' }}</span>
                    <span class="{{ $box }}" x-show="hari > 0" @if ($parts['hari'] === 0) style="display: none" @endif>
                        <span class="text-lg font-bold leading-tight tabular-nums" x-text="hari">{{ $parts['hari'] }}</span><span class="text-[11px]">hari</span>
                    </span>
                    <span class="{{ $box }}"><span class="text-lg font-bold leading-tight tabular-nums" x-text="dua(Math.floor(sisa % 86400 / 3600))">{{ sprintf('%02d', $parts['jam']) }}</span><span class="text-[11px]">jam</span></span>
                    <span class="{{ $box }}"><span class="text-lg font-bold leading-tight tabular-nums" x-text="dua(Math.floor(sisa % 3600 / 60))">{{ sprintf('%02d', $parts['menit']) }}</span><span class="text-[11px]">menit</span></span>
                    <span class="{{ $box }}"><span class="text-lg font-bold leading-tight tabular-nums" x-text="dua(sisa % 60)">{{ sprintf('%02d', $parts['detik']) }}</span><span class="text-[11px]">detik</span></span>
                </div>
            </div>

            {{-- Panah di tepi kotak pink (tengahnya tepat di garis tepi), tidak menutupi kartu --}}
            <div class="relative">
                <ul x-ref="track" class="banner-track -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-4 px-4 py-2 sm:-mx-8 sm:scroll-px-8 sm:px-8">
                    @foreach ($items as $item)
                        @php
                            $variant = $item->variant;
                            $product = $variant->product;
                            $cover = $product->cover;
                        @endphp
                        <li class="w-[calc(50%-0.5rem)] shrink-0 snap-start sm:w-[calc((100%-2rem)/3)] lg:w-[calc((100%-4rem)/5)]">
                            <a href="{{ route('produk.show', $product) }}?varian={{ $variant->sku }}"
                               class="group flex h-full flex-col gap-3 rounded-[20px] bg-white p-3 transition duration-300 ease-out hover:shadow-lg focus-visible:shadow-lg motion-safe:hover:scale-[1.03] motion-safe:focus-visible:scale-[1.03]">
                                <span class="relative block aspect-square overflow-hidden rounded-2xl bg-cream">
                                    @if ($cover)
                                        <img src="{{ $cover->url(800) }}" width="{{ $cover->width }}" height="{{ $cover->height }}" alt="" loading="lazy" decoding="async"
                                             @class(['h-full w-full object-cover', 'opacity-55' => $variant->stock === 0])>
                                    @endif
                                    <span class="absolute left-2 top-2 rounded-full bg-ink px-2.5 py-1 text-[13px] font-bold text-cream">-{{ $item->discountPercent() }}%</span>
                                    @if ($variant->stock === 0)
                                        <span class="absolute bottom-2 left-2 rounded-full border border-ink bg-white px-2.5 py-1 text-[13px] font-semibold">Stok habis</span>
                                    @elseif ($variant->stock <= $lowStock)
                                        <span class="absolute bottom-2 left-2 rounded-full bg-white px-2.5 py-1 text-[13px] font-semibold">Sisa {{ $variant->stock }}</span>
                                    @endif
                                </span>
                                <span class="flex flex-1 flex-col gap-0.5 px-1">
                                    @if ($product->brand)
                                        <span class="eyebrow">{{ $product->brand->name }}</span>
                                    @endif
                                    <span class="font-display text-[17px] leading-tight group-hover:underline">{{ $product->name }}</span>
                                    <span class="text-sm text-ink-soft">{{ $variant->aroma->name }}@if ($variant->size) · {{ $variant->size->label }}@endif</span>
                                    <span class="mt-auto flex flex-wrap items-baseline gap-x-2 pt-2">
                                        <span class="text-lg font-bold sm:text-xl">{{ Rupiah::format($item->sale_price) }}</span>
                                        <span class="text-sm text-ink-soft line-through">{{ Rupiah::format($variant->price) }}</span>
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @if ($items->count() > 2)
                    <button type="button" @click="geser(-1)" aria-label="Produk flash sale sebelumnya"
                            class="absolute -left-[3.375rem] top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition-colors hover:bg-cream sm:flex">
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg>
                    </button>
                    <button type="button" @click="geser(1)" aria-label="Produk flash sale berikutnya"
                            class="absolute -right-[3.375rem] top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition-colors hover:bg-cream sm:flex">
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                @endif
            </div>
        </div>
    </section>
@endif
