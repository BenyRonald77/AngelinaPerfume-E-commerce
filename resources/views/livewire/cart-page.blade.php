@php
    use App\Support\Rupiah;

    $count = $this->lines->sum(fn ($line) => $line['item']->quantity);
    $hasProblem = $this->lines->contains(fn ($line) => $line['problem']);
    $next = $this->gift['next'];
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 pb-20 pt-8 sm:px-6 lg:px-8">
    <h1 class="font-display text-[34px] leading-tight sm:text-[40px]">Keranjang</h1>

    @if ($this->lines->isEmpty())
        <div class="card-dashed flex flex-col items-start gap-4 p-8">
            <p class="text-[17px]">Keranjang masih kosong. Pilih aroma dan ukuran di katalog, lalu tekan "Tambah ke keranjang".</p>
            <a href="{{ route('produk.index') }}" class="btn-primary">Lihat katalog</a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_360px] lg:items-start">
            <ul class="divide-y divide-dusty-pink/40 rounded-3xl bg-white px-5 sm:px-7" aria-label="Isi keranjang">
                @foreach ($this->lines as ['item' => $item, 'problem' => $problem])
                    @php
                        $variant = $item->variant;
                        $product = $variant->product;
                        $image = $variant->aroma->imageUrl(800) ?? $product->cover?->url(800);
                        $max = \App\Services\CartService::maxQuantity($variant);
                    @endphp
                    <li wire:key="baris-{{ $item->id }}" class="flex gap-4 py-5 sm:gap-5">
                        <a href="{{ route('produk.show', $product) }}?varian={{ $variant->sku }}" class="shrink-0">
                            @if ($image)
                                <img src="{{ $image }}" alt="" width="96" height="96" loading="lazy" class="h-20 w-20 rounded-2xl bg-cream object-cover sm:h-24 sm:w-24">
                            @else
                                <span class="block h-20 w-20 rounded-2xl bg-cream sm:h-24 sm:w-24"></span>
                            @endif
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col gap-3">
                            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                                <div class="flex min-w-0 flex-col gap-0.5">
                                    <a href="{{ route('produk.show', $product) }}?varian={{ $variant->sku }}" class="font-display text-lg leading-snug hover:underline">{{ $product->name }}</a>
                                    <p class="text-[15px] text-ink-soft">{{ $variant->aroma->name }}@if ($variant->size) · {{ $variant->size->label }}@endif</p>
                                    @if ($item->quantity > 1)
                                        <p class="text-sm text-ink-soft">{{ Rupiah::format($variant->price) }} per botol</p>
                                    @endif
                                </div>
                                <p class="text-lg font-bold">{{ Rupiah::format($item->lineTotal()) }}</p>
                            </div>

                            @if ($problem)
                                <p class="text-sm font-medium text-red-700" role="alert">{{ $problem }}</p>
                            @endif

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="inline-flex items-center gap-1 rounded-full border border-dusty-pink/60 bg-white p-1" role="group" aria-label="Jumlah {{ $product->name }} {{ $variant->aroma->name }}">
                                    <button type="button" wire:click="decrement({{ $item->id }})" @disabled($item->quantity <= 1) aria-label="Kurangi jumlah"
                                            class="h-11 w-11 rounded-full bg-cream text-[22px] transition-colors enabled:hover:bg-blush disabled:text-ink-soft/50">−</button>
                                    <span class="min-w-[32px] text-center text-lg font-semibold" aria-live="polite">{{ $item->quantity }}</span>
                                    <button type="button" wire:click="increment({{ $item->id }})" @disabled($item->quantity >= $max) aria-label="Tambah jumlah"
                                            class="h-11 w-11 rounded-full bg-cream text-[22px] transition-colors enabled:hover:bg-blush disabled:text-ink-soft/50">+</button>
                                </div>
                                <button type="button" wire:click="remove({{ $item->id }})" class="min-h-[44px] text-[15px] font-semibold underline underline-offset-4">Hapus</button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside aria-labelledby="judul-ringkasan" class="flex flex-col gap-5 rounded-3xl bg-white p-6 lg:sticky lg:top-6">
                <h2 id="judul-ringkasan" class="text-xl font-bold">Ringkasan</h2>

                <dl class="flex flex-col gap-3 text-[15px]">
                    <div class="flex justify-between gap-4">
                        <dt>Subtotal ({{ $count }} barang)</dt>
                        <dd class="font-semibold">{{ Rupiah::format($this->subtotal) }}</dd>
                    </div>
                    @foreach ($this->gift['earned'] as $rule)
                        <div class="flex justify-between gap-4">
                            <dt>{{ $rule->name }}</dt>
                            <dd class="font-semibold">Rp0</dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between gap-4 text-ink-soft">
                        <dt>Ongkir</dt>
                        <dd>Dihitung saat checkout</dd>
                    </div>
                </dl>

                @if ($next)
                    @php($pct = min(100, (int) round($this->subtotal / $next->min_subtotal * 100)))
                    <div class="flex flex-col gap-2 rounded-2xl bg-blush/45 px-4 py-3.5">
                        <p class="text-[15px] font-medium">Tambah {{ Rupiah::format($next->min_subtotal - $this->subtotal) }} lagi untuk dapat {{ lcfirst($next->name) }}.</p>
                        <span class="block h-2 overflow-hidden rounded-full bg-white" role="progressbar" aria-label="Menuju {{ lcfirst($next->name) }}"
                              aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
                            <span class="block h-full rounded-full bg-ink" style="width: {{ $pct }}%"></span>
                        </span>
                    </div>
                @endif

                @if ($hasProblem)
                    <button type="button" class="btn-primary h-14 w-full" disabled aria-describedby="info-masalah">Lanjut ke checkout</button>
                    <p id="info-masalah" class="text-sm font-medium text-red-700">Perbaiki barang yang bertanda merah dulu.</p>
                @else
                    <a href="{{ route('checkout') }}" class="btn-primary h-14 w-full">Lanjut ke checkout</a>
                    @guest
                        <p class="text-sm text-ink-soft">Kamu akan diminta masuk atau daftar dulu. Isi keranjang tetap tersimpan.</p>
                    @endguest
                @endif
            </aside>
        </div>
    @endif
</div>
