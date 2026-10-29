@props(['status'])

@php
    use App\Enums\OrderStatus;

    // Warna dari palet toko; makna dibaca dari teks, warna hanya membantu memindai daftar
    $class = match ($status) {
        OrderStatus::MenungguPembayaran => 'bg-blush text-ink',
        OrderStatus::Lunas, OrderStatus::Diproses, OrderStatus::Dikirim => 'bg-cream text-ink ring-1 ring-inset ring-dusty-pink',
        OrderStatus::Selesai => 'bg-ink text-white',
        default => 'border border-dashed border-ink-soft text-ink-soft',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold', $class]) }}>{{ $status->label() }}</span>
