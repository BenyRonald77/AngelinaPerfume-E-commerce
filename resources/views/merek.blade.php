<x-layouts.shop title="Merek" description="Semua merek parfum original di Angelia Perfume, decant dan full size.">
    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 pb-20 pt-10 sm:px-6 lg:px-12">
        <header class="flex flex-col gap-1">
            <h1 class="font-display text-[34px] leading-tight sm:text-[44px]">Merek</h1>
            <p class="text-[17px] text-ink-soft">{{ $brands->count() }} merek parfum original, dari A sampai Z.</p>
        </header>

        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-6">
            @foreach ($brands as $brand)
                <li>
                    <a href="{{ $brand['href'] }}" class="group flex h-full flex-col rounded-2xl border border-dusty-pink/30 bg-white transition-shadow hover:shadow-md focus-visible:shadow-md">
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
