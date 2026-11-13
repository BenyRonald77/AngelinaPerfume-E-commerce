<x-layouts.shop title="Merek" description="Semua merek parfum di Angelia Perfume: brand lokal Indonesia, parfum Timur Tengah, dan lainnya.">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4 pb-20 pt-10 sm:px-6 lg:px-12">
        <header class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="font-display text-[34px] leading-tight sm:text-[44px]">Merek</h1>
                <p class="text-[17px] text-ink-soft">{{ $total }} merek, dikelompokkan berdasarkan asalnya.</p>
            </div>
            {{-- Lompat ke kelompok --}}
            <nav aria-label="Kelompok merek" class="flex flex-wrap gap-2">
                @foreach ($groups as $group)
                    <a href="#{{ $group['origin']->value }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-full border border-dusty-pink bg-white px-[18px] text-[15px] font-medium transition-colors hover:bg-blush/40">
                        {{ $group['origin']->label() }} <span class="text-ink-soft">{{ $group['brands']->count() }}</span>
                    </a>
                @endforeach
            </nav>
        </header>

        @foreach ($groups as $group)
            <section id="{{ $group['origin']->value }}" aria-labelledby="judul-{{ $group['origin']->value }}" class="flex scroll-mt-6 flex-col gap-5">
                <div class="flex flex-col gap-0.5 border-b border-dusty-pink/40 pb-3">
                    <h2 id="judul-{{ $group['origin']->value }}" class="font-display text-[26px] leading-tight sm:text-[30px]">{{ $group['origin']->label() }}</h2>
                    <p class="text-[15px] text-ink-soft">{{ $group['origin']->description() }}</p>
                </div>

                @if ($group['brands']->isEmpty())
                    <p class="card-dashed p-6 text-[15px]">Segera hadir. Belum ada merek {{ strtolower($group['origin']->label()) }} di katalog.</p>
                @else
                    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                        @foreach ($group['brands'] as $brand)
                            <li>
                                <a href="{{ $brand['href'] }}" class="group flex h-full flex-col gap-2.5 rounded-2xl bg-white p-2.5 transition-shadow hover:shadow-md">
                                    <span class="block aspect-square overflow-hidden rounded-xl bg-cream">
                                        @if ($brand['logo'])
                                            <img src="{{ $brand['logo'] }}" alt="" width="400" height="400" loading="lazy" decoding="async" class="h-full w-full object-contain p-3">
                                        @elseif ($brand['photo'])
                                            <img src="{{ $brand['photo'] }}" alt="" width="400" height="400" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="flex flex-col px-1 pb-1">
                                        <span class="font-semibold leading-snug group-hover:underline">{{ $brand['name'] }}</span>
                                        <span class="text-sm text-ink-soft">{{ $brand['products'] }} produk</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.shop>
