@php
    use App\Support\Rupiah;

    // Empat jaminan toko, sama dengan yang tertulis di foto produk Shopee
    $trust = [
        ['title' => '100% original', 'text' => 'Reseller resmi MYKONOS dan brand lokal pilihan.', 'icon' => '<path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z"/><path d="m9 12 2 2 4-4"/>'],
        ['title' => 'Botol kaca, disegel', 'text' => 'Suntikan berbeda untuk setiap parfum; botol disegel sebelum dikirim.', 'icon' => '<path d="M10 3h4v3h-4z"/><path d="M8 6h8v3l1 2v9a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-9l1-2z"/>'],
        ['title' => 'Takaran akurat', 'text' => 'Cairan asli dipindahkan dengan takaran akurat, tanpa tambahan alkohol.', 'icon' => '<path d="M9 3h6"/><path d="M10 3v6L5 19a1.5 1.5 0 0 0 1.3 2h11.4a1.5 1.5 0 0 0 1.3-2l-5-10V3"/><path d="M7.5 15h9"/>'],
        ['title' => 'Garansi 1×24 jam', 'text' => 'Komplain dengan video unboxing bila pecah, bocor, atau tidak sesuai.', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
    ];
@endphp

<x-layouts.shop overlay-header description="Decant dan full size parfum original dari {{ $brandCount }} merek. Coba aromanya mulai 0,5 ml, botol kaca disegel sebelum dikirim.">
    <x-slot:head>
        <link rel="preload" as="image" href="{{ asset('images/hero/parfum-amber-1000.webp') }}"
              imagesrcset="{{ asset('images/hero/parfum-amber-1000.webp') }} 1000w, {{ asset('images/hero/parfum-amber-1800.webp') }} 1800w"
              imagesizes="(min-width: 1024px) 68vw, 100vw">
    </x-slot:head>

    {{-- Hero foto penuh (permintaan pemilik). Foto: "Aesthetic Parfume Photoshoot", Simply Mersah di Unsplash
         (unsplash.com/photos/MLV5zTSzj98), Unsplash License: bebas dipakai komersial. Botol tanpa merek. --}}
    <section class="on-dark relative isolate flex min-h-[720px] flex-col overflow-hidden bg-[#2B1E1A] text-cream lg:min-h-[800px]">
        <img src="{{ asset('images/hero/parfum-amber-1000.webp') }}"
             srcset="{{ asset('images/hero/parfum-amber-1000.webp') }} 1000w, {{ asset('images/hero/parfum-amber-1800.webp') }} 1800w"
             sizes="(min-width: 1024px) 68vw, 100vw" width="1800" height="2025" fetchpriority="high" alt=""
             class="hero-photo absolute inset-y-0 right-0 -z-10 h-full w-full object-cover object-[55%_45%] lg:w-[72%]">
        <div aria-hidden="true" class="hero-shade absolute inset-0 -z-10"></div>
        <div aria-hidden="true" class="pointer-events-none absolute bottom-24 left-0 -z-10 select-none whitespace-nowrap font-logo text-[130px] leading-none text-cream/[0.07] sm:text-[220px] lg:bottom-20 lg:text-[300px]">Angelia</div>

        <div class="mx-auto flex w-full max-w-7xl flex-1 items-center px-4 pb-14 pt-32 sm:px-6 lg:px-12 lg:pt-36">
            <div class="flex max-w-xl flex-col items-start gap-6">
                <h1 class="font-display text-[38px] leading-[1.05] sm:text-[52px] lg:text-[60px]">Wangi favoritmu, cukup mulai <span class="whitespace-nowrap">0,5 ml.</span></h1>
                <p class="text-lg leading-relaxed text-cream/90 sm:text-[19px]">
                    Decant dan full size original dari {{ $brandCount }} merek. Coba aromanya dulu dalam botol kecil, lalu pilih botol besar yang paling kamu suka. Reseller resmi MYKONOS, di Shopee sejak 2023.
                </p>
                <div class="flex flex-wrap gap-3 pt-2">
                    {{-- Satu-satunya tombol dengan panah: jalur utama ke katalog decant --}}
                    <a href="{{ route('produk.index', ['jenis' => 'decant']) }}"
                       class="inline-flex h-[52px] items-center gap-2 rounded-full bg-cream px-7 font-semibold text-ink transition-colors hover:bg-white active:bg-blush">
                        Belanja decant
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </a>
                    @if ($travel)
                        <a href="{{ route('produk.show', $travel) }}"
                           class="inline-flex h-[52px] items-center rounded-full border border-cream/70 px-7 font-semibold text-cream transition-colors hover:bg-white/10 active:bg-white/20">Travel size MYKONOS</a>
                    @endif
                </div>
            </div>
        </div>

        @if ($bestsellers->isNotEmpty())
            @php($pages = $bestsellers->chunk(3)->values())
            {{-- Strip Terlaris seperti contoh: label + penghitung di kiri, tiga produk per halaman --}}
            <div class="strip-dark border-t border-white/20" x-data="{ page: 0, total: {{ $pages->count() }} }">
                <div class="grid lg:grid-cols-[230px_1fr]">
                    <div class="flex items-center justify-between gap-4 border-b border-white/20 px-4 py-5 sm:px-6 lg:flex-col lg:items-start lg:border-b-0 lg:border-r lg:px-8 lg:py-6">
                        <div class="flex flex-col gap-1">
                            <h2 class="font-display text-[26px] leading-none">Terlaris</h2>
                            <p class="text-[13px] text-cream/80">Paling banyak dibeli di Shopee, Juli–Oktober 2026</p>
                        </div>
                        @if ($pages->count() > 1)
                            <div class="flex items-center gap-1">
                                <button type="button" @click="page = (page - 1 + total) % total" aria-label="Produk terlaris sebelumnya"
                                        class="flex h-11 w-11 items-center justify-center rounded-full transition-colors hover:bg-white/15">
                                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg>
                                </button>
                                <span class="min-w-[52px] text-center text-sm tabular-nums" aria-live="polite">
                                    <span x-text="String(page + 1).padStart(2, '0')">01</span><span class="text-cream/60">/{{ str_pad((string) $pages->count(), 2, '0', STR_PAD_LEFT) }}</span>
                                </span>
                                <button type="button" @click="page = (page + 1) % total" aria-label="Produk terlaris berikutnya"
                                        class="flex h-11 w-11 items-center justify-center rounded-full transition-colors hover:bg-white/15">
                                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                                </button>
                            </div>
                        @endif
                    </div>

                    @foreach ($pages as $n => $chunk)
                        <ul class="grid lg:grid-cols-3" x-show="page === {{ $n }}" @if ($n > 0) style="display: none" @endif>
                            @foreach ($chunk as $product)
                                @php($price = $product->cardPrice())
                                <li class="border-b border-white/15 last:border-b-0 lg:border-b-0 lg:border-r lg:last:border-r-0">
                                    <a href="{{ route('produk.show', $product) }}" class="group flex h-full items-center gap-4 px-4 py-4 transition-colors hover:bg-white/10 sm:px-6 lg:py-6">
                                        @if ($product->cover)
                                            <img src="{{ $product->cover->url(800) }}" width="76" height="76" alt="" loading="lazy" decoding="async" class="h-[76px] w-[76px] shrink-0 rounded-xl bg-white object-cover">
                                        @endif
                                        <span class="flex min-w-0 flex-col gap-0.5">
                                            <span class="flex items-baseline gap-1.5 whitespace-nowrap">
                                                @if ($price)
                                                    @if ($price['prefix'])<span class="text-sm text-cream/80">{{ $price['prefix'] }}</span>@endif
                                                    <span class="text-xl font-bold">{{ $price['amount'] }}</span>
                                                @else
                                                    <span class="text-base font-semibold">Stok habis</span>
                                                @endif
                                            </span>
                                            <span class="text-[15px] font-semibold group-hover:underline">{{ $product->name }}</span>
                                            <span class="text-sm text-cream/80">{{ $product->cardMeta() }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @if ($collections)
        <section aria-labelledby="judul-koleksi" class="mx-auto flex max-w-7xl flex-col items-center gap-10 px-4 pb-10 pt-20 sm:px-6 lg:px-12 lg:pt-24">
            <div class="flex flex-col items-center gap-2 text-center">
                <h2 id="judul-koleksi" class="font-display text-[36px] leading-tight lg:text-5xl">Koleksi</h2>
                <p class="text-lg text-ink-soft">Pilih botol sesuai kebutuhanmu, dari coba-coba sampai koleksi.</p>
            </div>
            <ul class="grid w-full grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($collections as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="group flex flex-col items-center gap-3.5 text-center">
                            <span class="flex h-[132px] w-[132px] items-center justify-center overflow-hidden rounded-full border border-blush bg-white transition-colors group-hover:border-ink sm:h-[148px] sm:w-[148px]">
                                @if ($item['product']->cover)
                                    <img src="{{ $item['product']->cover->url(800) }}" width="148" height="148" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                @endif
                            </span>
                            <span class="flex flex-col gap-0.5">
                                <span class="text-lg font-semibold group-hover:underline">{{ $item['name'] }}</span>
                                @if ($item['sub'])
                                    <span class="text-sm text-ink-soft">{{ $item['sub'] }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section id="katalog" aria-labelledby="judul-katalog" class="mx-auto flex max-w-7xl flex-col gap-7 px-4 pb-10 pt-14 sm:px-6 lg:px-12">
        <h2 id="judul-katalog" class="font-display text-[30px] leading-tight lg:text-[40px]">Pilihan untukmu</h2>
        <livewire:catalog :limit="8" />
    </section>

    @if ($giftRules->isNotEmpty())
        @php($first = $giftRules->first())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-12">
            <div class="flex flex-wrap items-center justify-between gap-6 rounded-[32px] border border-blush bg-white p-7 sm:p-12">
                <div class="flex max-w-2xl flex-col gap-2">
                    <h2 class="font-display text-[26px] leading-tight sm:text-4xl">Belanja min. {{ Rupiah::format($first->min_subtotal) }}, dapat {{ \Illuminate\Support\Str::lower(preg_replace('/\s*\(.*\)$/', '', $first->name)) }}.</h2>
                    <p class="text-[17px]">
                        Hadiah masuk ke pesanan otomatis, tanpa perlu diklaim.
                        @foreach ($giftRules->skip(1) as $rule)
                            Belanja min. {{ Rupiah::format($rule->min_subtotal) }} dapat {{ \Illuminate\Support\Str::lower(preg_replace('/\s*\(.*\)$/', '', $rule->name)) }}.
                        @endforeach
                    </p>
                </div>
                <a href="#katalog" class="btn-primary h-[52px]">Pilih parfum</a>
            </div>
        </section>
    @endif

    <section aria-label="Jaminan toko" class="mx-auto max-w-7xl px-4 pb-24 pt-10 sm:px-6 lg:px-12">
        <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($trust as $item)
                <li class="flex flex-col gap-3">
                    <span class="flex h-[52px] w-[52px] items-center justify-center rounded-full border border-blush bg-white">
                        <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                    </span>
                    <h3 class="text-lg font-semibold">{{ $item['title'] }}</h3>
                    <p class="text-[15px] text-ink-soft">{{ $item['text'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.shop>
