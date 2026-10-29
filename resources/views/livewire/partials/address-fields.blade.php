{{-- Kolom form alamat (AddressForm). $prefix membuat id unik bila dua form ada di satu halaman. --}}
@php($p = $prefix ?? 'alamat')
@php($select = 'h-12 w-full rounded-2xl border-dusty-pink bg-white px-4 text-base focus:border-ink focus:ring-0')

<div class="grid gap-5 sm:grid-cols-2">
    <div class="flex flex-col gap-1.5">
        <x-input-label :for="$p.'-nama'" value="Nama penerima" />
        <x-text-input :id="$p.'-nama'" wire:model="form.recipient_name" type="text" class="w-full" autocomplete="name" required />
        <x-input-error :messages="$errors->get('form.recipient_name')" />
    </div>

    <div class="flex flex-col gap-1.5">
        <x-input-label :for="$p.'-hp'" value="Nomor HP" />
        <x-text-input :id="$p.'-hp'" wire:model="form.phone" type="tel" inputmode="tel" class="w-full" autocomplete="tel" placeholder="08xxxxxxxxxx" required />
        <x-input-error :messages="$errors->get('form.phone')" />
    </div>

    <div class="flex flex-col gap-1.5 sm:col-span-2">
        <x-input-label :for="$p.'-alamat'" value="Alamat lengkap" />
        <textarea id="{{ $p }}-alamat" wire:model="form.address_line" rows="3" autocomplete="street-address" required
                  placeholder="Nama jalan, nomor rumah, RT/RW, patokan"
                  class="w-full rounded-2xl border-dusty-pink bg-white px-4 py-3 text-base focus:border-ink focus:ring-0"></textarea>
        <x-input-error :messages="$errors->get('form.address_line')" />
    </div>

    <div class="flex flex-col gap-1.5">
        <x-input-label :for="$p.'-provinsi'" value="Provinsi" />
        <select id="{{ $p }}-provinsi" wire:model="form.province" class="{{ $select }}" autocomplete="address-level1" required>
            <option value="">Pilih provinsi</option>
            @foreach (config('provinsi') as $provinsi)
                <option value="{{ $provinsi }}">{{ $provinsi }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('form.province')" />
    </div>

    <div class="flex flex-col gap-1.5">
        <x-input-label :for="$p.'-kota'" value="Kota/kabupaten" />
        <x-text-input :id="$p.'-kota'" wire:model="form.city" type="text" class="w-full" autocomplete="address-level2" required />
        <x-input-error :messages="$errors->get('form.city')" />
    </div>

    <div class="flex flex-col gap-1.5">
        <x-input-label :for="$p.'-kecamatan'" value="Kecamatan" />
        <x-text-input :id="$p.'-kecamatan'" wire:model="form.district" type="text" class="w-full" autocomplete="address-level3" required />
        <x-input-error :messages="$errors->get('form.district')" />
    </div>

    <div class="flex flex-col gap-1.5">
        <x-input-label :for="$p.'-kodepos'" value="Kode pos" />
        <x-text-input :id="$p.'-kodepos'" wire:model="form.postal_code" type="text" inputmode="numeric" maxlength="5" class="w-full" autocomplete="postal-code" required />
        <x-input-error :messages="$errors->get('form.postal_code')" />
    </div>

    <div class="flex flex-col gap-1.5">
        <x-input-label :for="$p.'-label'" value="Label (opsional)" />
        <x-text-input :id="$p.'-label'" wire:model="form.label" type="text" maxlength="40" class="w-full" placeholder="Rumah, Kantor, Kos" />
        <x-input-error :messages="$errors->get('form.label')" />
    </div>

    <label class="inline-flex min-h-[44px] items-center gap-2.5 self-end text-[15px]">
        <input type="checkbox" wire:model="form.is_primary" class="h-5 w-5 rounded border-ink-soft text-ink focus:ring-ink">
        Jadikan alamat utama
    </label>
</div>
