# CLAUDE.md - Angelia Perfume (toko online)

Website toko sendiri untuk toko Shopee **Angelia Perfume** (https://shopee.co.id/angeliaperfume): decant (share in bottle) dan full size parfum original dari hampir 100 merek. Ada akun pembeli, keranjang, checkout, pembayaran online, dan panel admin.
Selalu balas pengguna dalam Bahasa Indonesia. Teks UI berbahasa Indonesia, harga dalam Rupiah.

## Sumber kebenaran
- **PRD** (Claude Docs): https://claude.ai/artifact/M48wB2NLwtGXuXFry4UmJr. Baca dengan tool Claude Docs (bukan web fetch) sebelum membuat fitur; bagian penting: Katalog & data produk, Varian/ukuran/foto, Desain & palet, Fitur pembeli, Panel admin, Aturan bisnis, Model data, Pertanyaan terbuka.
- **Desain** (kanvas Design): https://claude.ai/artifact/9LyocBwcQh1VivYg5T4sPH, artboard `Main.dc.html` (Beranda) dan `Produk.dc.html` (Detail produk, interaktif). Salinan lokal di `docs/design/`; gambar di file `.dc.html` memakai `/_blob/...` yang hanya jalan di kanvas, versi lokalnya di `docs/design/img/`.
- **Panduan daftar API kurir**: https://claude.ai/artifact/6NPVFdiFhpvaq5kwTw3twZ

## Data produk (di luar repo, jangan di-commit)
Folder `C:\Users\ASUS\Documents\JCP IA\shopee-media\` (sekitar 1,4 GB):
- 182 folder produk. Isi tiap folder: `00-sampul.*` (foto sampul), `01..` (foto produk), `panduan-ukuran*`, `varian-NN-<aroma>.*` (foto per aroma, berisi notes top/middle/base), `info.txt` (kode produk & variasi Shopee, harga/stok/berat per SKU), `deskripsi.txt`.
- `harga-stok-semua.csv`: 4.501 SKU dengan kolom folder, kode_produk, kode_variasi, nama_produk, aroma, ukuran, sku, harga, stok, berat_gram. Ini sumber utama impor/seeder.
- `rekap-penjualan.txt`, `produk-terlaris.csv`: rekap pesanan Shopee Juli–Oktober 2026 (tanpa data pribadi).
- `ekspor/`: file xlsx asli Seller Centre (Informasi Media, Penjualan, Pengiriman, Dasar).
- Skrip Python: `unduh_foto_shopee.py`, `tambah_harga_stok.py`, `rekap_penjualan.py`. File xlsx Shopee tidak bisa dibaca openpyxl; baca XML-nya lewat zipfile seperti di skrip itu.

## Stack
Laravel 12 (PHP 8.2+), MySQL/MariaDB, Filament (panel admin di `/admin`), Blade + Livewire + Tailwind CSS (tanpa server Node di produksi), Laravel Breeze + Socialite (login Google), Xendit (Invoice API + webhook).
- Pengembangan LOKAL dulu. Laptop: PHP 8.2 dan MariaDB dari XAMPP (`C:\xampp`, `mysql.exe` di `C:\xampp\mysql\bin`, belum di PATH), Composer, Node 24, Git, Python 3.12.
- Hosting nanti: Rumahweb shared hosting (cPanel, LiteSpeed). Jangan bergantung pada queue worker yang selalu jalan, `proc_open`, atau `mail()`; tugas berkala lewat cron (`schedule:run`), email lewat SMTP, build aset di GitHub Actions.

## Aturan bisnis (ringkas; detail di PRD)
1. Produk → aroma → ukuran. **SKU = aroma × ukuran**, masing-masing punya harga, stok, berat, batas beli. Harga bisa berbeda antar aroma pada ukuran yang sama.
2. Listing Shopee "PART 1/2/3" (MYKONOS dan CRUSITA decant) adalah **satu produk** di website.
3. Kode SKU dibuat saat impor: `AP-<nomor produk 4 digit>-<nomor aroma 2 digit>-<ml>ML`, misalnya `AP-0012-03-2ML`. Kode produk dan kode variasi Shopee tetap disimpan untuk pencocokan (sinkron stok nanti).
4. Ukuran tetap: 0,5 dan 1 ml (vial oles), 2/3/5/10 ml (spray), 15 ml (travel size), 37/50/100 ml (botol asli). Nama variasi Shopee "Aroma,Ukuran" dipecah di koma sebelum angka ukuran (ukuran memakai koma desimal, "Topaz,0,5mL (vial oles)").
5. Pembeli **wajib punya akun** (tidak ada guest checkout). Satu admin (pemilik); peran staf gudang disiapkan saja.
6. Hadiah otomatis: free decant untuk belanja min. Rp100.000, free tester untuk min. Rp400.000; tercatat Rp0 di pesanan. Listing GWP Shopee bukan produk katalog.
7. Batas beli per SKU (full size MYKONOS 1 botol per pesanan). Extra dus/bubble wrap = opsi checkout Rp1.700.
8. Stok berkurang saat pesanan dibuat; pesanan tidak dibayar 24 jam batal otomatis dan stok kembali. Stok tidak boleh minus.
9. Jangan menampilkan data yang belum pasti. Stok habis tampil "Stok habis" dengan tombol tanya via WhatsApp. Data yang belum ada pakai placeholder, misalnya `[NOMOR TOKO]`.
10. Deskripsi decant di Shopee kebanyakan hanya aturan toko yang diulang: tampilkan di halaman Bantuan, bukan per produk. Deskripsi full size (notes, BPOM) tampil di halaman produk.

## Keputusan yang masih terbuka (jangan diasumsikan)
- **Ongkir/kurir**: perusahaan menolak Biteship berbayar. Pilihan: Biteship Starter (Rp0 sampai 100 kiriman/bulan), tabel ongkir manual per provinsi + admin mengetik resi, atau API langsung J&T/JNE. Buat modul ongkir sebagai interface/driver yang bisa diganti; mulai dengan **tabel ongkir manual**.
- **Xendit**: selama pengembangan memakai akun perusahaan developer dalam mode test; saat rilis akun atas nama toko. Kunci hanya di `.env`.
- COD, gratis ongkir, jam kirim/operasional, kurir pengganti SPX Hemat, sinkron stok dengan Shopee (Shopee Open Platform, akun developer atas nama toko). Lihat "Pertanyaan terbuka" di PRD.

## Desain
- Palet (token Tailwind): `cream` #FFF4F3 (latar), `blush` #F9B6BA (hero, header, footer, chip terpilih), `dusty-pink` #DE9DA9 (bar pengumuman, garis kartu/filter), `ink` #3B2A25 (teks, judul, harga, tombol utama), `ink-soft` #5A4842 (teks sekunder), `white` (kartu, panel). **Jangan pakai** cocoa #551902 atau rose #DF7783 (pemilik menilai terlalu merah).
- Font Google: Pacifico (logo), Shrikhand (judul & nama produk), League Spartan (teks, harga, tombol).
- Mobile-first, target sentuh minimal 44 px, kontras teks minimal 4.5:1. Ikuti tata letak kanvas: hero blush + strip Terlaris, lingkaran Koleksi, grid produk dengan filter, halaman produk dengan chip aroma (foto kecil), pilihan ukuran, notes, bar progres hadiah, tab Deskripsi/Pengiriman/Garansi.
- Rating bintang tidak ditampilkan sampai ada ulasan nyata.

## Konvensi & batasan
- Rahasia (Xendit, Google, kurir, SMTP) hanya di `.env`; jangan di-commit.
- Data Shopee hanya dari ekspor Seller Centre atau Shopee Open Platform resmi. Jangan scraping atau mengakali anti-bot Shopee.
- Ekspor pesanan Shopee (`Order.all.*.xlsx` di Downloads) berisi nama, alamat, HP pembeli: jangan disalin ke repo atau seeder.
- Foto produk diubah ke WebP (800 px dan 1600 px) saat impor, disimpan di storage, tidak di-commit.
- Komentar kode boleh Bahasa Indonesia.
- Cek sebelum selesai: `php artisan test` dan `npm run build`.
