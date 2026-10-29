<x-layouts.akun title="Profil">
    <div class="grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="judul-data" class="flex flex-col gap-5 rounded-3xl bg-white p-6 sm:p-8">
            <div class="flex flex-col gap-1">
                <h2 id="judul-data" class="text-xl font-bold">Data diri</h2>
                <p class="text-[15px] text-ink-soft">Nomor HP dipakai admin bila ada kendala pesanan.</p>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-5">
                @csrf
                @method('patch')

                <div class="flex flex-col gap-1.5">
                    <x-input-label for="name" value="Nama" />
                    <x-text-input id="name" name="name" type="text" class="w-full" :value="old('name', $user->name)" required autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="w-full" :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" />
                    <p class="text-sm text-ink-soft">Mengganti email berarti perlu verifikasi ulang.</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <x-input-label for="phone" value="Nomor HP (WhatsApp)" />
                    <x-text-input id="phone" name="phone" type="tel" inputmode="tel" class="w-full" :value="old('phone', $user->phone)" autocomplete="tel" placeholder="08xxxxxxxxxx" />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>

                <button type="submit" class="btn-primary self-start">Simpan profil</button>
            </form>
        </section>

        <section aria-labelledby="judul-password" class="flex flex-col gap-5 rounded-3xl bg-white p-6 sm:p-8">
            <div class="flex flex-col gap-1">
                <h2 id="judul-password" class="text-xl font-bold">{{ $user->password ? 'Ganti password' : 'Atur password' }}</h2>
                <p class="text-[15px] text-ink-soft">
                    {{ $user->password ? 'Minimal 8 karakter.' : 'Akunmu masuk lewat Google. Atur password bila ingin bisa masuk dengan email juga.' }}
                </p>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-5">
                @csrf
                @method('put')

                @if ($user->password)
                    <div class="flex flex-col gap-1.5">
                        <x-input-label for="current_password" value="Password sekarang" />
                        <x-password-input id="current_password" name="current_password" class="w-full" autocomplete="current-password" />
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" />
                    </div>
                @endif

                <div class="flex flex-col gap-1.5">
                    <x-input-label for="password" value="Password baru" />
                    <x-password-input id="password" name="password" class="w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <x-input-label for="password_confirmation" value="Ulangi password baru" />
                    <x-password-input id="password_confirmation" name="password_confirmation" class="w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
                </div>

                <button type="submit" class="btn-outline self-start">Simpan password</button>
            </form>
        </section>
    </div>

    @if ($activeOrders)
        <p class="text-[15px] text-ink-soft">
            Ada {{ $activeOrders }} pesanan yang masih berjalan.
            <a href="{{ route('akun.pesanan') }}" class="font-semibold text-ink underline underline-offset-4">Lihat pesanan</a>
        </p>
    @endif
</x-layouts.akun>
