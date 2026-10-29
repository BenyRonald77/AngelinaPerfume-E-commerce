@props(['title', 'heading' => null])

@php
    $tabs = [
        ['label' => 'Pesanan', 'href' => route('akun.pesanan'), 'active' => request()->routeIs('akun.pesanan*')],
        ['label' => 'Alamat', 'href' => route('akun.alamat'), 'active' => request()->routeIs('akun.alamat')],
        ['label' => 'Profil', 'href' => route('akun'), 'active' => request()->routeIs('akun')],
    ];
@endphp

<x-layouts.shop :title="$title">
    <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 pb-20 pt-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <p class="text-[15px] text-ink-soft">Halo, {{ auth()->user()->name }}</p>
                <h1 class="font-display text-[30px] leading-tight sm:text-[40px]">{{ $heading ?? $title }}</h1>
            </div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex min-h-[44px] items-center text-[15px] font-semibold underline underline-offset-4">Keluar</button>
            </form>
        </div>

        <nav aria-label="Menu akun" class="-mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            @foreach ($tabs as $tab)
                <a href="{{ $tab['href'] }}" @if ($tab['active']) aria-current="page" @endif
                   @class([
                       'inline-flex min-h-[44px] shrink-0 items-center rounded-full border-2 px-5 pt-0.5 text-[15px] font-semibold transition-colors',
                       'border-ink bg-ink text-white' => $tab['active'],
                       'border-dusty-pink/60 bg-white hover:border-ink' => ! $tab['active'],
                   ])>{{ $tab['label'] }}</a>
            @endforeach
        </nav>

        @unless (auth()->user()->hasVerifiedEmail())
            <div class="card-dashed flex flex-wrap items-center justify-between gap-4 p-5">
                <p class="max-w-prose text-[15px]">Email <strong>{{ auth()->user()->email }}</strong> belum diverifikasi. Checkout dan riwayat pesanan terbuka setelah kamu mengeklik tautan di email.</p>
                <form method="post" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn-outline">Kirim ulang tautan</button>
                </form>
            </div>
        @endunless

        {{ $slot }}
    </div>

    @isset($scripts)
        <x-slot:scripts>{{ $scripts }}</x-slot:scripts>
    @endisset
</x-layouts.shop>
