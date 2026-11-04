import './bootstrap';

// Alpine tidak diimpor di sini: Livewire (@livewireScripts) sudah membawa Alpine,
// dan dua salinan Alpine di satu halaman saling bentrok.

// Animasi muncul saat di-scroll: elemen [data-reveal] tampil sekali ketika masuk layar.
// Dilewati bila browser tidak mendukung atau pengguna memilih "reduced motion".
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if ('IntersectionObserver' in window && ! reducedMotion) {
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        }
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 });

    document.documentElement.classList.add('js-reveal');
    document.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));
}

// Video hero Beranda (permintaan klien). Tidak diputar bila pengguna memilih "reduced motion" atau mode
// hemat data: yang tampil hanya poster. Sambungan akhir-ke-awal klip tidak mulus, jadi setiap putaran
// dipudarkan (crossfade) ke salinan kedua yang mulai dari awal. Video berhenti saat hero tidak terlihat.
const heroVideo = document.querySelector('[data-hero-video] video');
const saveData = navigator.connection?.saveData === true;

if (heroVideo && ! reducedMotion && ! saveData && 'IntersectionObserver' in window) {
    const FADE_SECONDS = 1.2; // sama dengan duration-[1200ms] pada elemen video
    const twin = heroVideo.cloneNode(true);
    twin.removeAttribute('poster');
    twin.style.opacity = '0';
    heroVideo.after(twin);

    let current = heroVideo;
    let next = twin;
    let fading = false;
    let visible = false;

    const crossfade = () => {
        fading = true;
        next.currentTime = 0;
        next.play().catch(() => {});
        next.style.opacity = '1';
        current.style.opacity = '0';
        setTimeout(() => {
            current.pause();
            [current, next] = [next, current];
            fading = false;
        }, FADE_SECONDS * 1000);
    };

    for (const video of [heroVideo, twin]) {
        video.muted = true;
        video.addEventListener('timeupdate', () => {
            if (video === current && ! fading && video.duration && video.currentTime >= video.duration - FADE_SECONDS) {
                crossfade();
            }
        });
        video.addEventListener('ended', () => {
            if (video === current && ! fading) {
                crossfade();
            }
        });
    }

    // Salinan kedua baru dimuat setelah video pertama berjalan (berkasnya sudah ada di cache)
    heroVideo.addEventListener('playing', () => { twin.preload = 'auto'; }, { once: true });

    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        if (visible) {
            current.play().catch(() => {});
        } else {
            current.pause();
        }
    }, { threshold: 0.15 }).observe(heroVideo.parentElement);
}
