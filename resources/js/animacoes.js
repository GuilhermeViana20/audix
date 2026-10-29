// Animações de entrada: elementos com data-reveal sobem e aparecem ao entrar na tela.
// Elementos dentro de data-stagger entram um depois do outro.
const reduzir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function preparar() {
    document.querySelectorAll('[data-stagger]').forEach((pai) => {
        [...pai.children].forEach((filho, i) => {
            filho.setAttribute('data-reveal', '');
            filho.style.setProperty('--d', Math.min(i, 12) * 70 + 'ms');
        });
    });
}

function observar() {
    const elementos = document.querySelectorAll('[data-reveal]');
    if (reduzir || !('IntersectionObserver' in window)) {
        elementos.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const observador = new IntersectionObserver((entradas) => {
        entradas.forEach((entrada) => {
            if (entrada.isIntersecting) {
                entrada.target.classList.add('is-visible');
                observador.unobserve(entrada.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    elementos.forEach((el) => observador.observe(el));
}

document.addEventListener('DOMContentLoaded', () => {
    preparar();
    observar();
});
