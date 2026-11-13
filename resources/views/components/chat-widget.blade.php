{{-- Tanya Angelia: tombol bulat logo A di pojok kanan bawah (permintaan klien).
     Dua pilihan: chat langsung di WhatsApp, atau isi form singkat yang dikirim sebagai pesan WhatsApp.
     Isian form tidak dikirim ke server website; hanya disusun di browser menjadi teks pesan. --}}
@php
    $wa = \App\Support\WhatsApp::baseUrl();
    $genders = ['Perempuan', 'Laki-laki'];
    $aromas = ['Sweet', 'Fruity', 'Floral', 'Fresh', 'Woody', 'Musky', 'Spicy'];
    $acaras = ['Sehari-hari', 'Kerja / kuliah', 'Kencan', 'Pesta / acara malam', 'Liburan', 'Hadiah'];
    $chip = 'inline-flex min-h-[44px] cursor-pointer items-center rounded-full border border-dusty-pink bg-white px-4 text-[15px] transition-colors hover:bg-blush/40 peer-checked:border-ink peer-checked:bg-blush peer-checked:font-semibold peer-focus-visible:ring-2 peer-focus-visible:ring-ink peer-focus-visible:ring-offset-2';
@endphp

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tanyaAngelia', (wa) => ({
            buka: false,
            langkah: 'pilih',
            nama: '',
            gender: '',
            aroma: [],
            acara: '',
            galat: {},
            bukaTutup() {
                this.buka ? this.tutup() : this.bukaPanel();
            },
            bukaPanel() {
                this.buka = true;
                this.$nextTick(() => this.$refs.judul.focus());
            },
            tutup() {
                this.buka = false;
                this.langkah = 'pilih';
                this.$nextTick(() => this.$refs.tombol.focus());
            },
            keForm() {
                this.langkah = 'form';
                this.$nextTick(() => this.$refs['f-nama'].focus());
            },
            kembali() {
                this.langkah = 'pilih';
                this.galat = {};
                this.$nextTick(() => this.$refs.judul.focus());
            },
            kirim() {
                this.galat = {};
                if (!this.nama.trim()) this.galat.nama = 'Isi nama kamu.';
                if (!this.gender) this.galat.gender = 'Pilih salah satu.';
                if (!this.aroma.length) this.galat.aroma = 'Pilih minimal satu aroma.';
                if (!this.acara) this.galat.acara = 'Pilih acaranya.';
                const pertama = Object.keys(this.galat)[0];
                if (pertama) {
                    this.$nextTick(() => this.$refs['f-' + pertama]?.focus());
                    return;
                }
                if (!wa) return;
                const pesan = [
                    'Halo Angelia Perfume, saya mau dibantu pilih parfum.',
                    '',
                    'Nama: ' + this.nama.trim(),
                    'Gender: ' + this.gender,
                    'Aroma: ' + this.aroma.join(', '),
                    'Acara: ' + this.acara,
                    '',
                    '(dikirim dari form di ' + location.host + ')',
                ].join('\n');
                window.open(wa + '?text=' + encodeURIComponent(pesan), '_blank', 'noopener');
            },
        }));
    });
</script>

{{-- z-20 saat tertutup: di bawah header (z-30) dan panel filter katalog di HP (z-30); z-40 saat panel terbuka --}}
<div x-data="tanyaAngelia(@js($wa))" @keydown.escape.window="buka && tutup()" :class="buka ? 'z-40' : 'z-20'"
     class="fixed bottom-4 right-4 z-20 flex flex-col items-end gap-3 print:hidden sm:bottom-6 sm:right-6">
    {{-- Panel melayang di atas isi halaman, jadi diberi bayangan --}}
    <section id="tanya-angelia" x-show="buka" x-cloak role="dialog" aria-labelledby="tanya-judul"
             x-transition:enter="motion-safe:transition motion-safe:duration-200 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="motion-safe:transition motion-safe:duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="flex max-h-[calc(100dvh-7.5rem)] w-[min(380px,calc(100vw-2rem))] flex-col overflow-hidden rounded-3xl border border-dusty-pink/40 bg-white shadow-xl">
        <header class="flex shrink-0 items-center gap-3 bg-petal py-3 pl-5 pr-2">
            <img src="{{ asset('apple-touch-icon.png') }}" alt="" width="44" height="44" class="h-11 w-11 shrink-0 rounded-full border border-dusty-pink/40">
            <div class="min-w-0 flex-1">
                <h2 id="tanya-judul" x-ref="judul" tabindex="-1" class="font-display text-xl leading-tight focus:outline-none">Tanya Angelia</h2>
                <p class="text-sm text-ink-soft">Konsultasi parfum lewat WhatsApp</p>
            </div>
            <button type="button" @click="tutup()" aria-label="Tutup" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-white/70">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </header>

        <div class="overflow-y-auto">
            {{-- Langkah 1: pilih cara --}}
            <div x-show="langkah === 'pilih'" class="flex flex-col gap-3 p-5">
                <p class="text-[17px] font-semibold leading-snug">Hai! Mau langsung chat, atau dibantu pilih parfum dulu?</p>
                @if ($wa)
                    <a href="{{ \App\Support\WhatsApp::link('Halo Angelia Perfume, saya mau tanya parfum.') }}" target="_blank" rel="noopener"
                       class="flex min-h-[64px] items-center gap-3 rounded-2xl border border-dusty-pink/60 px-4 py-3 transition-colors hover:bg-cream">
                        <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l1-4.2A8 8 0 1 1 20 12Z"/></svg>
                        <span class="flex flex-col">
                            <span class="font-semibold">Chat langsung di WhatsApp</span>
                            <span class="text-sm text-ink-soft">Admin Angelia Perfume · {{ \App\Support\WhatsApp::display() }}</span>
                        </span>
                    </a>
                @else
                    <p class="card-dashed px-4 py-3 text-[15px]">Nomor WhatsApp toko: [NOMOR TOKO]</p>
                @endif
                <button type="button" @click="keForm()"
                        class="flex min-h-[64px] items-center gap-3 rounded-2xl border border-dusty-pink/60 px-4 py-3 text-left transition-colors hover:bg-cream">
                    {{-- Botol parfum --}}
                    <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M10 3h4v3h-4zM9 6h6v2.5a5 5 0 0 1 3 4.6V19a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-5.9a5 5 0 0 1 3-4.6Z"/></svg>
                    <span class="flex flex-col">
                        <span class="font-semibold">Bantu pilih parfum</span>
                        <span class="text-sm text-ink-soft">Isi 4 pertanyaan singkat, lalu lanjut ke WhatsApp</span>
                    </span>
                </button>
            </div>

            {{-- Langkah 2: form singkat --}}
            <form x-show="langkah === 'form'" x-cloak @submit.prevent="kirim()" novalidate class="flex flex-col gap-5 p-5">
                <button type="button" @click="kembali()" class="-ml-2 inline-flex min-h-[44px] items-center gap-1.5 self-start rounded-full px-2 text-sm font-semibold hover:underline">
                    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg>
                    Kembali
                </button>
                <p class="-mt-3 text-[15px] text-ink-soft">Lengkapi isian ini, lalu lanjut ngobrol dengan admin di WhatsApp.</p>

                <div class="flex flex-col gap-1.5">
                    <label for="tanya-nama" class="font-semibold">Nama</label>
                    <input id="tanya-nama" x-ref="f-nama" type="text" x-model="nama" autocomplete="name" placeholder="Nama kamu"
                           :aria-invalid="galat.nama ? 'true' : 'false'" aria-describedby="tanya-galat-nama"
                           class="h-12 rounded-xl border-dusty-pink px-4 text-base focus:border-ink focus:ring-0">
                    <p id="tanya-galat-nama" x-show="galat.nama" x-text="galat.nama" class="text-sm font-medium text-red-700"></p>
                </div>

                <fieldset class="flex flex-col gap-2" aria-describedby="tanya-galat-gender">
                    <legend class="pb-2 font-semibold">Gender</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($genders as $g)
                            <label><input type="radio" name="tanya-gender" value="{{ $g }}" x-model="gender" class="peer sr-only" @if ($loop->first) x-ref="f-gender" @endif><span class="{{ $chip }}">{{ $g }}</span></label>
                        @endforeach
                    </div>
                    <p id="tanya-galat-gender" x-show="galat.gender" x-text="galat.gender" class="text-sm font-medium text-red-700"></p>
                </fieldset>

                <fieldset class="flex flex-col gap-2" aria-describedby="tanya-galat-aroma">
                    <legend class="pb-2 font-semibold">Aroma <span class="font-normal text-ink-soft">(boleh lebih dari satu)</span></legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($aromas as $a)
                            <label><input type="checkbox" value="{{ $a }}" x-model="aroma" class="peer sr-only" @if ($loop->first) x-ref="f-aroma" @endif><span class="{{ $chip }}">{{ $a }}</span></label>
                        @endforeach
                    </div>
                    <p id="tanya-galat-aroma" x-show="galat.aroma" x-text="galat.aroma" class="text-sm font-medium text-red-700"></p>
                </fieldset>

                <fieldset class="flex flex-col gap-2" aria-describedby="tanya-galat-acara">
                    <legend class="pb-2 font-semibold">Acara</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($acaras as $o)
                            <label><input type="radio" name="tanya-acara" value="{{ $o }}" x-model="acara" class="peer sr-only" @if ($loop->first) x-ref="f-acara" @endif><span class="{{ $chip }}">{{ $o }}</span></label>
                        @endforeach
                    </div>
                    <p id="tanya-galat-acara" x-show="galat.acara" x-text="galat.acara" class="text-sm font-medium text-red-700"></p>
                </fieldset>

                @if ($wa)
                    <button type="submit" class="btn-primary w-full">Mulai ngobrol</button>
                @else
                    <p class="card-dashed px-4 py-3 text-[15px]">Nomor WhatsApp toko: [NOMOR TOKO]</p>
                @endif
                <p class="-mt-2 text-[13px] text-ink-soft">Jawabanmu tidak disimpan di website, hanya dikirim sebagai pesan WhatsApp.</p>
            </form>
        </div>
    </section>

    {{-- Tombol bulat melayang di atas isi halaman, jadi diberi bayangan; tidak berdenyut --}}
    <button type="button" x-ref="tombol" @click="bukaTutup()" :aria-expanded="buka.toString()" aria-expanded="false" aria-controls="tanya-angelia"
            aria-label="Tanya Angelia Perfume"
            class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-cream shadow-lg ring-1 ring-dusty-pink/50 transition-transform duration-200 motion-safe:hover:scale-105">
        <img x-show="!buka" src="{{ asset('apple-touch-icon.png') }}" alt="" width="64" height="64" class="h-full w-full object-cover">
        <svg x-show="buka" x-cloak aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
</div>
