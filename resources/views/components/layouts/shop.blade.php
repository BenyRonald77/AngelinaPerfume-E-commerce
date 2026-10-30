@props(['title' => null, 'description' => null, 'overlayHeader' => false])

@php
    use App\Support\Promo;
    use App\Support\WhatsApp;

    $jenis = request()->routeIs('produk.index') ? request('jenis') : null;
    $nav = [
        ['label' => 'Beranda', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Katalog', 'href' => route('produk.index'), 'active' => request()->routeIs('produk.index') && ! $jenis],
        ['label' => 'Decant', 'href' => route('produk.index', ['jenis' => 'decant']), 'active' => $jenis === 'decant'],
        ['label' => 'Full Size', 'href' => route('produk.index', ['jenis' => 'full-size']), 'active' => $jenis === 'full-size'],
    ];
    // Di atas hero bermawar (Beranda) pil diberi latar putih tipis supaya tetap terbaca di atas kelopak
    $pill = $overlayHeader ? 'border border-ink/25 bg-white/40 hover:bg-white/70' : 'border border-ink/25 hover:bg-white/60';
    $pillActive = 'bg-white';
    $cartCount = app(\App\Services\CartService::class)->count();
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · Angelia Perfume' : 'Angelia Perfume · Decant dan full size parfum original' }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Pacifico&family=Shrikhand&display=swap">
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col">
    <a href="#isi" class="sr-only z-50 rounded-full bg-ink px-5 py-3 font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Langsung ke isi</a>

    {{-- Mode overlay (Beranda): header transparan menempel di atas hero pink --}}
    <div @class(['absolute inset-x-0 top-0 z-30' => $overlayHeader, 'relative z-30' => ! $overlayHeader])>
        <header @class(['relative text-ink' => $overlayHeader, 'bg-blush text-ink' => ! $overlayHeader])
                x-data="{ menu: false }" @keydown.escape.window="menu = false">
            {{-- Header selebar layar: logo di tepi kiri, cari dan akun di tepi kanan --}}
            <div class="w-full px-4 sm:px-6 lg:px-10 xl:px-14">
                {{-- Tiga kolom di layar lebar: menu tepat di tengah layar, tidak terdorong oleh lebar kotak cari --}}
                <div class="flex items-center justify-between gap-3 py-4 xl:grid xl:grid-cols-[1fr_auto_1fr]">
                    <a href="{{ route('home') }}" class="font-logo text-[26px] leading-tight sm:text-[30px] xl:justify-self-start">Angelia Perfume</a>

                    <nav aria-label="Menu utama" class="hidden flex-wrap gap-2 md:flex">
                        @foreach ($nav as $item)
                            <a href="{{ $item['href'] }}"
                               @if ($item['active']) aria-current="page" @endif
                               @class([
                                   // pt-0.5: huruf League Spartan duduk sedikit tinggi, digeser 1px supaya tepat di tengah pil
                                   'inline-flex min-h-[44px] items-center rounded-full px-[18px] pt-0.5 text-[15px] font-medium transition-colors',
                                   $pillActive => $item['active'],
                                   $pill => ! $item['active'],
                               ])>{{ $item['label'] }}</a>
                        @endforeach
                    </nav>

                    <div class="flex items-center gap-2 xl:justify-self-end">
                        <form action="{{ route('produk.index') }}" method="get" role="search" class="hidden lg:block">
                            <label for="cari-header" class="sr-only">Cari parfum, merek, atau aroma</label>
                            <div class="flex h-11 items-center rounded-full border border-ink/25 bg-white/70 pl-4 pr-1 focus-within:bg-white">
                                <input id="cari-header" name="cari" type="search" value="{{ request()->routeIs('produk.index') ? request('cari') : '' }}"
                                       placeholder="Cari parfum atau merek"
                                       class="w-44 border-0 bg-transparent p-0 text-[15px] placeholder:text-ink-soft focus:ring-0 xl:w-56">
                                <button type="submit" aria-label="Cari" class="flex h-9 w-9 items-center justify-center rounded-full hover:bg-blush">
                                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                                </button>
                            </div>
                        </form>

                        <a href="{{ auth()->check() ? route('akun') : route('login') }}"
                           aria-label="{{ auth()->check() ? 'Akun saya' : 'Masuk atau daftar' }}"
                           @if (request()->routeIs('akun*')) aria-current="page" @endif
                           class="flex h-11 w-11 items-center justify-center rounded-full transition-colors {{ request()->routeIs('akun*') ? $pillActive : $pill }}">
                            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
                        </a>

                        {{-- Jumlah barang diperbarui halaman keranjang lewat event "cart-count" --}}
                        <a href="{{ route('keranjang') }}" x-data="{ count: {{ $cartCount }} }" @cart-count.window="count = $event.detail.count"
                           :aria-label="count ? 'Keranjang, ' + count + ' barang' : 'Keranjang kosong'"
                           aria-label="{{ $cartCount ? "Keranjang, {$cartCount} barang" : 'Keranjang kosong' }}"
                           @if (request()->routeIs('keranjang', 'checkout')) aria-current="page" @endif
                           class="relative flex h-11 w-11 items-center justify-center rounded-full transition-colors {{ request()->routeIs('keranjang', 'checkout') ? $pillActive : $pill }}">
                            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l-1.2 11.1a1 1 0 0 1-1 .9H7.2a1 1 0 0 1-1-.9z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                            <span x-show="count > 0" x-text="count > 99 ? '99+' : count" @if (! $cartCount) style="display: none" @endif
                                  class="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-ink px-1 pt-px text-xs font-bold text-white ring-2 ring-blush">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                        </a>

                        <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full transition-colors md:hidden {{ $pill }}"
                                @click="menu = !menu" :aria-expanded="menu.toString()" aria-expanded="false" aria-controls="menu-hp"
                                aria-label="Menu">
                            <svg x-show="!menu" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                            <svg x-show="menu" x-cloak aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Menu HP: navigasi dan pencarian --}}
                <div id="menu-hp" x-show="menu" x-cloak @class(['mb-4 flex flex-col gap-3 md:hidden', 'rounded-3xl bg-cream p-4 shadow-lg' => $overlayHeader, 'pb-5' => ! $overlayHeader])>
                    <form action="{{ route('produk.index') }}" method="get" role="search">
                        <label for="cari-hp" class="sr-only">Cari parfum, merek, atau aroma</label>
                        <div class="flex h-12 items-center rounded-full bg-white pl-4 pr-1 text-ink">
                            <input id="cari-hp" name="cari" type="search" placeholder="Cari parfum, merek, atau aroma"
                                   class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base placeholder:text-ink-soft focus:ring-0">
                            <button type="submit" aria-label="Cari" class="flex h-10 w-10 items-center justify-center rounded-full hover:bg-blush">
                                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            </button>
                        </div>
                    </form>
                    <nav aria-label="Menu utama (HP)" class="grid grid-cols-2 gap-2">
                        @foreach ($nav as $item)
                            <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                               @class(['flex min-h-[48px] items-center justify-center rounded-full text-base font-medium', $pillActive => $item['active'], $pill => ! $item['active']])>{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                </div>
            </div>
        </header>
    </div>

    <main id="isi" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-blush text-ink">
        <div class="mx-auto flex max-w-7xl flex-col gap-12 px-4 pb-8 pt-14 sm:px-6 lg:px-12">
            <div class="flex flex-wrap justify-between gap-10">
                <div class="flex max-w-sm flex-col gap-3">
                    <span class="font-logo text-[30px] leading-snug">Angelia Perfume</span>
                    <p class="text-[15px] text-ink-soft">Decant dan full size parfum original. Cairan asli tanpa tambahan alkohol, dipindahkan ke botol kaca dan disegel sebelum dikirim.</p>
                </div>
                <div class="flex flex-wrap gap-x-14 gap-y-8 text-[15px]">
                    <div class="flex flex-col gap-1">
                        <span class="pb-1 font-bold">Belanja</span>
                        <a class="py-1.5 hover:underline" href="{{ route('produk.index', ['jenis' => 'decant']) }}">Decant</a>
                        <a class="py-1.5 hover:underline" href="{{ route('produk.index', ['ukuran' => ['15']]) }}">Travel size</a>
                        <a class="py-1.5 hover:underline" href="{{ route('produk.index', ['jenis' => 'full-size']) }}">Full size</a>
                        <a class="py-1.5 hover:underline" href="{{ route('produk.index', ['jenis' => 'aksesori']) }}">Aksesori</a>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="pb-1 font-bold">Kontak</span>
                        @if ($wa = WhatsApp::link('Halo Angelia Perfume, saya mau tanya.'))
                            <a class="py-1.5 hover:underline" href="{{ $wa }}" rel="noopener" target="_blank">WhatsApp {{ WhatsApp::display() }}</a>
                        @else
                            <span class="py-1.5">WhatsApp {{ WhatsApp::display() }}</span>
                        @endif
                        <a class="py-1.5 hover:underline" href="{{ config('toko.shopee_url') }}" rel="noopener" target="_blank">Shopee: angeliaperfume</a>
                    </div>
                </div>
            </div>
            <p class="border-t border-ink/15 pt-6 text-sm text-ink-soft">© {{ now()->year }} Angelia Perfume</p>
        </div>
    </footer>

    {{-- Pesan singkat: hasil aksi (flash dari server, event "toko-pesan" dari Livewire) dan galat permintaan Livewire.
         Satu-satunya elemen berbayang di halaman: melayang di atas isi. --}}
    @php($flash = session('flash_error') ?? session('flash'))
    <div x-data="{ pesan: @js($flash ?? ''), link: @js(session('flash_cart') && request()->routeIs('produk.*') ? route('keranjang') : ''),
                   tampil(p, l = '') { this.pesan = p; this.link = l; clearTimeout(this.t); this.t = setTimeout(() => this.pesan = '', 8000) } }"
         x-init="pesan && tampil(pesan, link)"
         @toko-error.window="tampil($event.detail)" @toko-pesan.window="tampil($event.detail.pesan ?? $event.detail)"
         class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4">
        <div x-show="pesan" @if (! $flash) x-cloak @endif role="status" aria-live="polite"
             class="pointer-events-auto flex max-w-lg items-center gap-4 rounded-2xl bg-ink py-2 pl-5 pr-2 text-[15px] font-medium text-white shadow-lg">
            <p class="py-1.5" x-text="pesan">{{ $flash }}</p>
            <a x-show="link" :href="link" class="inline-flex min-h-[44px] shrink-0 items-center rounded-full bg-cream px-4 font-semibold text-ink">Lihat keranjang</a>
            <button type="button" @click="pesan = ''" aria-label="Tutup pesan" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full hover:bg-white/10">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
    </div>

    {{-- Skrip halaman (Alpine.data) harus terdaftar sebelum Livewire menyalakan Alpine --}}
    {{ $scripts ?? '' }}
    @livewireScripts
    <script>
        document.addEventListener('livewire:init', () => {
            const kabari = (pesan) => window.dispatchEvent(new CustomEvent('toko-error', { detail: pesan }));
            Livewire.interceptRequest(({ onError, onFailure }) => {
                onError(({ preventDefault }) => {
                    kabari('Permintaan gagal. Muat ulang halaman lalu coba lagi.');
                    @unless (config('app.debug')) preventDefault(); @endunless
                });
                onFailure(() => kabari('Koneksi terputus. Periksa internet lalu coba lagi.'));
            });
        });
    </script>
</body>
</html>
