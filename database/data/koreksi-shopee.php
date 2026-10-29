<?php

/*
| Koreksi data ekspor Shopee yang tidak bisa dirapikan otomatis.
| 'produk' memakai nama folder di shopee-media sebagai kunci; kolom yang bisa diisi:
| name, brand, concentration, aroma (nama pilihan untuk produk tanpa aroma, mis. hadiah).
*/

return [

    'produk' => [
        // Listing PART 1/2/3 di Shopee digabung menjadi satu produk (aturan bisnis 2)
        'decant-mykonos-semua-varian' => ['name' => 'MYKONOS Decant', 'brand' => 'MYKONOS'],
        'decant-crusita-semua-varian' => ['name' => 'CRUSITA Decant', 'brand' => 'CRUSITA'],

        'alchemist-frangrance-all-variant-size-0-5ml-1ml-2ml-3ml-5ml-25955904428' => ['name' => 'ALCHEMIST Fragrance'],
        'fermo-frgas-all-variant-size-0-5ml-1ml-2ml-3ml-5ml-29573515338' => ['name' => 'FERMO Frags'],
        'hmns-hair-body-mist-edt-size-2ml-3ml-5ml-10ml-21487337555' => ['name' => 'HMNS Hair & Body Mist'],
        'one-two-by-beauty-now-edp-ex-collab-with-jessica-mila-size-0-24063844325' => ['name' => 'one. & two. by Beauty Now'],
        'parfum-miniso-edt-edp-size-1ml-2ml-5ml-24958494751' => ['name' => 'MINISO'],
        'rasasi-hawas-all-variant-ice-black-fire-elixir-for-him-for-h-51653329608' => ['name' => 'RASASI Hawas'],

        'bnib-sisa-stok-decant-angelia-perfume-24012688785' => ['name' => 'MENU Full Size BNIB', 'brand' => 'MENU'],
        'crusita-near-exp-50ml-stok-terbatas-wajib-baca-deskripsi-ang-24087091428' => ['name' => 'CRUSITA Clearance Near Exp'],
        'crusita-scent-of-indonesia-extrait-de-parfum-50ml-angelia-pe-27564151343' => ['name' => 'CRUSITA Scent of Indonesia'],
        'mykonos-glitch-extrait-de-parfum-x-ade-setiawan-50ml-100ml-a-40005997511' => ['name' => 'MYKONOS Glitch x Ade Setiawan'],
        'mykonos-x-jumbo-don-meri-50ml-extrait-de-parfum-angelia-perf-40479220053' => ['name' => 'MYKONOS x Jumbo Don Meri'],
        'ready-stock-mykonos-50ml-all-variant-edp-extrait-de-parfum-a-26852484325' => ['name' => 'MYKONOS Full Size 50 ml', 'concentration' => null],
        'varian-lengkap-mykonos-travel-size-15-ml-miniature-collectio-26852228008' => ['name' => 'MYKONOS Travel Size 15 ml'],

        'atomizer-5ml-botol-parfum-travel-size-angelia-perfume-46452973465' => ['name' => 'Botol Atomizer 5 ml'],
        'storage-kotak-penyimpanan-vial-oles-botol-spray-decant-parfu-24909755216' => ['name' => 'Kotak Penyimpanan Vial & Botol Spray'],
        'official-vial-tester-saff-co-2ml-extrait-de-parfum-22388452932' => ['name' => 'SAFF & Co. Official Vial 2 ml'],

        // Hadiah (GWP): tidak tampil di katalog, dipakai aturan hadiah otomatis
        'free-random-official-tester-mykonos-3-5ml-26415148107' => ['name' => 'Free Tester MYKONOS 3,5 ml', 'aroma' => 'Tester random'],
        'freebies-random-official-tester-minimum-purchase-400k-29750302047' => ['name' => 'Free Tester Random (min. belanja Rp400.000)', 'brand' => null, 'aroma' => 'Tester random'],
        'gwp-freebies-mystery-gift-for-minimum-purchase-100k-angelia-24008815732' => ['name' => 'Mystery Gift (min. belanja Rp100.000)', 'brand' => null, 'aroma' => 'Hadiah random'],
    ],

    // Salah ketik atau singkatan nama aroma di kolom variasi Shopee
    'aroma' => [
        'California Sig' => 'California Signature',
        'Royale Ispahan' => 'Royal Ispahan',
        'Land of Prdise' => 'Land of Paradise',
        'Greatest Toba' => 'The Greatest Toba',
    ],

];
