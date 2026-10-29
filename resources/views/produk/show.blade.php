@php
    use App\Support\Rupiah;
    use App\Support\WhatsApp;

    $initialAroma = $aromas->firstWhere('id', $initial->product_aroma_id);
    $firstImage = $initialAroma?->image ? null : $images->first();
    $mainSrc = $initialAroma?->image ? $initialAroma->imageUrl(800) : $firstImage?->url(800);
    $mainSrcset = $initialAroma?->image
        ? $initialAroma->imageUrl(800).' 800w, '.$initialAroma->imageUrl(1600).' 1600w'
        : ($firstImage ? $firstImage->url(800).' 800w, '.$firstImage->url(1600).' 1600w' : null);
    $mainW = $initialAroma?->image ? $initialAroma->image_width : $firstImage?->width;
    $mainH = $initialAroma?->image ? $initialAroma->image_height : $firstImage?->height;
    $initialTitle = $initialAroma->name.($initial->size ? ' · '.$initial->size->label : '');
    $stockText = fn (int $stock) => match (true) {
        $stock === 0 => 'Stok habis',
        $stock <= $payload['lowStock'] => "Stok menipis · {$stock} botol",
        default => "Tersedia · {$stock} botol",
    };
    $isAccessory = $product->category->slug === \App\Models\Category::AKSESORI;
    $showAromaSearch = $aromas->count() > 12;
    // Status terpilih dibaca dari aria-pressed (diperbarui Alpine), bukan kelas statis yang bisa tertinggal
    $chip = 'inline-flex min-h-[52px] items-center gap-2.5 rounded-full border-2 border-dusty-pink/60 bg-white pr-4 text-[15px] font-medium transition-colors hover:border-ink aria-pressed:border-ink aria-pressed:bg-blush';
    $sizeBtn = 'flex min-h-[64px] min-w-[92px] flex-col items-center justify-center gap-0.5 rounded-2xl border-2 border-dusty-pink/60 bg-white px-3.5 transition-colors enabled:hover:border-ink aria-pressed:border-ink aria-pressed:bg-blush disabled:border-dashed disabled:border-ink-soft/40 disabled:bg-cream disabled:text-ink-soft';
    $thumb = 'block h-20 w-20 overflow-hidden rounded-2xl border-2 border-dusty-pink/45 bg-white opacity-80 transition hover:opacity-100 aria-pressed:border-ink aria-pressed:opacity-100 sm:h-24 sm:w-24';
@endphp

<x-layouts.shop :title="$product->name"
                :description="$product->description
                    ? \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $product->description), 155)
                    : $product->category->name.' '.$product->name.' original di Angelia Perfume, '.$aromas->count().' pilihan aroma.'">
    <div class="mx-auto max-w-7xl px-4 pb-20 pt-6 sm:px-6 lg:px-12" x-data="productPage(@js($payload))">
        <nav aria-label="Breadcrumb" class="pb-7 text-sm text-ink-soft">
            <ol class="flex flex-wrap gap-2">
                <li><a href="{{ route('home') }}" class="text-ink hover:underline">Beranda</a></li>
                <li aria-hidden="true">›</li>
                <li><a href="{{ route('produk.index', ['jenis' => $product->category->slug]) }}" class="text-ink hover:underline">{{ $product->category->name }}</a></li>
                <li aria-hidden="true">›</li>
                <li aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 items-start gap-10 lg:grid-cols-2 lg:gap-16">
            {{-- Galeri: foto aroma terpilih, lalu foto produk --}}
            <div class="flex flex-col gap-4 lg:sticky lg:top-6">
                <div class="aspect-square overflow-hidden rounded-[28px] border border-dusty-pink/45 bg-white">
                    @if ($mainSrc)
                        <img src="{{ $mainSrc }}" srcset="{{ $mainSrcset }}" sizes="(min-width: 1024px) 560px, 92vw"
                             width="{{ $mainW }}" height="{{ $mainH }}" fetchpriority="high"
                             :src="main.src" :srcset="main.srcset" :width="main.w" :height="main.h"
                             alt="{{ $product->name }}" :alt="mainAlt"
                             class="block h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-ink-soft">Foto belum ada</div>
                    @endif
                </div>

                @if ($images->isNotEmpty())
                    <ul class="flex gap-3 overflow-x-auto pb-1" aria-label="Foto lainnya">
                        <li x-show="aroma.image" @if (! $initialAroma?->image) style="display: none" @endif class="shrink-0">
                            <button type="button" @click="active = 'aroma'" :aria-pressed="(active === 'aroma').toString()" aria-pressed="{{ $initialAroma?->image ? 'true' : 'false' }}"
                                    class="{{ $thumb }}"
                                    :aria-label="'Foto aroma ' + aroma.name">
                                <img src="{{ $initialAroma?->imageUrl(800) }}" :src="aroma.image?.src" width="96" height="96" alt="" class="h-full w-full object-cover" loading="lazy">
                            </button>
                        </li>
                        @foreach ($images as $i => $image)
                            <li class="shrink-0">
                                <button type="button" @click="active = {{ $i }}" :aria-pressed="(active === {{ $i }}).toString()"
                                        aria-pressed="{{ ! $initialAroma?->image && $i === 0 ? 'true' : 'false' }}"
                                        aria-label="Foto produk {{ $i + 1 }}" class="{{ $thumb }}">
                                    <img src="{{ $image->url(800) }}" width="96" height="96" alt="" class="h-full w-full object-cover" loading="lazy" decoding="async">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-2">
                    <p class="eyebrow">{{ $product->category->name }} · {{ $product->name }}</p>
                    <h1 class="font-display text-[34px] leading-[1.05] sm:text-[44px] lg:text-[52px]">
                        <span x-text="title">{{ $initialTitle }}</span>
                    </h1>
                    @if ($product->concentration)
                        <p class="text-[15px] text-ink-soft">{{ $product->concentration }}</p>
                    @endif
                    <p class="inline-flex items-center gap-2 text-[15px] font-medium">
                        {{-- Titik menandai status stok: ink = tersedia, pink = menipis, garis = habis --}}
                        <span aria-hidden="true" class="h-2.5 w-2.5 rounded-full"
                              :class="variant.stock === 0 ? 'border-2 border-ink-soft' : (variant.stock <= lowStock ? 'bg-dusty-pink' : 'bg-ink')"></span>
                        <span x-text="stockText">{{ $stockText($initial->stock) }}</span>
                    </p>
                </div>

                <p class="text-[40px] font-bold leading-none" x-text="rp(variant.price)">{{ Rupiah::format($initial->price) }}</p>

                {{-- Notes aroma: kotak bergaris putus-putus seperti di foto varian toko --}}
                <div x-show="hasNotes" @if (! $initialAroma->hasNotes()) style="display: none" @endif class="card-dashed grid gap-4 p-5 sm:grid-cols-3">
                    <template x-for="layer in noteLayers" :key="layer.label">
                        <div class="flex flex-col gap-1" :class="layer.wide ? 'sm:col-span-3' : ''">
                            <span class="eyebrow" x-text="layer.label"></span>
                            <span class="text-[15px]" x-text="layer.value"></span>
                        </div>
                    </template>
                </div>

                <fieldset class="flex flex-col gap-2.5">
                    <legend class="pb-2.5 text-[15px] font-semibold">
                        {{ $isAccessory ? 'Pilihan' : 'Aroma' }} <span class="font-normal text-ink-soft">· <span x-text="aroma.name">{{ $initialAroma->name }}</span></span>
                    </legend>
                    @if ($showAromaSearch)
                        <div class="pb-1">
                            <label for="cari-aroma" class="sr-only">Cari aroma</label>
                            <input id="cari-aroma" type="search" x-model="q" placeholder="Cari di {{ $aromas->count() }} aroma"
                                   class="h-11 w-full rounded-full border-dusty-pink bg-white px-4 text-base placeholder:text-ink-soft focus:border-ink focus:ring-0 sm:max-w-xs">
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-2.5 {{ $showAromaSearch ? 'max-h-[340px] overflow-y-auto pr-1' : '' }}">
                        @foreach ($aromas as $a)
                            @php($inStock = $a->variants->contains(fn ($v) => $v->stock > 0))
                            <button type="button" @click="pickAroma({{ $a->id }})"
                                    @if ($showAromaSearch) x-show="matches(@js(mb_strtolower($a->name)))" @endif
                                    :aria-pressed="(aromaId === {{ $a->id }}).toString()" aria-pressed="{{ $a->id === $initialAroma->id ? 'true' : 'false' }}"
                                    class="{{ $chip }} {{ $a->image ? 'pl-1.5' : 'pl-4' }}">
                                @if ($a->image)
                                    <img src="{{ $a->imageUrl(800) }}" width="40" height="40" alt="" loading="lazy" decoding="async" class="h-10 w-10 rounded-full object-cover">
                                @endif
                                <span>{{ $a->name }}@if ($a->note) <span class="text-ink-soft">({{ $a->note }})</span>@endif</span>
                                @unless ($inStock)
                                    <span class="rounded-full border border-ink-soft px-2 py-0.5 text-xs text-ink-soft">Habis</span>
                                @endunless
                            </button>
                        @endforeach
                    </div>
                </fieldset>

                @if ($variants->contains(fn ($v) => $v->size || $v->note))
                    <fieldset class="flex flex-col gap-2.5">
                        <legend class="pb-2.5 text-[15px] font-semibold">Ukuran</legend>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($variants as $v)
                                <button type="button" @click="pickVariant({{ $v->id }})"
                                        x-show="aromaId === {{ $v->product_aroma_id }}" @if ($v->product_aroma_id !== $initialAroma->id) style="display: none" @endif
                                        @disabled($v->stock === 0)
                                        :aria-pressed="(variantId === {{ $v->id }}).toString()" aria-pressed="{{ $v->id === $initial->id ? 'true' : 'false' }}"
                                        class="{{ $sizeBtn }}">
                                    <span class="text-base font-semibold">{{ $v->size?->label ?? $v->note }}@if ($v->size && $v->note) <span class="font-normal">({{ $v->note }})</span>@endif</span>
                                    <span class="text-[13px]">
                                        @if ($v->stock === 0)
                                            Habis
                                        @else
                                            {{ $isDecant && $v->size ? $v->size->bottle_type->label().' · ' : '' }}{{ Rupiah::format($v->price) }}
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                {{-- Stok habis: tanya lewat WhatsApp (F10, aturan bisnis 9) --}}
                <div x-show="variant.stock === 0" @if ($initial->stock > 0) style="display: none" @endif class="card-dashed flex flex-col gap-3 p-5">
                    <p class="text-[15px]">Ukuran ini sedang habis. Tanyakan kapan tersedia lagi ke admin.</p>
                    <template x-if="whatsapp">
                        <a :href="waLink" target="_blank" rel="noopener" class="btn-outline self-start">Tanya via WhatsApp</a>
                    </template>
                    <template x-if="! whatsapp">
                        <p class="text-[15px] font-semibold">WhatsApp {{ WhatsApp::display() }}</p>
                    </template>
                </div>

                <div x-show="variant.stock > 0" @if ($initial->stock === 0) style="display: none" @endif class="flex flex-col gap-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <span id="label-jumlah" class="text-[15px] font-semibold">Jumlah</span>
                        <div class="inline-flex items-center gap-1 rounded-full border border-dusty-pink/60 bg-white p-1" role="group" aria-labelledby="label-jumlah">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1" aria-label="Kurangi jumlah"
                                    class="h-11 w-11 rounded-full bg-cream text-[22px] transition-colors enabled:hover:bg-blush disabled:text-ink-soft/50">−</button>
                            <span class="min-w-[36px] text-center text-xl font-semibold" aria-live="polite" x-text="qty">1</span>
                            <button type="button" @click="qty = Math.min(maxQty, qty + 1)" :disabled="qty >= maxQty" aria-label="Tambah jumlah"
                                    class="h-11 w-11 rounded-full bg-cream text-[22px] transition-colors enabled:hover:bg-blush disabled:text-ink-soft/50">+</button>
                        </div>
                        <span class="text-sm text-ink-soft">Subtotal <span x-text="rp(subtotal)">{{ Rupiah::format($initial->price) }}</span></span>
                        <span x-show="variant.limit" @if (! $initial->max_per_order) style="display: none" @endif class="text-sm text-ink-soft">
                            Maks. <span x-text="variant.limit">{{ $initial->max_per_order }}</span> botol per pesanan
                        </span>
                    </div>

                    @if ($payload['gift'])
                        <div class="flex flex-col gap-2 rounded-2xl bg-blush/45 px-5 py-3.5">
                            <p class="text-[15px] font-medium" x-text="giftText"></p>
                            <span class="block h-2 overflow-hidden rounded-full bg-white" role="progressbar" aria-label="Menuju free decant"
                                  aria-valuemin="0" aria-valuemax="100" :aria-valuenow="giftPct">
                                <span class="block h-full rounded-full bg-ink" :style="'width: ' + giftPct + '%'"></span>
                            </span>
                        </div>
                    @endif

                    {{-- TODO(tahap 3: Belanja): sambungkan ke keranjang dan checkout; sampai itu tombol nonaktif dengan keterangan --}}
                    <div class="flex flex-col gap-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button type="button" class="btn-outline h-14" disabled aria-describedby="info-keranjang">Tambah ke keranjang</button>
                            <button type="button" class="btn-primary h-14" disabled aria-describedby="info-keranjang">Beli sekarang</button>
                        </div>
                        <p id="info-keranjang" class="text-sm text-ink-soft">
                            Keranjang dan checkout segera hadir. Sementara ini pesan lewat
                            <a href="{{ config('toko.shopee_url') }}" target="_blank" rel="noopener" class="font-semibold text-ink underline underline-offset-2">toko Shopee kami</a>.
                        </p>
                    </div>
                </div>

                <ul class="flex flex-wrap gap-x-6 gap-y-3 text-sm text-ink-soft">
                    <li class="inline-flex items-center gap-2"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z"/><path d="m9 12 2 2 4-4"/></svg>100% original</li>
                    @if ($isDecant)
                        <li class="inline-flex items-center gap-2"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3h4v3h-4z"/><path d="M8 6h8v3l1 2v9a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-9l1-2z"/></svg>Botol kaca disegel</li>
                    @endif
                    @if ($hasBpom)
                        <li class="inline-flex items-center gap-2"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="m9 12 2 2 4-4"/></svg>Terdaftar BPOM</li>
                    @endif
                    <li class="inline-flex items-center gap-2"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Garansi 1×24 jam</li>
                </ul>
            </div>
        </div>

        {{-- Deskripsi, pengiriman, garansi --}}
        <section class="mt-20 flex flex-wrap gap-x-16 gap-y-8" aria-label="Info produk">
            <div role="tablist" aria-label="Info produk" class="flex w-full gap-1 overflow-x-auto sm:w-[220px] sm:flex-none sm:flex-col sm:overflow-visible"
                 @keydown.arrow-right.prevent="moveTab(1)" @keydown.arrow-down.prevent="moveTab(1)"
                 @keydown.arrow-left.prevent="moveTab(-1)" @keydown.arrow-up.prevent="moveTab(-1)">
                @foreach (['Deskripsi', 'Pengiriman', 'Garansi & retur'] as $i => $label)
                    <button type="button" role="tab" id="tab-{{ $i }}" aria-controls="panel-{{ $i }}"
                            :aria-selected="(tab === {{ $i }}).toString()" aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            :tabindex="tab === {{ $i }} ? 0 : -1" tabindex="{{ $i === 0 ? 0 : -1 }}"
                            @click="tab = {{ $i }}"
                            class="min-h-[52px] shrink-0 border-b-2 border-dusty-pink/50 px-1 text-left text-lg transition-colors hover:border-ink aria-selected:border-ink aria-selected:font-semibold">{{ $label }}</button>
                @endforeach
            </div>

            <div class="min-w-0 flex-[999_1_520px]">
                <div role="tabpanel" id="panel-0" aria-labelledby="tab-0" x-show="tab === 0" class="flex flex-col gap-4">
                    <h2 class="font-display text-[32px] leading-tight">Tentang {{ $isDecant ? 'decant ini' : 'produk ini' }}</h2>
                    @if ($product->description)
                        <div class="max-w-prose whitespace-pre-line text-[17px] leading-relaxed">{{ $product->description }}</div>
                    @elseif ($isDecant)
                        <p class="max-w-prose text-[17px] leading-relaxed">{{ $product->name }} dalam botol decant Angelia Perfume: cairan asli dipindahkan dari botol original dengan suntikan berbeda untuk setiap parfum, tanpa tambahan alkohol.</p>
                        <p class="max-w-prose text-[17px] leading-relaxed">Ukuran 0,5 ml dan 1 ml memakai vial oles kaca; 2 ml sampai 10 ml memakai botol kaca spray. Semua botol disegel sebelum dikirim.</p>
                    @else
                        <p class="text-[17px]">Deskripsi produk ini belum tersedia.</p>
                    @endif
                </div>
                <div role="tabpanel" id="panel-1" aria-labelledby="tab-1" x-show="tab === 1" style="display: none" class="flex flex-col gap-4">
                    <h2 class="font-display text-[32px] leading-tight">Pengiriman</h2>
                    <p class="max-w-prose text-[17px] leading-relaxed">Berat paket <span x-text="variant.weight">{{ $initial->weight_grams }}</span> gram per botol. Ongkir dihitung saat checkout dari berat paket dan alamat tujuan.</p>
                    <p class="max-w-prose text-[17px] leading-relaxed">Jam kirim: [ATURAN JAM KIRIM, menunggu konfirmasi toko].</p>
                </div>
                <div role="tabpanel" id="panel-2" aria-labelledby="tab-2" x-show="tab === 2" style="display: none" class="flex flex-col gap-4">
                    <h2 class="font-display text-[32px] leading-tight">Garansi & retur</h2>
                    <p class="max-w-prose text-[17px] leading-relaxed">Komplain diterima maksimal 1×24 jam sejak barang diterima, dengan video unboxing dari awal sampai akhir.</p>
                    <p class="max-w-prose text-[17px] leading-relaxed">Berlaku bila produk tidak sesuai, pecah atau bocor, atau tidak original.</p>
                </div>
            </div>
        </section>

        @if ($related->isNotEmpty())
            <section class="mt-20 flex flex-col gap-6" aria-labelledby="judul-terkait">
                <h2 id="judul-terkait" class="font-display text-[32px] leading-tight">Kamu mungkin suka</h2>
                <div class="grid grid-cols-2 gap-x-4 gap-y-9 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-slot:scripts>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('productPage', (d) => ({
                    ...d,
                    aromaId: d.initial.aroma,
                    variantId: d.initial.variant,
                    qty: 1,
                    active: 'aroma',
                    tab: 0,
                    q: '',
                    init() {
                        if (! this.aroma.image) this.active = 0;
                    },
                    get aroma() { return this.aromas.find((a) => a.id === this.aromaId); },
                    get variant() { return this.variants.find((v) => v.id === this.variantId); },
                    get title() { return this.aroma.name + (this.variant.size ? ' · ' + this.variant.size : ''); },
                    get main() {
                        if (this.active === 'aroma' && this.aroma.image) return this.aroma.image;
                        return this.images[this.active === 'aroma' ? 0 : this.active] ?? this.aroma.image;
                    },
                    get mainAlt() { return this.active === 'aroma' ? this.productName + ' aroma ' + this.aroma.name : this.productName; },
                    get stockText() {
                        const s = this.variant.stock;
                        if (s === 0) return 'Stok habis';
                        return (s <= this.lowStock ? 'Stok menipis · ' : 'Tersedia · ') + s + ' botol';
                    },
                    get hasNotes() { return Object.values(this.aroma.notes).some(Boolean); },
                    get noteLayers() {
                        const n = this.aroma.notes;
                        const layers = [['Top notes', n.top], ['Middle notes', n.middle], ['Base notes', n.base]]
                            .filter(([, v]) => v).map(([label, value]) => ({ label, value, wide: false }));
                        if (n.general) layers.push({ label: 'Notes', value: n.general, wide: true });
                        return layers;
                    },
                    get maxQty() { const v = this.variant; return Math.max(1, Math.min(v.stock, v.limit ?? v.stock)); },
                    get subtotal() { return this.variant.price * this.qty; },
                    get giftPct() { return this.gift ? Math.min(100, Math.round(this.subtotal / this.gift * 100)) : 0; },
                    get giftText() {
                        if (! this.gift) return '';
                        return this.subtotal >= this.gift
                            ? 'Pesanan ini dapat free decant otomatis.'
                            : 'Tambah ' + this.rp(this.gift - this.subtotal) + ' lagi untuk dapat free decant.';
                    },
                    get waLink() {
                        const text = 'Halo Angelia Perfume, apakah ' + this.productName + ' aroma ' + this.aroma.name
                            + (this.variant.size ? ' ukuran ' + this.variant.size : '') + ' tersedia lagi?';
                        return this.whatsapp + '?text=' + encodeURIComponent(text);
                    },
                    rp(n) { return 'Rp' + n.toLocaleString('id-ID'); },
                    matches(name) { return this.q === '' || name.includes(this.q.toLowerCase()); },
                    pickAroma(id) {
                        const current = this.variant;
                        this.aromaId = id;
                        const options = this.variants.filter((v) => v.aroma === id);
                        const same = options.find((v) => v.size === current.size && v.note === current.note && v.stock > 0);
                        this.variantId = (same ?? options.find((v) => v.stock > 0) ?? options[0]).id;
                        this.qty = 1;
                        this.active = this.aroma.image ? 'aroma' : 0;
                        this.syncUrl();
                    },
                    pickVariant(id) {
                        this.variantId = id;
                        this.qty = 1;
                        this.syncUrl();
                    },
                    moveTab(step) {
                        this.tab = (this.tab + step + 3) % 3;
                        this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus());
                    },
                    syncUrl() {
                        const url = new URL(window.location.href);
                        url.searchParams.set('varian', this.variant.sku);
                        history.replaceState(null, '', url);
                    },
                }));
            });
        </script>
    </x-slot:scripts>
</x-layouts.shop>
