@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-2xl bg-blush/45 px-4 py-3 text-[15px] font-medium text-ink', 'role' => 'status']) }}>
        {{ $status }}
    </div>
@endif
