@props(['product', 'bestseller' => false, 'eager' => false])

@php
    $price = $product->cardPrice();
    $cover = $product->cover;
@endphp

<article {{ $attributes->class('group relative flex flex-col gap-3') }}>
    <div class="relative aspect-square overflow-hidden rounded-[20px] border border-dusty-pink/45 bg-white">
        @if ($cover)
            <img src="{{ $cover->url(800) }}"
                 srcset="{{ $cover->url(800) }} 800w, {{ $cover->url(1600) }} 1600w"
                 sizes="(min-width: 1280px) 290px, (min-width: 1024px) 23vw, (min-width: 640px) 31vw, 46vw"
                 width="{{ $cover->width }}" height="{{ $cover->height }}" alt=""
                 loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
                 @class(['h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]', 'opacity-55' => ! $price])>
        @else
            <div class="flex h-full items-center justify-center px-4 text-center text-sm text-ink-soft">Foto belum ada</div>
        @endif

        @if (! $price)
            <span class="absolute left-3 top-3 rounded-full border border-ink bg-white px-3 py-1.5 text-[13px] font-semibold">Stok habis</span>
        @elseif ($bestseller)
            <span class="absolute left-3 top-3 rounded-full bg-ink px-3 py-1.5 text-[13px] font-semibold text-cream">Terlaris</span>
        @endif
    </div>

    <div class="flex flex-col gap-1">
        @if ($product->brand)
            <span class="eyebrow">{{ $product->brand->name }}</span>
        @endif
        <h3 class="font-display text-[17px] leading-tight sm:text-xl">
            {{-- Tautan menutupi seluruh kartu supaya target sentuh besar --}}
            <a href="{{ route('produk.show', $product) }}" class="after:absolute after:inset-0 after:rounded-[20px] hover:underline">{{ $product->name }}</a>
        </h3>
        <p class="text-sm text-ink-soft">{{ $product->cardMeta() }}</p>
        <p class="flex items-baseline gap-1.5 pt-1">
            @if ($price)
                @if ($price['prefix'])
                    <span class="text-sm text-ink-soft">{{ $price['prefix'] }}</span>
                @endif
                <span class="text-lg font-bold sm:text-[22px]">{{ $price['amount'] }}</span>
            @else
                <span class="text-[15px] font-semibold text-ink-soft">Tanya stok via WhatsApp</span>
            @endif
        </p>
    </div>
</article>
