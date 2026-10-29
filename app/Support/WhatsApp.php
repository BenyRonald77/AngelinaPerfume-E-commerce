<?php

namespace App\Support;

final class WhatsApp
{
    /** https://wa.me/<nomor>, atau null selama WHATSAPP_NUMBER belum diisi */
    public static function baseUrl(): ?string
    {
        $number = preg_replace('/\D/', '', (string) config('toko.whatsapp_number'));

        return $number === '' ? null : 'https://wa.me/'.$number;
    }

    /** null selama nomor belum diisi; tampilan memakai placeholder [NOMOR TOKO] */
    public static function link(string $message): ?string
    {
        $base = self::baseUrl();

        return $base === null ? null : $base.'?text='.rawurlencode($message);
    }

    public static function display(): string
    {
        $number = (string) config('toko.whatsapp_number');

        return $number !== '' ? '+'.ltrim($number, '+') : '[NOMOR TOKO]';
    }
}
