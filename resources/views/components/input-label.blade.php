@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[15px] font-semibold text-ink']) }}>
    {{ $value ?? $slot }}
</label>
