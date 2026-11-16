/*!
 * Lubrax · slider.js — slider principal del home (sin dependencias)
 *
 * Marcado esperado:
 *   <section class="hero" data-hero data-interval="7000">
 *     <div class="hero__track"> <article class="hero-slide">…</article> … </div>
 *     [data-hero-current] [data-hero-next-num] [data-hero-prev] [data-hero-next] [data-hero-toggle]
 *   </section>
 *
 * Accesibilidad (WAI-ARIA APG "Carousel"):
 *   - Botón de pausa visible; se pausa con hover, foco o pestaña oculta.
 *   - Diapositivas inactivas con aria-hidden + inert.
 *   - Flechas del teclado cuando el foco está dentro; gestos de deslizar en táctil.
 *   - Con "reducir movimiento" no avanza solo.
 */
(() => {
  'use strict';

  const root = document.querySelector('[data-hero]');
  if (!root) return;
  const slides = Array.from(root.querySelectorAll('.hero-slide'));
  if (!slides.length) return;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const interval = parseInt(root.dataset.interval, 10) || 7000;
  const elCurrent = root.querySelector('[data-hero-current]');
  const elNext = root.querySelector('[data-hero-next-num]');
  const btnPrev = root.querySelector('[data-hero-prev]');
  const btnNext = root.querySelector('[data-hero-next]');
  const btnToggle = root.querySelector('[data-hero-toggle]');
  const bar = root.querySelector('.hero-counter__bar span');
  const pad = (n) => String(n).padStart(2, '0');

  let index = Math.max(0, slides.findIndex((s) => s.classList.contains('is-active')));
  let timer = null;
  let remaining = interval;
  let startedAt = 0;
  let userPaused = reduce;           // pausa explícita del usuario (botón)
  let hoverPaused = false;           // pausa temporal (hover / foco / pestaña oculta)

  root.style.setProperty('--hero-interval', `${interval}ms`);

  /* Etiquetas accesibles "1 de N": se calculan solas al agregar o quitar diapositivas */
  slides.forEach((s, n) => {
    s.setAttribute('role', 'group');
    s.setAttribute('aria-roledescription', 'diapositiva');
    s.setAttribute('aria-label', `${n + 1} de ${slides.length}`);
  });
  if (slides.length < 2) root.querySelector('.hero__controls')?.setAttribute('hidden', '');

  const preload = (i) => {
    const img = slides[i]?.querySelector('img[loading="lazy"]');
    if (img) img.loading = 'eager';
  };

  const restartBar = () => {
    if (!bar) return;
    root.classList.remove('is-playing');
    void bar.offsetWidth; // reinicia la animación CSS
    if (!userPaused) root.classList.add('is-playing');
  };

  const go = (i, { user = false } = {}) => {
    index = (i + slides.length) % slides.length;
    slides.forEach((s, n) => {
      const active = n === index;
      s.classList.toggle('is-active', active);
      s.setAttribute('aria-hidden', String(!active));
      s.inert = !active;
    });
    if (elCurrent) elCurrent.textContent = pad(index + 1);
    if (elNext) {
      const next = (index + 1) % slides.length;
      elNext.textContent = pad(next + 1);
      elNext.setAttribute('aria-label', `Ir a la diapositiva ${next + 1} de ${slides.length}`);
    }
    preload((index + 1) % slides.length);
    remaining = interval;
    restartBar();
    schedule();
    if (user) root.querySelector('.hero__track')?.setAttribute('aria-live', 'polite');
  };

  const schedule = () => {
    clearTimeout(timer);
    if (userPaused || hoverPaused || slides.length < 2) return;
    startedAt = performance.now();
    timer = setTimeout(() => go(index + 1), remaining);
  };

  const holdOn = () => {                // pausa temporal conservando el tiempo restante
    if (hoverPaused) return;
    hoverPaused = true;
    clearTimeout(timer);
    remaining = Math.max(400, remaining - (performance.now() - startedAt));
    root.classList.add('is-paused');
  };
  const holdOff = () => {
    if (!hoverPaused) return;
    hoverPaused = false;
    root.classList.remove('is-paused');
    schedule();
  };

  const setUserPaused = (paused) => {
    userPaused = paused;
    if (btnToggle) {
      btnToggle.setAttribute('aria-label', paused ? 'Reproducir el carrusel' : 'Pausar el carrusel');
      btnToggle.querySelector('use')?.setAttribute('href', paused ? '#i-play' : '#i-pause');
    }
    if (paused) { clearTimeout(timer); root.classList.remove('is-playing'); }
    else { remaining = interval; restartBar(); schedule(); }
  };

  btnPrev?.addEventListener('click', () => go(index - 1, { user: true }));
  btnNext?.addEventListener('click', () => go(index + 1, { user: true }));
  elNext?.addEventListener('click', () => go(index + 1, { user: true }));
  btnToggle?.addEventListener('click', () => setUserPaused(!userPaused));

  root.addEventListener('mouseenter', holdOn);
  root.addEventListener('mouseleave', holdOff);
  root.addEventListener('focusin', holdOn);
  root.addEventListener('focusout', (e) => { if (!root.contains(e.relatedTarget)) holdOff(); });
  document.addEventListener('visibilitychange', () => (document.hidden ? holdOn() : holdOff()));

  root.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') { e.preventDefault(); go(index + 1, { user: true }); }
    if (e.key === 'ArrowLeft') { e.preventDefault(); go(index - 1, { user: true }); }
  });

  /* Deslizar en pantallas táctiles */
  let x0 = null, y0 = null;
  root.addEventListener('pointerdown', (e) => { if (e.pointerType !== 'mouse') { x0 = e.clientX; y0 = e.clientY; } }, { passive: true });
  root.addEventListener('pointerup', (e) => {
    if (x0 === null) return;
    const dx = e.clientX - x0, dy = e.clientY - y0;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) go(index + (dx < 0 ? 1 : -1), { user: true });
    x0 = y0 = null;
  }, { passive: true });

  setUserPaused(userPaused);
  go(index);
})();
