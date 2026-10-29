@props(['disabled' => false])

{{-- Kolom password dengan tombol lihat/sembunyikan; class dipasang di pembungkus, atribut lain di input --}}
<div x-data="{ show: false }" {{ $attributes->only('class')->merge(['class' => 'relative']) }}>
    <input type="password" :type="show ? 'text' : 'password'" @disabled($disabled)
           {{ $attributes->except('class')->merge(['class' => 'h-12 w-full rounded-2xl border-dusty-pink bg-white pl-4 pr-14 text-base focus:border-ink focus:ring-0 disabled:bg-cream']) }}>
    <button type="button" @click="show = ! show"
            :aria-pressed="show.toString()" aria-pressed="false"
            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" aria-label="Tampilkan password"
            @if ($attributes->has('id')) aria-controls="{{ $attributes->get('id') }}" @endif
            class="absolute right-0.5 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-xl text-ink-soft transition-colors hover:bg-cream hover:text-ink">
        <svg x-show="! show" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg x-show="show" x-cloak aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.6 5.1A9.8 9.8 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7c1.9 0 3.6-.6 5-1.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="m3 3 18 18"/></svg>
    </button>
</div>
