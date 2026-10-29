<?php

namespace App\Livewire\Forms;

use App\Models\Address;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/** Form alamat pengiriman; dipakai di buku alamat dan di checkout */
class AddressForm extends Form
{
    #[Locked]
    public ?int $id = null;

    public string $label = '';

    public string $recipient_name = '';

    public string $phone = '';

    public string $address_line = '';

    public string $district = '';

    public string $city = '';

    public string $province = '';

    public string $postal_code = '';

    public bool $is_primary = false;

    protected function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:40'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'address_line' => ['required', 'string', 'max:500'],
            'district' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', Rule::in(config('provinsi'))],
            'postal_code' => ['required', 'digits:5'],
            'is_primary' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP diawali 08 atau +62, contoh 081234567890.',
            'postal_code.digits' => 'Kode pos 5 angka.',
            'province.in' => 'Pilih provinsi dari daftar.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'recipient_name' => 'nama penerima',
            'phone' => 'nomor HP',
            'address_line' => 'alamat',
            'district' => 'kecamatan',
            'city' => 'kota/kabupaten',
            'province' => 'provinsi',
            'postal_code' => 'kode pos',
        ];
    }

    public function fillFrom(Address $address): void
    {
        $this->id = $address->id;
        $this->fill($address->only(['recipient_name', 'phone', 'address_line', 'district', 'city', 'province', 'postal_code', 'is_primary']));
        $this->label = (string) $address->label;
    }

    public function startNew(User $user): void
    {
        $this->reset();
        $this->resetValidation();
        // Alamat pertama otomatis jadi alamat utama; nama dan HP diisi dari profil
        $this->recipient_name = $user->name;
        $this->phone = (string) $user->phone;
        $this->is_primary = ! $user->addresses()->exists();
    }

    public function save(User $user): Address
    {
        $data = $this->validate();
        $data['label'] = filled($data['label']) ? $data['label'] : null;

        $address = $this->id ? $user->addresses()->findOrFail($this->id) : $user->addresses()->make();
        $address->fill($data);
        // Selalu ada tepat satu alamat utama
        $address->is_primary = $this->is_primary || ! $user->addresses()->whereKeyNot($address->id ?? 0)->where('is_primary', true)->exists();
        $address->save();

        if ($address->is_primary) {
            $user->addresses()->whereKeyNot($address->id)->update(['is_primary' => false]);
        }

        $this->reset();

        return $address;
    }
}
