<x-guest-layout>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="font-display text-[32px] leading-tight">Masuk</h1>
            <p class="text-[15px] text-ink-soft">Masuk untuk checkout dan melihat riwayat pesananmu.</p>
        </div>

        <x-auth-session-status :status="session('status')" />

        <x-google-button>Masuk dengan Google</x-google-button>

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1.5">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="email@contoh.com" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="flex flex-col gap-1.5">
                <x-input-label for="password" :value="__('Password')" />
                <x-password-input id="password" class="w-full" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label for="remember_me" class="inline-flex min-h-[44px] items-center gap-2.5 text-[15px]">
                    <input id="remember_me" type="checkbox" name="remember" class="h-5 w-5 rounded border-ink-soft text-ink focus:ring-ink">
                    {{ __('Remember me') }}
                </label>
                @if (Route::has('password.request'))
                    <a class="inline-flex min-h-[44px] items-center text-[15px] font-semibold underline underline-offset-4" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <x-primary-button class="w-full">{{ __('Log in') }}</x-primary-button>
        </form>

        <p class="text-center text-[15px]">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold underline underline-offset-4">Daftar</a>
        </p>
    </div>
</x-guest-layout>
