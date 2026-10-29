<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): SymfonyRedirect
    {
        abort_unless(self::enabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => 'Masuk dengan Google gagal. Silakan coba lagi.']);
        }

        // Akun email yang sudah ada otomatis ditautkan ke Google
        $user = User::where('google_id', $google->getId())->first()
            ?? User::firstOrNew(['email' => $google->getEmail()]);

        $user->google_id = $google->getId();
        $user->name = $user->name ?: ($google->getName() ?: $google->getEmail());
        $user->email_verified_at ??= now();
        $user->save();

        Auth::login($user, remember: true);

        return redirect()->intended(route('akun', absolute: false));
    }
}
