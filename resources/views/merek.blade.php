<x-layouts.shop title="Merek" description="Semua merek parfum original di Angelia Perfume, decant dan full size.">
    {{-- Filter huruf A-Z di sisi browser; ?huruf=A bisa dibagikan dan dibuka langsung --}}
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 pb-20 pt-10 sm:px-6 lg:px-12"
         x-data="{
             huruf: '',
             jumlah: @js($letters),
             init() {
                 const h = new URLSearchParams(location.search).get('huruf')?.toUpperCase() ?? '';
                 if (this.jumlah[h]) this.huruf = h;
             },
             pilih(h) {
                 this.huruf = h;
                 const url = new URL(location.href);
                 h ? url.searchParams.set('huruf', h) : url.searchParams.delete('huruf');
                 history.replaceState(null, '', url);
                 // Bila bilah huruf sedang menempel di atas, kembali ke awal daftar
                 if (this.$refs.bar.getBoundingClientRect().top <= 1) this.$refs.bar.scrollIntoView();
             },
         }">
        <header class="flex flex-col gap-1">
            <h1 class="font-display text-[34px] leading-tight sm:text-[44px]">Merek</h1>
            <p class="text-[17px] text-ink-soft">{{ $brands->count() }} merek parfum original, dari A sampai Z.</p>
        </header>

        <div x-ref="bar" class="sticky top-0 z-20 -mx-4 flex flex-col gap-2 border-b border-dusty-pink/30 bg-cream px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-12 lg:px-12">
            <nav aria-label="Saring merek berdasarkan huruf awal" class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul class="flex w-max gap-1.5 sm:w-auto sm:flex-wrap">
                    <li>
                        <button type="button" @click="pilih('')" :aria-pressed="(huruf === '').toString()" aria-pressed="true"
                                class="inline-flex h-11 items-center rounded-full border border-dusty-pink px-4 text-[15px] font-semibold transition-colors hover:bg-blush/40"
                                :class="huruf === '' ? 'bg-blush' : 'bg-white'">Semua</button>
                    </li>
                    @foreach ($letters as $letter => $count)
                        <li>
                            @if ($count)
                                <button type="button" @click="pilih(@js($letter))" :aria-pressed="(huruf === @js($letter)).toString()" aria-pressed="false"
                                        aria-label="{{ $letter === '#' ? 'Angka dan simbol' : 'Huruf '.$letter }}, {{ $count }} merek"
                                        class="inline-flex h-11 min-w-11 items-center justify-center rounded-full border border-dusty-pink bg-white px-2 text-[15px] font-semibold transition-colors hover:bg-blush/40"
                                        :class="{ '!bg-blush': huruf === @js($letter) }">{{ $letter }}</button>
                            @else
                                {{-- Belum ada merek berawalan huruf ini --}}
                                <span class="inline-flex h-11 min-w-11 items-center justify-center rounded-full px-2 text-[15px] text-ink-soft/50" aria-hidden="true">{{ $letter }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
            <p class="text-sm text-ink-soft" aria-live="polite">
                <span x-show="huruf" x-cloak x-text="`${jumlah[huruf] ?? 0} merek berawalan ${huruf === '#' ? 'angka atau simbol' : huruf}`"></span>
                <span x-show="!huruf">Semua merek</span>
            </p>
        </div>

        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-6">
            @foreach ($brands as $brand)
                <li x-show="!huruf || huruf === @js($brand['letter'])">
                    {{-- Kartu membesar saat kursor di atasnya (permintaan klien); tanpa gerak bila pengguna memilih kurangi gerakan --}}
                    <a href="{{ $brand['href'] }}" class="group relative flex h-full flex-col rounded-2xl border border-dusty-pink/30 bg-white transition duration-300 ease-out hover:z-10 hover:shadow-lg focus-visible:z-10 focus-visible:shadow-lg motion-safe:hover:scale-[1.04] motion-safe:focus-visible:scale-[1.04]">
                        <span class="relative block aspect-[3/2] shrink-0">
                            @if ($brand['logo'])
                                <img src="{{ $brand['logo'] }}" alt="Logo {{ $brand['name'] }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-contain p-5 sm:p-6">
                            @else
                                {{-- Logo belum ada: nama merek sebagai teks, bukan tiruan logo --}}
                                <span class="absolute inset-0 flex items-center justify-center p-4 text-center text-lg font-semibold leading-tight tracking-wide text-ink-soft">{{ $brand['name'] }}</span>
                            @endif
                        </span>
                        <span class="flex flex-col border-t border-dusty-pink/20 px-3.5 py-2.5">
                            <span class="font-semibold leading-snug group-hover:underline">{{ $brand['name'] }}</span>
                            <span class="text-sm text-ink-soft">{{ $brand['products'] }} produk</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.shop>
