@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-12 rounded-2xl border-dusty-pink bg-white px-4 text-base focus:border-ink focus:ring-0 disabled:bg-cream']) }}>
