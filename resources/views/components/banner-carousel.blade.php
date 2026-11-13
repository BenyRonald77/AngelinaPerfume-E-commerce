@php
    // Banner toko dari desain Canva klien (rasio 3:1). Teks alternatif menyalin isi banner, karena jam operasional
    // dan aturan pengiriman adalah informasi, bukan hiasan.
    $banners = [
        ['file' => 'selamat-datang', 'alt' => 'Welcome to Angelia Perfume Store! Kami menyediakan tester/share in bottle parfum dari berbagai merek. Kualitas decant terbaik dan desain premium: tanpa campuran, 100% original, bergaransi.'],
        ['file' => 'jam-operasional', 'alt' => 'Jam operasional: Senin sampai Jumat 09.00–16.00 WIB; Sabtu 09.00–12.00 WIB (checkout maksimal jam 09.00).'],
        ['file' => 'info-pengiriman', 'alt' => 'Info pengiriman: pesanan Sabtu (setelah jam 09.00) dan Minggu dikirim hari Senin. Instant: checkout maksimal jam 16.00. Pesanan masuk sebelum jam 12.00 dikirim hari yang sama. Saat event tanggal kembar dan payday sale, pengemasan lebih lama.'],
    ];
@endphp

{{-- Banner bergeser (permintaan klien). Geser dengan jari memakai scroll-snap bawaan browser; berpindah sendiri tiap
     5 detik, berhenti saat disorot kursor atau difokus keyboard, dan tidak bergerak sendiri untuk pengguna "reduced motion". --}}
<section aria-roledescription="carousel" aria-label="Info toko"
         x-data="{
             aktif: 0,
             total: {{ count($banners) }},
             jeda: false,
             timer: null,
             ke(i) { this.$refs.track.scrollTo({ left: this.$refs.track.clientWidth * i, behavior: 'smooth' }) },
             mulai() {
                 if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                 clearInterval(this.timer);
                 this.timer = setInterval(() => { if (! this.jeda && ! document.hidden) this.ke((this.aktif + 1) % this.total) }, 5000);
             },
         }"
         x-init="mulai()"
         @mouseenter="jeda = true" @mouseleave="jeda = false" @focusin="jeda = true" @focusout="jeda = false"
         class="mx-auto flex max-w-7xl flex-col items-center gap-2 px-4 pt-10 sm:px-6 lg:px-12">
    <div x-ref="track" @scroll.debounce.80ms="aktif = Math.round($el.scrollLeft / $el.clientWidth)"
         class="banner-track flex w-full snap-x snap-mandatory overflow-x-auto rounded-2xl sm:rounded-3xl">
        @foreach ($banners as $i => $banner)
            <figure class="w-full shrink-0 snap-center" aria-roledescription="slide" aria-label="{{ $i + 1 }} dari {{ count($banners) }}">
                <img src="{{ asset("images/banner/{$banner['file']}-1400.webp") }}"
                     srcset="{{ asset("images/banner/{$banner['file']}-800.webp") }} 800w, {{ asset("images/banner/{$banner['file']}-1400.webp") }} 1400w, {{ asset("images/banner/{$banner['file']}-2000.webp") }} 2000w"
                     sizes="(min-width: 1280px) 1184px, calc(100vw - 32px)"
                     width="2000" height="667" loading="lazy" decoding="async" alt="{{ $banner['alt'] }}"
                     class="block aspect-[3/1] w-full object-cover">
            </figure>
        @endforeach
    </div>

    <div class="flex items-center">
        @foreach ($banners as $i => $banner)
            <button type="button" @click="ke({{ $i }}); mulai()" :aria-current="aktif === {{ $i }} ? 'true' : 'false'"
                    aria-label="Tampilkan banner {{ $i + 1 }}" class="group flex h-11 w-9 items-center justify-center">
                <span :class="aktif === {{ $i }} ? 'w-8 bg-dusty-pink' : 'w-2 bg-ink/20 group-hover:bg-ink/40'"
                      class="block h-2 rounded-full transition-all duration-300"></span>
            </button>
        @endforeach
    </div>
</section>
