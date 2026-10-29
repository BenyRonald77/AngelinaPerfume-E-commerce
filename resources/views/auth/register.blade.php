<x-guest-layout>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="font-display text-[32px] leading-tight">Daftar</h1>
            <p class="text-[15px] text-ink-soft">Akun dibutuhkan untuk checkout, supaya alamat dan riwayat pesananmu tersimpan.</p>
        </div>

        <x-google-button>Daftar dengan Google</x-google-button>

        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1.5">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" class="w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nama kamu" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="flex flex-col gap-1.5">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="email@contoh.com" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="flex flex-col gap-1.5">
                <x-input-label for="password" :value="__('Password')" />
                <x-password-input id="password" class="w-full" name="password" required autocomplete="new-password" aria-describedby="aturan-password" />
                <p id="aturan-password" class="text-sm text-ink-soft">Minimal 8 karakter.</p>
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="flex flex-col gap-1.5">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-password-input id="password_confirmation" class="w-full" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <x-primary-button class="w-full">{{ __('Register') }}</x-primary-button>
        </form>

        <p class="text-center text-[15px]">
            <a href="{{ route('login') }}" class="font-semibold underline underline-offset-4">{{ __('Already registered?') }}</a>
        </p>
    </div>
</x-guest-layout>
