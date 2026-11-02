@php
    use App\Support\WhatsApp;

    $sections = [
        ['Data yang kami kumpulkan', [
            'Data akun: nama, email, nomor HP, dan password yang tersimpan dalam bentuk terenkripsi. Bila masuk lewat Google, kami menerima nama dan email dari akun Google tersebut.',
            'Data pengiriman: nama penerima, nomor HP, alamat, kecamatan, kota, provinsi, dan kode pos.',
            'Data pesanan: barang yang dibeli, total pembayaran, status pembayaran, kurir, nomor resi, dan catatan untuk penjual.',
            'Cookie sesi: dipakai agar kamu tetap masuk dan isi keranjang tidak hilang.',
        ]],
        ['Cara kami memakai data', [
            'Memproses pesanan, pembayaran, pengiriman, dan klaim garansi.',
            'Mengirim email terkait akun dan status pesanan.',
            'Menghubungi kamu bila ada kendala pada pesanan.',
        ]],
        ['Pihak yang menerima data', [
            'Penyedia pembayaran, saat pembayaran online tersedia: data pesanan dan kontak dikirim ke Xendit. Website ini tidak menyimpan data kartu.',
            'Kurir: nama, nomor HP, dan alamat penerima, untuk mengantar paket.',
            'Penyedia layanan yang menjalankan website dan email kami.',
            'Pengelola teknis website, PT Jogja Creative Production, sebatas untuk membangun dan merawat website.',
            'Kami tidak menjual data pribadimu.',
        ]],
        ['Penyimpanan data', [
            'Data akun disimpan selama akun aktif. Riwayat pesanan tetap disimpan sebagai catatan transaksi toko.',
        ]],
        ['Hak kamu', [
            'Profil, alamat, dan password bisa diubah sendiri di halaman Akun.',
            'Untuk meminta salinan atau penghapusan data, hubungi kami lewat kontak di bawah.',
        ]],
    ];
@endphp

<x-layouts.shop title="Kebijakan Privasi" description="Data apa yang dikumpulkan Angelia Perfume, cara memakainya, dan hak kamu atas data tersebut.">
    <article class="mx-auto flex max-w-3xl flex-col gap-10 px-4 pb-20 pt-10 sm:px-6">
        <header class="flex flex-col gap-3">
            <h1 class="font-display text-[34px] leading-tight sm:text-[44px]">Kebijakan Privasi</h1>
            <p class="text-[15px] text-ink-soft">Berlaku sejak 2 November 2026</p>
            <p class="text-[17px] leading-relaxed">Halaman ini menjelaskan cara toko Angelia Perfume mengelola data pribadimu saat memakai website ini. Website dibangun dan dirawat oleh PT Jogja Creative Production untuk toko Angelia Perfume.</p>
        </header>

        @foreach ($sections as [$heading, $items])
            <section class="flex flex-col gap-3">
                <h2 class="text-xl font-bold">{{ $heading }}</h2>
                <ul class="flex list-disc flex-col gap-2 pl-5 text-[17px] leading-relaxed">
                    @foreach ($items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <section class="flex flex-col gap-3">
            <h2 class="text-xl font-bold">Hubungi kami</h2>
            <p class="text-[17px] leading-relaxed">
                Pertanyaan soal privasi bisa dikirim ke
                @if ($wa = WhatsApp::link('Halo Angelia Perfume, saya mau tanya soal data pribadi saya.'))
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="font-semibold underline underline-offset-4">WhatsApp {{ WhatsApp::display() }}</a>.
                @else
                    WhatsApp {{ WhatsApp::display() }}.
                @endif
            </p>
        </section>
    </article>
</x-layouts.shop>
