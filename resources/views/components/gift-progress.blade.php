{{-- Progres menuju hadiah otomatis (permintaan klien): pembeli tahu sudah belanja decant berapa dari Rp100.000.
     Dipakai di keranjang dan checkout; data dari App\Support\Promo::progress(). --}}
@props(['progress'])

@if ($progress->isNotEmpty())
    <div class="flex flex-col gap-3">
        @foreach ($progress as ['rule' => $rule, 'current' => $current, 'target' => $target, 'reached' => $reached])
            @php($pct = $target > 0 ? min(100, intdiv($current * 100, $target)) : 100)
            <div class="flex flex-col gap-2 rounded-2xl bg-blush/45 px-4 py-3.5">
                <p class="flex items-baseline justify-between gap-3 text-[15px]">
                    <span class="font-semibold">{{ $rule->spendLabel() }}</span>
                    <span class="tabular-nums"><span class="font-semibold">{{ \App\Support\Rupiah::format($current) }}</span> / {{ \App\Support\Rupiah::format($target) }}</span>
                </p>
                <span class="block h-2 overflow-hidden rounded-full bg-white" role="progressbar" aria-label="{{ $rule->spendLabel() }} menuju {{ $rule->giftLabel() }}"
                      aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
                    <span class="block h-full rounded-full bg-ink" style="width: {{ $pct }}%"></span>
                </span>
                <p class="text-sm">
                    @if ($reached)
                        <span class="font-semibold">Kamu dapat {{ $rule->giftLabel() }}.</span>
                    @else
                        Tambah <span class="font-semibold">{{ \App\Support\Rupiah::format($target - $current) }}</span> lagi untuk dapat {{ $rule->giftLabel() }}.
                    @endif
                    @if ($rule->description)
                        <span class="text-ink-soft">{{ $rule->description }}</span>
                    @endif
                    @if ($rule->category)
                        <span class="text-ink-soft">Hanya produk {{ \Illuminate\Support\Str::lower($rule->category->name) }} yang dihitung.</span>
                    @endif
                </p>
            </div>
        @endforeach
    </div>
@endif
