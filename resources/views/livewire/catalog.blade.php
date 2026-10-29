@php
    use App\Livewire\Catalog;

    $filterButton = 'inline-flex min-h-[44px] items-center gap-2 rounded-full border border-dusty-pink bg-white px-[18px] text-[15px] font-medium transition-colors hover:bg-blush/40';
    $panel = 'fixed inset-x-4 bottom-4 z-30 max-h-[70vh] overflow-y-auto rounded-3xl border border-dusty-pink/60 bg-white p-5 shadow-xl sm:absolute sm:inset-x-auto sm:bottom-auto sm:left-0 sm:top-full sm:mt-2 sm:w-80';
    $option = 'flex min-h-[44px] items-center gap-3 rounded-xl px-2 text-[15px] hover:bg-cream';
    $jenisLabel = $this->categories->firstWhere('slug', $jenis)?->name;
    $sizeLabels = $this->sizes->filter(fn ($s) => in_array((float) $s->ml, array_map('floatval', $ukuran), true))->pluck('label');
@endphp

<div class="flex flex-col gap-7">
    {{-- Pencarian, filter, urutan --}}
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-0 basis-full flex-col gap-1.5 sm:max-w-sm sm:flex-1 sm:basis-auto">
                <label for="cari-katalog-{{ $limit }}" class="text-sm font-semibold">Cari parfum, merek, atau aroma</label>
                <input id="cari-katalog-{{ $limit }}" type="search" wire:model.live.debounce.400ms="search"
                       placeholder="mis. vanilla, MYKONOS, Topaz"
                       class="h-12 rounded-full border-dusty-pink bg-white px-5 text-base placeholder:text-ink-soft focus:border-ink focus:ring-0">
            </div>
            {{-- Menu urutan buatan sendiri: daftar <select> bawaan tidak bisa diberi gaya toko --}}
            <div class="relative flex flex-col gap-1.5"
                 x-data="{
                     open: false,
                     value: @js($urut),
                     active: 0,
                     options: @js(collect(Catalog::SORTS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()),
                     get label() { return this.options.find((o) => o.key === this.value)?.label },
                     show() {
                         this.open = true;
                         this.active = Math.max(0, this.options.findIndex((o) => o.key === this.value));
                         this.$nextTick(() => this.$refs.list.focus());
                     },
                     close(refocus = true) { this.open = false; if (refocus) this.$refs.button.focus(); },
                     choose(i) {
                         this.value = this.options[i].key;
                         this.$wire.set('urut', this.value);
                         this.close();
                     },
                 }"
                 @click.outside="if (open) close(false)">
                <span id="label-urut-{{ $limit }}" class="text-sm font-semibold">Urutkan</span>
                <button type="button" x-ref="button" id="tombol-urut-{{ $limit }}"
                        @click="open ? close() : show()" @keydown.arrow-down.prevent="show()" @keydown.arrow-up.prevent="show()"
                        aria-haspopup="listbox" :aria-expanded="open.toString()" aria-expanded="false"
                        aria-labelledby="label-urut-{{ $limit }} tombol-urut-{{ $limit }}"
                        class="flex h-12 min-w-[210px] items-center justify-between gap-3 rounded-full border border-dusty-pink bg-white pl-5 pr-4 text-base transition-colors hover:border-ink aria-expanded:border-ink">
                    <span x-text="label">{{ Catalog::SORTS[$urut] ?? Catalog::SORTS['terlaris'] }}</span>
                    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform" :class="open && 'rotate-180'"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <ul x-ref="list" x-show="open" x-cloak role="listbox" tabindex="-1"
                    aria-labelledby="label-urut-{{ $limit }}" :aria-activedescendant="'urut-{{ $limit }}-' + active"
                    @keydown.arrow-down.prevent="active = (active + 1) % options.length"
                    @keydown.arrow-up.prevent="active = (active - 1 + options.length) % options.length"
                    @keydown.home.prevent="active = 0" @keydown.end.prevent="active = options.length - 1"
                    @keydown.enter.prevent="choose(active)" @keydown.space.prevent="choose(active)"
                    @keydown.escape.prevent.stop="close()" @keydown.tab="close(false)"
                    class="absolute left-0 top-full z-30 mt-2 w-full min-w-[230px] rounded-2xl border border-dusty-pink/60 bg-white p-1.5 shadow-xl focus:outline-none">
                    <template x-for="(option, i) in options" :key="option.key">
                        <li :id="'urut-{{ $limit }}-' + i" role="option" :aria-selected="(option.key === value).toString()"
                            @click="choose(i)" @mousemove="active = i"
                            class="flex min-h-[44px] cursor-pointer items-center justify-between gap-3 rounded-xl px-3.5 text-[15px] aria-selected:font-semibold"
                            :class="active === i ? 'bg-blush/50' : ''">
                            <span x-text="option.label"></span>
                            <svg x-show="option.key === value" aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div x-data="{ open: null }" @keydown.escape="open = null" @click.outside="open = null" class="flex flex-wrap items-center gap-2.5">
            {{-- Jenis --}}
            <div class="relative">
                <button type="button" class="{{ $filterButton }}" @click="open = open === 'jenis' ? null : 'jenis'"
                        :aria-expanded="(open === 'jenis').toString()" aria-expanded="false" aria-controls="f-jenis-{{ $limit }}">
                    Jenis @if ($jenisLabel)<span class="font-semibold">· {{ $jenisLabel }}</span>@endif
                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="f-jenis-{{ $limit }}" x-show="open === 'jenis'" x-cloak class="{{ $panel }}">
                    <fieldset>
                        <legend class="pb-2 font-semibold">Jenis</legend>
                        <label class="{{ $option }}"><input type="radio" wire:model.live="jenis" value="" class="h-5 w-5 border-ink-soft text-ink focus:ring-ink"> Semua</label>
                        @foreach ($this->categories as $category)
                            <label class="{{ $option }}"><input type="radio" wire:model.live="jenis" value="{{ $category->slug }}" class="h-5 w-5 border-ink-soft text-ink focus:ring-ink"> {{ $category->name }}</label>
                        @endforeach
                    </fieldset>
                    <button type="button" class="btn-primary mt-3 w-full sm:hidden" @click="open = null">Selesai</button>
                </div>
            </div>

            {{-- Merek --}}
            <div class="relative">
                <button type="button" class="{{ $filterButton }}" @click="open = open === 'merek' ? null : 'merek'"
                        :aria-expanded="(open === 'merek').toString()" aria-expanded="false" aria-controls="f-merek-{{ $limit }}">
                    Merek @if (count($merek))<span class="font-semibold">· {{ count($merek) }}</span>@endif
                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="f-merek-{{ $limit }}" x-show="open === 'merek'" x-cloak class="{{ $panel }}" x-data="{ q: '' }">
                    <fieldset>
                        <legend class="pb-2 font-semibold">Merek</legend>
                        <label for="cari-merek-{{ $limit }}" class="sr-only">Cari merek</label>
                        <input id="cari-merek-{{ $limit }}" type="search" x-model="q" placeholder="Cari merek"
                               class="mb-2 h-11 w-full rounded-full border-dusty-pink px-4 text-base focus:border-ink focus:ring-0">
                        <div class="max-h-72 overflow-y-auto sm:max-h-64">
                            @foreach ($this->brands as $brand)
                                <label class="{{ $option }}" x-show="q === '' || @js(mb_strtolower($brand->name)).includes(q.toLowerCase())">
                                    <input type="checkbox" wire:model.live="merek" value="{{ $brand->slug }}" class="h-5 w-5 rounded border-ink-soft text-ink focus:ring-ink">
                                    {{ $brand->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <button type="button" class="btn-primary mt-3 w-full sm:hidden" @click="open = null">Selesai</button>
                </div>
            </div>

            {{-- Ukuran --}}
            <div class="relative">
                <button type="button" class="{{ $filterButton }}" @click="open = open === 'ukuran' ? null : 'ukuran'"
                        :aria-expanded="(open === 'ukuran').toString()" aria-expanded="false" aria-controls="f-ukuran-{{ $limit }}">
                    Ukuran @if ($sizeLabels->isNotEmpty())<span class="font-semibold">· {{ $sizeLabels->implode(', ') }}</span>@endif
                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="f-ukuran-{{ $limit }}" x-show="open === 'ukuran'" x-cloak class="{{ $panel }}">
                    <fieldset>
                        <legend class="pb-2 font-semibold">Ukuran</legend>
                        @foreach ($this->sizes as $size)
                            <label class="{{ $option }}">
                                <input type="checkbox" wire:model.live="ukuran" value="{{ (string) ($size->ml + 0) }}" class="h-5 w-5 rounded border-ink-soft text-ink focus:ring-ink">
                                <span>{{ $size->label }} <span class="text-ink-soft">· {{ $size->bottle_type->label() }}</span></span>
                            </label>
                        @endforeach
                    </fieldset>
                    <button type="button" class="btn-primary mt-3 w-full sm:hidden" @click="open = null">Selesai</button>
                </div>
            </div>

            {{-- Harga --}}
            <div class="relative">
                <button type="button" class="{{ $filterButton }}" @click="open = open === 'harga' ? null : 'harga'"
                        :aria-expanded="(open === 'harga').toString()" aria-expanded="false" aria-controls="f-harga-{{ $limit }}">
                    Harga @if ($harga && isset(Catalog::PRICE_RANGES[$harga]))<span class="font-semibold">· {{ Catalog::PRICE_RANGES[$harga]['label'] }}</span>@endif
                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="f-harga-{{ $limit }}" x-show="open === 'harga'" x-cloak class="{{ $panel }}">
                    <fieldset>
                        <legend class="pb-2 font-semibold">Harga per botol</legend>
                        <label class="{{ $option }}"><input type="radio" wire:model.live="harga" value="" class="h-5 w-5 border-ink-soft text-ink focus:ring-ink"> Semua harga</label>
                        @foreach (Catalog::PRICE_RANGES as $value => $range)
                            <label class="{{ $option }}"><input type="radio" wire:model.live="harga" value="{{ $value }}" class="h-5 w-5 border-ink-soft text-ink focus:ring-ink"> {{ $range['label'] }}</label>
                        @endforeach
                    </fieldset>
                    <button type="button" class="btn-primary mt-3 w-full sm:hidden" @click="open = null">Selesai</button>
                </div>
            </div>

            <label class="inline-flex min-h-[44px] items-center gap-2.5 px-2 text-[15px] font-medium">
                <input type="checkbox" wire:model.live="tersedia" class="h-5 w-5 rounded border-ink-soft text-ink focus:ring-ink">
                Hanya yang tersedia
            </label>

            @if ($this->activeFilterCount() > 0)
                <button type="button" wire:click="resetFilters" class="inline-flex min-h-[44px] items-center px-2 text-[15px] font-semibold underline underline-offset-4 hover:text-ink-soft">
                    Hapus filter
                </button>
            @endif
        </div>
    </div>

    {{-- Hasil --}}
    <div class="flex items-center justify-between gap-4">
        <p class="text-[15px] text-ink-soft" aria-live="polite">
            <span wire:loading.remove wire:target="search, jenis, merek, ukuran, harga, tersedia, urut, resetFilters, gotoPage, nextPage, previousPage">{{ number_format($total, 0, ',', '.') }} produk</span>
            <span wire:loading wire:target="search, jenis, merek, ukuran, harga, tersedia, urut, resetFilters, gotoPage, nextPage, previousPage">Memuat produk…</span>
        </p>
    </div>

    @if ($total === 0)
        <div class="card-dashed flex flex-col items-start gap-4 p-6 sm:p-8">
            <p class="font-display text-2xl">Belum ada produk yang cocok.</p>
            <p class="max-w-prose text-[17px]">
                @if (! $tersedia && $this->activeFilterCount() === 0)
                    Katalog sedang kosong.
                @else
                    Coba hapus beberapa filter atau cari dengan kata lain.
                    @if ($tersedia) Produk yang stoknya habis juga tersembunyi; matikan "Hanya yang tersedia" untuk melihatnya. @endif
                @endif
            </p>
            <button type="button" wire:click="resetFilters" class="btn-outline">Hapus semua filter</button>
        </div>
    @else
        <div wire:loading.class="opacity-50" wire:target="search, jenis, merek, ukuran, harga, tersedia, urut, resetFilters, gotoPage, nextPage, previousPage"
             class="grid grid-cols-2 gap-x-4 gap-y-9 transition-opacity sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 lg:gap-y-10">
            @foreach ($products as $product)
                <x-product-card :product="$product" :bestseller="$bestsellerIds->contains($product->id)" wire:key="p-{{ $product->id }}" />
            @endforeach
        </div>

        @if ($limit > 0)
            @if ($total > $products->count() || $allVisible > $total)
                <a href="{{ route('produk.index', array_filter(['cari' => $search ?: null, 'jenis' => $jenis ?: null, 'merek' => $merek ?: null, 'ukuran' => $ukuran ?: null, 'harga' => $harga ?: null, 'tersedia' => $tersedia ? null : 0, 'urut' => $urut !== 'terlaris' ? $urut : null])) }}"
                   class="btn-outline self-center">
                    {{ $this->activeFilterCount() > 0 ? 'Lihat semua '.number_format($total, 0, ',', '.').' produk yang cocok' : 'Lihat semua '.number_format($allVisible, 0, ',', '.').' produk' }}
                </a>
            @endif
        @else
            {{ $products->links('livewire.pagination') }}
        @endif
    @endif
</div>
