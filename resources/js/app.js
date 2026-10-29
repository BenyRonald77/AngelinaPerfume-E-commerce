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
