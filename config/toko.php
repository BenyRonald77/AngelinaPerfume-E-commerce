<?php

return [

    /*
    | Folder hasil ekspor Shopee (foto, info.txt, harga-stok-semua.csv).
    | Path relatif dibaca dari root proyek. Folder ini tidak di-commit.
    */
    'shopee_media_path' => env('SHOPEE_MEDIA_PATH', 'shopee-media'),

    // Folder merek.php, koreksi-shopee.php, dan notes-aroma.php untuk impor
    'import_data_path' => database_path('data'),

    // Nomor WhatsApp admin format internasional tanpa +, mis. 6281234567890
    'whatsapp_number' => env('WHATSAPP_NUMBER'),

    'shopee_url' => 'https://shopee.co.id/angeliaperfume',

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Angelia'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
