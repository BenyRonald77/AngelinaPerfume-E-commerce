@props(['messages'])

@if ($messages)
    {{-- Merah gelap khusus pesan error (fungsi, bukan warna merek); kontras 6:1 di latar putih --}}
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm font-medium text-red-700', 'role' => 'alert']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
