{{-- Pemilih merek (contoh dari klien: menu Brands di SOCO): tab Nama Merek / Asal Merek, kotak cari, indeks huruf A-Z + #, daftar per huruf.
     mode="link": tautan ke katalog merek itu (dropdown Merek di navbar).
     mode="check": centang wire:model "merek" (filter Merek di katalog). --}}
@props(['mode' => 'link', 'id' => 'merek'])

@php
    $brands = collect(\App\Support\BrandMenu::brands());
    $byLetter = $brands->groupBy('letter');
    $letters = [...range('A', 'Z'), '#'];
    $check = $mode === 'check';
    $item = 'flex min-h-[40px] items-center gap-3 rounded-lg px-2 text-[15px] transition-colors hover:bg-cream';
@endphp

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pemilihMerek', (nama) => ({
                tab: 'nama',
                q: '',
                // { A: ['aamo', 'aamodini', ...], ... } nama huruf kecil per huruf awal
                nama,
                cocok(n) {
                    return this.q.trim() === '' || n.includes(this.q.trim().toLowerCase());
                },
                ada(h) {
                    return (this.nama[h] ?? []).some((n) => this.cocok(n));
                },
                kosong() {
                    return !Object.keys(this.nama).some((h) => this.ada(h));
                },
                lompat(h) {
                    const el = this.$refs.daftar.querySelector(`[data-huruf="${h}"]`);
                    if (!el) return;
                    const halus = !matchMedia('(prefers-reduced-motion: reduce)').matches;
                    this.$refs.daftar.scrollTo({ top: el.offsetTop, behavior: halus ? 'smooth' : 'auto' });
                },
            }));
        });
    </script>
@endonce

<div x-data="pemilihMerek(@js($byLetter->map(fn ($group) => $group->map(fn ($b) => mb_strtolower($b['name']))->values())))" class="flex flex-col gap-3 text-ink">
    <div role="tablist" aria-label="Pilih merek berdasarkan" class="grid grid-cols-2 border-b border-ink/10">
        <button type="button" role="tab" id="{{ $id }}-tab-nama" aria-controls="{{ $id }}-nama" @click="tab = 'nama'"
                :aria-selected="(tab === 'nama').toString()" aria-selected="true"
                class="-mb-px min-h-[44px] border-b-2 px-2 text-[15px] font-semibold transition-colors"
                :class="tab === 'nama' ? 'border-ink text-ink' : 'border-transparent text-ink-soft hover:text-ink'">Nama Merek</button>
        <button type="button" role="tab" id="{{ $id }}-tab-asal" aria-controls="{{ $id }}-asal" @click="tab = 'asal'"
                :aria-selected="(tab === 'asal').toString()" aria-selected="false"
                class="-mb-px min-h-[44px] border-b-2 px-2 text-[15px] font-semibold transition-colors"
                :class="tab === 'asal' ? 'border-ink text-ink' : 'border-transparent text-ink-soft hover:text-ink'">Asal Merek</button>
    </div>

    {{-- Tab Nama Merek --}}
    <div id="{{ $id }}-nama" role="tabpanel" aria-labelledby="{{ $id }}-tab-nama" x-show="tab === 'nama'" class="flex flex-col gap-2">
        <label for="{{ $id }}-cari" class="sr-only">Cari merek</label>
        <div class="relative">
            <input id="{{ $id }}-cari" type="search" x-model="q" placeholder="Cari merek…" autocomplete="off"
                   class="h-11 w-full rounded-xl border-dusty-pink pl-4 pr-11 text-base focus:border-ink focus:ring-0">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-ink-soft"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </div>

        {{-- Indeks huruf: lompat ke kelompoknya; huruf tanpa merek (atau tanpa hasil cari) redup dan nonaktif --}}
        <div class="grid grid-cols-7 sm:grid-cols-[repeat(14,minmax(0,1fr))]" aria-label="Lompat ke huruf">
            @foreach ($letters as $h)
                <button type="button" @click="lompat(@js($h))" :disabled="!ada(@js($h))" @disabled(! $byLetter->has($h))
                        aria-label="{{ $h === '#' ? 'Angka dan simbol' : 'Huruf '.$h }}"
                        class="h-11 rounded-lg text-[15px] font-medium transition-colors hover:bg-blush/40 disabled:text-ink-soft/40 disabled:hover:bg-transparent sm:h-9">{{ $h }}</button>
            @endforeach
        </div>

        <div x-ref="daftar" class="relative max-h-60 overflow-y-auto overscroll-contain border-t border-ink/10 pr-1 sm:max-h-72">
            @foreach ($byLetter as $h => $group)
                <section x-show="ada(@js($h))" data-huruf="{{ $h }}" aria-label="{{ $h }}">
                    <h3 class="sticky top-0 z-10 bg-white px-2 pb-1 pt-3 text-sm font-bold">{{ $h }}</h3>
                    <ul>
                        @foreach ($group as $b)
                            <li x-show="cocok(@js(mb_strtolower($b['name'])))">
                                @if ($check)
                                    <label class="{{ $item }}"><input type="checkbox" wire:model.live="merek" value="{{ $b['slug'] }}" class="h-5 w-5 rounded border-ink-soft text-ink focus:ring-ink"> {{ $b['name'] }}</label>
                                @else
                                    <a href="{{ route('produk.index', ['merek' => [$b['slug']]]) }}" class="{{ $item }} hover:underline">{{ $b['name'] }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
            <p x-show="kosong()" x-cloak class="px-2 py-4 text-[15px] text-ink-soft">Tidak ada merek dengan nama "<span x-text="q.trim()"></span>".</p>
        </div>
    </div>

    {{-- Tab Asal Merek --}}
    <div id="{{ $id }}-asal" role="tabpanel" aria-labelledby="{{ $id }}-tab-asal" x-show="tab === 'asal'" x-cloak
         class="max-h-[22rem] overflow-y-auto overscroll-contain pr-1">
        @foreach (\App\Enums\BrandOrigin::cases() as $origin)
            @php($group = $brands->where('origin', $origin->value))
            <section aria-label="{{ $origin->label() }}">
                <h3 class="sticky top-0 z-10 flex items-baseline justify-between gap-3 bg-white px-2 pb-1 pt-3">
                    <span class="text-sm font-bold">{{ $origin->label() }}</span>
                    <span class="text-xs text-ink-soft">{{ $origin->description() }}</span>
                </h3>
                @if ($group->isEmpty())
                    <p class="px-2 py-2 text-[15px] text-ink-soft">Segera hadir</p>
                @else
                    <ul>
                        @foreach ($group as $b)
                            <li>
                                @if ($check)
                                    <label class="{{ $item }}"><input type="checkbox" wire:model.live="merek" value="{{ $b['slug'] }}" class="h-5 w-5 rounded border-ink-soft text-ink focus:ring-ink"> {{ $b['name'] }}</label>
                                @else
                                    <a href="{{ route('produk.index', ['merek' => [$b['slug']]]) }}" class="{{ $item }} hover:underline">{{ $b['name'] }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>

    @unless ($check)
        <a href="{{ route('merek') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-ink/25 text-[15px] font-semibold transition-colors hover:bg-cream">Lihat semua merek dan logonya</a>
    @endunless
</div>
