{{-- Pop-up promo di tepi kiri (contoh SOCO dari klien): tab tegak yang membuka panel promo.
     Terbuka otomatis sekali untuk setiap isi promo; setelah ditutup, ingatan disimpan di browser pengunjung.
     Isi diatur admin di Pengaturan toko → Pop-up promo. --}}
@php
    use App\Models\Setting;

    $promo = collect(Setting::PROMO_DEFAULTS)->mapWithKeys(fn ($d, $key) => [str_replace('promo_', '', $key) => (string) Setting::get($key)]);
    $tampil = $promo['aktif'] === '1' && trim($promo['judul']) !== '';
    // Kunci ingatan berubah bila isi promo berubah, jadi promo baru terbuka lagi sekali
    $kunci = 'promo-ditutup-'.substr(md5($promo['judul'].$promo['teks']), 0, 10);
@endphp

@if ($tampil)
    <div x-data="{
             buka: false,
             init() {
                 let pernah = false;
                 try { pernah = localStorage.getItem(@js($kunci)) === '1'; } catch (e) {}
                 if (! pernah) setTimeout(() => this.buka = true, 1200);
             },
             tutup() {
                 this.buka = false;
                 try { localStorage.setItem(@js($kunci), '1'); } catch (e) {}
             },
         }"
         @keydown.escape.window="buka && tutup()"
         class="fixed left-0 top-1/2 z-20 flex -translate-y-1/2 items-stretch print:hidden">
        {{-- Tab tegak: membuka atau menutup panel --}}
        <button type="button" @click="buka ? tutup() : (buka = true)" :aria-expanded="buka.toString()" aria-expanded="false" aria-controls="promo-panel"
                class="flex w-9 flex-col items-center justify-between gap-2 rounded-r-2xl bg-dusty-pink py-3 text-ink transition-colors hover:bg-blush sm:w-10">
            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform" :class="{ 'rotate-180': ! buka }"><path d="m15 6-6 6 6 6"/></svg>
            <span class="rotate-180 whitespace-nowrap text-sm font-semibold tracking-wide [writing-mode:vertical-rl]">{{ $promo['label'] }}</span>
        </button>

        {{-- Panel melayang di atas isi halaman, jadi diberi bayangan --}}
        <section id="promo-panel" x-show="buka" x-cloak aria-labelledby="promo-judul"
                 x-transition:enter="motion-safe:transition motion-safe:duration-300 ease-out" x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="motion-safe:transition motion-safe:duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="relative -ml-px flex w-[min(320px,calc(100vw-4rem))] flex-col gap-3 rounded-r-2xl border border-l-0 border-dusty-pink/50 bg-petal p-5 pr-12 shadow-xl">
            <button type="button" @click="tutup()" aria-label="Tutup promo" class="absolute right-1 top-1 flex h-11 w-11 items-center justify-center rounded-full transition-colors hover:bg-white/70">
                <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
            <h2 id="promo-judul" class="font-display text-[22px] leading-tight">{{ $promo['judul'] }}</h2>
            @if (trim($promo['teks']) !== '')
                <p class="text-[15px] leading-relaxed text-ink-soft">{{ $promo['teks'] }}</p>
            @endif
            @if (trim($promo['tombol']) !== '' && str_starts_with($promo['tautan'], '/') && ! str_starts_with($promo['tautan'], '//'))
                <a href="{{ url($promo['tautan']) }}" class="btn-primary mt-1 self-start !min-h-[44px] !px-5 text-[15px]">{{ $promo['tombol'] }}</a>
            @endif
            @if (trim($promo['catatan']) !== '')
                <p class="text-xs text-ink-soft">{{ $promo['catatan'] }}</p>
            @endif
        </section>
    </div>
@endif
