{{-- Mawar cat air di keempat sudut hero (permintaan pemilik, mengikuti contoh mawar pink bergaris putih).
     Digambar sebagai SVG: tajam di semua layar, beberapa KB, tanpa foto berlisensi.
     Satu mawar = tiga lapis kelopak bergradasi (gelap di pusat, pucat di tepi) dengan garis putih;
     filter feDisplacementMap membuat tepi kelopak sedikit bergelombang seperti sapuan kuas. --}}
<svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
    <defs>
        <radialGradient id="mawar-luar" cx="0" cy="0" r="96" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#E48A9A"/>
            <stop offset=".55" stop-color="#F5BCC3"/>
            <stop offset="1" stop-color="#FFEAEC"/>
        </radialGradient>
        <radialGradient id="mawar-tengah" cx="0" cy="0" r="66" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#D97B8D"/>
            <stop offset="1" stop-color="#F8CDD2"/>
        </radialGradient>
        <radialGradient id="mawar-dalam" cx="0" cy="0" r="40" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#C8687C"/>
            <stop offset="1" stop-color="#F1B2BB"/>
        </radialGradient>
        <filter id="mawar-cat-air" x="-15%" y="-15%" width="130%" height="130%">
            <feTurbulence type="fractalNoise" baseFrequency="0.035" numOctaves="2" seed="7" result="noise"/>
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="7" xChannelSelector="R" yChannelSelector="G"/>
        </filter>
        <path id="kelopak-luar" d="M0 0C-52 -30 -62 -86 0 -95C62 -86 52 -30 0 0Z"/>
        <path id="kelopak-tengah" d="M0 0C-38 -20 -46 -60 0 -67C46 -60 38 -20 0 0Z"/>
        <path id="kelopak-dalam" d="M0 0C-24 -12 -29 -37 0 -41C29 -37 24 -12 0 0Z"/>

        <symbol id="mawar" viewBox="-100 -100 200 200" overflow="visible">
            <g filter="url(#mawar-cat-air)" stroke="#fff" stroke-opacity=".85" stroke-width="1.6" stroke-linejoin="round">
                <g fill="url(#mawar-luar)" fill-opacity=".93">
                    @foreach ([0, 60, 120, 180, 240, 300] as $deg)
                        <use href="#kelopak-luar" transform="rotate({{ $deg }})"/>
                    @endforeach
                </g>
                <g fill="url(#mawar-tengah)" fill-opacity=".92">
                    @foreach ([30, 102, 174, 246, 318] as $deg)
                        <use href="#kelopak-tengah" transform="rotate({{ $deg }})"/>
                    @endforeach
                </g>
                <g fill="url(#mawar-dalam)">
                    @foreach ([15, 105, 195, 285] as $deg)
                        <use href="#kelopak-dalam" transform="rotate({{ $deg }})"/>
                    @endforeach
                </g>
                <circle r="12" fill="#C8687C" fill-opacity=".9"/>
                <path d="M-3 1a4 4 0 1 1 7-1a7.5 7.5 0 1 1-13 2a11 11 0 0 1 10-11" fill="none" stroke-width="1.5"/>
            </g>
        </symbol>

        {{-- Satu rangkaian sudut: mawar besar di pojok, dua mawar sedang, satu kuncup, dan percikan cat --}}
        <symbol id="sudut-mawar" viewBox="0 0 400 400" overflow="visible">
            <use href="#mawar" x="-20" y="-20" width="260" height="260" transform="rotate(12 110 110)"/>
            <use href="#mawar" x="200" y="-5" width="150" height="150" transform="rotate(-24 275 70)"/>
            <use href="#mawar" x="5" y="220" width="130" height="130" transform="rotate(38 70 285)"/>
            <use href="#mawar" x="262" y="172" width="76" height="76" transform="rotate(70 300 210)"/>
            <g fill="#DE9DA9">
                <circle cx="222" cy="168" r="7" fill-opacity=".45"/>
                <circle cx="338" cy="132" r="4" fill-opacity=".5"/>
                <circle cx="168" cy="258" r="5" fill-opacity=".4"/>
                <circle cx="236" cy="296" r="3" fill-opacity=".5"/>
                <circle cx="372" cy="46" r="6" fill-opacity=".35"/>
                <circle cx="120" cy="348" r="4" fill-opacity=".4"/>
            </g>
            <g fill="#fff">
                <circle cx="200" cy="215" r="5" fill-opacity=".7"/>
                <circle cx="300" cy="275" r="3" fill-opacity=".7"/>
                <circle cx="90" cy="215" r="3" fill-opacity=".6"/>
            </g>
        </symbol>
    </defs>
</svg>

@php($sudut = 'pointer-events-none absolute -z-10 w-[190px] sm:w-[250px] lg:w-[340px] xl:w-[380px]')
<svg viewBox="0 0 400 400" aria-hidden="true" focusable="false" class="{{ $sudut }} -left-10 top-2 sm:-left-12 sm:-top-12"><use href="#sudut-mawar"/></svg>
<svg viewBox="0 0 400 400" aria-hidden="true" focusable="false" class="{{ $sudut }} -right-10 top-2 -scale-x-100 sm:-right-12 sm:-top-12"><use href="#sudut-mawar"/></svg>
<svg viewBox="0 0 400 400" aria-hidden="true" focusable="false" class="{{ $sudut }} -bottom-10 -left-14 -scale-y-100 sm:-bottom-12 sm:-left-12"><use href="#sudut-mawar"/></svg>
<svg viewBox="0 0 400 400" aria-hidden="true" focusable="false" class="{{ $sudut }} -bottom-10 -right-14 rotate-180 sm:-bottom-12 sm:-right-12"><use href="#sudut-mawar"/></svg>
