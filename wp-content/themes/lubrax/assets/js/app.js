/*!
 * Lubrax · app.js — núcleo (todas las páginas)
 * JavaScript vanilla. Requiere bootstrap.bundle.min.js (defer, antes).
 *
 * IMPORTANTE: este archivo NO lee datos del mock. Trabaja solo con el DOM
 * (atributos data-*), así funciona igual cuando el CMS imprima el marcado.
 *
 * API pública (window.LBX):
 *   LBX.distributor.get()      -> distribuidor activo o null
 *   LBX.distributor.region()   -> región activa ('' = todas)
 *   LBX.distributor.scope()    -> { type: 'distributor'|'region'|'all', ids, region, label, name }
 *   LBX.distributor.set(id) · .setRegion(nombre) · .clear()  -> cambian y emiten 'lbx:distributorchange'
 *   LBX.distributor.list()     -> distribuidores disponibles (desde el <select> del modal)
 *   LBX.toast(mensaje)
 *   LBX.reveal(contenedor)     -> activa animaciones de scroll en contenido nuevo
 *   LBX.tooltips(contenedor)   -> activa tooltips de Bootstrap en contenido nuevo
 *   LBX.pagination(nav, { page, pages, onChange })  -> paginador del catálogo y del blog
 *   LBX.utils                  -> utilidades compartidas
 */
(() => {
  'use strict';

  const LBX = (window.LBX = window.LBX || {});
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.documentElement.classList.add('js');

  /* ------------------------------------------------------------------
   * Utilidades
   * ------------------------------------------------------------------ */
  const store = {
    get(k) { try { return localStorage.getItem(k); } catch { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch { /* sin persistencia */ } },
    remove(k) { try { localStorage.removeItem(k); } catch { /* sin persistencia */ } }
  };
  const normalize = (v) => String(v ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
  const slug = (v) => normalize(v).replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const haversineKm = (a, b) => {
    const R = 6371, rad = (d) => (d * Math.PI) / 180;
    const h = Math.sin(rad(b.lat - a.lat) / 2) ** 2 + Math.cos(rad(a.lat)) * Math.cos(rad(b.lat)) * Math.sin(rad(b.lng - a.lng) / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
  };
  const formatKm = (km) => `${km.toLocaleString('es-CL', { maximumFractionDigits: km < 10 ? 1 : 0 })} km`;
  const params = () => new URLSearchParams(window.location.search);
  const geolocate = () => new Promise((resolve, reject) => {
    if (!('geolocation' in navigator)) { reject(new Error('sin geolocalización')); return; }
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => resolve({ lat: coords.latitude, lng: coords.longitude }),
      reject,
      { enableHighAccuracy: false, timeout: 9000, maximumAge: 600000 }
    );
  });
  LBX.utils = { $, $$, store, normalize, slug, escapeHtml, haversineKm, formatKm, params, geolocate, reduceMotion };

  /* ------------------------------------------------------------------
   * Toasts
   * ------------------------------------------------------------------ */
  LBX.toast = (message) => {
    if (!window.bootstrap) return;
    let box = $('#toastContainer');
    if (!box) {
      box = document.createElement('div');
      box.id = 'toastContainer';
      box.className = 'toast-container position-fixed bottom-0 start-50 translate-middle-x p-3';
      document.body.append(box);
    }
    const el = document.createElement('div');
    el.className = 'toast align-items-center text-bg-dark border-0';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>';
    el.querySelector('.toast-body').textContent = message;
    box.append(el);
    el.addEventListener('hidden.bs.toast', () => el.remove());
    new bootstrap.Toast(el, { delay: 3500 }).show();
  };

  /* ------------------------------------------------------------------
   * 1. Zona activa del header: un distribuidor, una región completa o "Todos"
   *    Fuente: <select id="distributorSelect"> con <option> que traen
   *    data-label, data-region, data-lat, data-lng (los imprime el CMS).
   *    Se recuerda en localStorage: lbx.distributor (id) o lbx.region (nombre).
   * ------------------------------------------------------------------ */
  const DIST_KEY = 'lbx.distributor';
  const REGION_KEY = 'lbx.region';
  const readDistributors = () => $$('#distributorSelect option[value]').filter((o) => o.value).map((o) => ({
    id: o.value,
    name: o.textContent.trim(),
    label: o.dataset.label || o.textContent.trim(),
    region: o.dataset.region || '',
    lat: parseFloat(o.dataset.lat),
    lng: parseFloat(o.dataset.lng)
  }));
  let distributors = readDistributors();
  const findDist = (id) => distributors.find((d) => d.id === id) || null;
  const validRegion = (r) => (r && distributors.some((d) => d.region === r) ? r : '');
  let currentDist = null;
  let currentRegion = '';
  const restore = () => {
    currentDist = findDist(store.get(DIST_KEY));
    currentRegion = currentDist ? currentDist.region : validRegion(store.get(REGION_KEY));
  };
  restore();

  /* Alcance actual: qué distribuidores cuentan para el catálogo y la disponibilidad */
  const scope = () => {
    if (currentDist) {
      return { type: 'distributor', id: currentDist.id, region: currentDist.region, label: currentDist.label, name: currentDist.name, ids: [currentDist.id] };
    }
    if (currentRegion) {
      return { type: 'region', id: '', region: currentRegion, label: currentRegion, name: `la región ${currentRegion}`, ids: distributors.filter((d) => d.region === currentRegion).map((d) => d.id) };
    }
    return { type: 'all', id: '', region: '', label: 'Todos', name: 'todos los distribuidores', ids: [] };
  };

  const renderDistributor = () => {
    const s = scope();
    $$('[data-dist-name]').forEach((el) => { el.textContent = s.label; });
    $$('[data-dist-fullname]').forEach((el) => { el.textContent = s.name; });
    $$('[data-dist-label]').forEach((el) => { el.textContent = s.type === 'region' ? 'Región' : 'Distribuidor'; });
    document.documentElement.dataset.distributor = s.id;
    document.documentElement.dataset.region = s.region;
  };
  const commit = () => {
    if (currentDist) store.set(DIST_KEY, currentDist.id); else store.remove(DIST_KEY);
    if (currentRegion && !currentDist) store.set(REGION_KEY, currentRegion); else store.remove(REGION_KEY);
    renderDistributor();
    document.dispatchEvent(new CustomEvent('lbx:distributorchange', { detail: scope() }));
  };

  LBX.distributor = {
    get: () => currentDist,                    // distribuidor elegido o null
    region: () => currentRegion,               // región activa ('' = todas)
    scope,                                     // { type: 'distributor'|'region'|'all', ids, label, … }
    list: () => distributors.slice(),
    set(id) { currentDist = findDist(id); currentRegion = currentDist ? currentDist.region : ''; commit(); },
    setRegion(region) { currentDist = null; currentRegion = validRegion(region); commit(); },
    clear() { currentDist = null; currentRegion = ''; commit(); },
    refresh() { distributors = readDistributors(); restore(); renderDistributor(); }
  };
  const scopeMessage = () => {
    const s = scope();
    return s.type === 'all' ? 'Mostrando el catálogo completo.' : `Mostrando productos disponibles en ${s.type === 'region' ? `la región ${s.region}` : s.label}.`;
  };

  const initDistributorModal = () => {
    const modalEl = $('#distributorModal');
    if (!modalEl || !window.bootstrap) return;
    const form = $('form', modalEl);
    const regionSel = $('#distributorRegion', modalEl);
    const distSel = $('#distributorSelect', modalEl);
    const status = $('[data-dist-geo-status]', modalEl);
    const allOptions = $$('option[value]', distSel).filter((o) => o.value).map((o) => o.cloneNode(true));

    const regions = [...new Set(allOptions.map((o) => o.dataset.region).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es'));
    regionSel.replaceChildren(new Option('Todas las regiones', ''), ...regions.map((r) => new Option(r, r)));

    /* Con una región elegida, la primera opción significa "toda la región" */
    const fillDistributors = (region) => {
      const first = new Option(region ? `Todos los distribuidores de ${region}` : 'Todos los distribuidores', '');
      distSel.replaceChildren(first, ...allOptions.filter((o) => !region || o.dataset.region === region).map((o) => o.cloneNode(true)));
    };
    const preselect = (region, id) => {
      regionSel.value = region || '';
      fillDistributors(regionSel.value);
      distSel.value = id || '';
    };

    modalEl.addEventListener('show.bs.modal', () => { status.textContent = ''; preselect(currentRegion, currentDist?.id); });
    regionSel.addEventListener('change', () => fillDistributors(regionSel.value));
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      if (distSel.value) LBX.distributor.set(distSel.value);
      else if (regionSel.value) LBX.distributor.setRegion(regionSel.value);
      else LBX.distributor.clear();
      bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      LBX.toast(scopeMessage());
    });
    $('[data-dist-clear]', modalEl)?.addEventListener('click', () => {
      LBX.distributor.clear();
      bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      LBX.toast(scopeMessage());
    });
    $('[data-dist-geolocate]', modalEl)?.addEventListener('click', async () => {
      status.textContent = 'Buscando tu ubicación…';
      try {
        const pos = await geolocate();
        const near = distributors.filter((d) => !Number.isNaN(d.lat))
          .map((d) => ({ ...d, km: haversineKm(pos, d) })).sort((a, b) => a.km - b.km)[0];
        if (!near) return;
        preselect(near.region, near.id);
        status.textContent = `El más cercano es ${near.label} (a ${formatKm(near.km)}). Confirma para continuar.`;
      } catch {
        status.textContent = 'No pudimos obtener tu ubicación. Elige la región manualmente.';
      }
    });
  };

  /* ------------------------------------------------------------------
   * 2. Header: estado sólido al hacer scroll + volver arriba
   * ------------------------------------------------------------------ */
  const initScrollUi = () => {
    const header = $('.site-header');
    const toTop = $('#backToTop');
    let ticking = false;
    const update = () => {
      const y = window.scrollY;
      header?.classList.toggle('is-solid', y > 40);
      toTop?.classList.toggle('is-visible', y > 700);
      ticking = false;
    };
    window.addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(update); ticking = true; } }, { passive: true });
    update();
    toTop?.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
      $('#main')?.focus({ preventScroll: true });
    });
  };

  /* ------------------------------------------------------------------
   * 3. Animaciones con scroll
   *    data-reveal="up|down|left|right|zoom|clip|title"
   *    data-reveal-delay="0.2"   ·   data-reveal-stagger (en el padre)
   * ------------------------------------------------------------------ */
  let revealObserver = null;
  const reveal = (root = document) => {
    $$('[data-reveal-stagger]', root).forEach((group) => {
      const step = parseFloat(group.dataset.revealStagger) || 0.08;
      $$(':scope > [data-reveal], :scope > * > [data-reveal]', group).forEach((el, i) => {
        if (!el.style.getPropertyValue('--reveal-delay')) el.style.setProperty('--reveal-delay', `${(i % 8) * step}s`);
      });
    });
    $$('[data-reveal-delay]', root).forEach((el) => el.style.setProperty('--reveal-delay', `${el.dataset.revealDelay}s`));
    $$('[data-reveal="title"]', root).forEach((el) => $$('.line', el).forEach((line, i) => line.style.setProperty('--line-i', i)));
    const targets = $$('[data-reveal]:not(.is-visible)', root);
    if (reduceMotion || !('IntersectionObserver' in window)) { targets.forEach((el) => el.classList.add('is-visible')); return; }
    if (!revealObserver) {
      revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) { entry.target.classList.add('is-visible'); revealObserver.unobserve(entry.target); }
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    }
    targets.forEach((el) => revealObserver.observe(el));
  };
  LBX.reveal = reveal;

  /* Parallax suave: data-parallax="0.15" (fracción del desplazamiento) */
  const initParallax = () => {
    const items = $$('[data-parallax]');
    if (!items.length || reduceMotion) return;
    const visible = new Set();
    const io = new IntersectionObserver((entries) => entries.forEach((e) => (e.isIntersecting ? visible.add(e.target) : visible.delete(e.target))));
    items.forEach((el) => io.observe(el));
    let ticking = false;
    const update = () => {
      const vh = window.innerHeight;
      visible.forEach((el) => {
        const r = el.getBoundingClientRect();
        const progress = (r.top + r.height / 2 - vh / 2) / vh;
        el.style.transform = `translate3d(0, ${(-progress * parseFloat(el.dataset.parallax) * 100).toFixed(2)}px, 0)`;
      });
      ticking = false;
    };
    window.addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(update); ticking = true; } }, { passive: true });
    update();
  };

  /* Contadores: data-count-to="180" data-count-prefix="+" */
  const initCounters = () => {
    const items = $$('[data-count-to]');
    if (!items.length) return;
    const run = (el) => {
      const to = parseFloat(el.dataset.countTo);
      const prefix = el.dataset.countPrefix || '';
      if (reduceMotion) { el.textContent = prefix + to.toLocaleString('es-CL'); return; }
      const start = performance.now(), dur = 1600;
      const tick = (now) => {
        const t = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = prefix + Math.round(to * eased).toLocaleString('es-CL');
        if (t < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    const io = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } }), { threshold: 0.6 });
    items.forEach((el) => io.observe(el));
  };

  /* ------------------------------------------------------------------
   * 4. Formularios: validación Bootstrap + evento cancelable
   *    Al ser válido se emite 'lbx:formsubmit' (detail: { form, data }).
   *    Si nadie lo cancela, el formulario se envía a su "action".
   * ------------------------------------------------------------------ */
  const initForms = () => {
    $$('form.needs-validation').forEach((form) => {
      form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
          form.classList.add('was-validated');
          form.querySelector(':invalid')?.focus();
          return;
        }
        form.classList.add('was-validated');
        const ev = new CustomEvent('lbx:formsubmit', { cancelable: true, detail: { form, data: Object.fromEntries(new FormData(form)) } });
        if (!document.dispatchEvent(ev)) e.preventDefault();
      });
    });
    $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form?.requestSubmit()));
  };

  /* Buscador dentro del acordeón de preguntas frecuentes */
  const initFaqSearch = () => {
    const input = $('[data-faq-search]');
    if (!input) return;
    const list = $(input.dataset.faqSearch);
    const empty = $('[data-faq-empty]');
    input.addEventListener('input', () => {
      const q = normalize(input.value);
      let shown = 0;
      $$('.accordion-item', list).forEach((item) => {
        const hit = !q || normalize(item.textContent).includes(q);
        item.hidden = !hit;
        if (hit) shown++;
      });
      if (empty) empty.hidden = shown > 0;
    });
  };

  /* ------------------------------------------------------------------
   * 5. Tooltips de Bootstrap: data-bs-toggle="tooltip" data-bs-title="…"
   *    (se mantiene el aria-label del elemento para lectores de pantalla)
   * ------------------------------------------------------------------ */
  const initTooltips = (root = document) => {
    if (!window.bootstrap) return;
    $$('[data-bs-toggle="tooltip"]', root).forEach((el) => bootstrap.Tooltip.getOrCreateInstance(el, { container: 'body' }));
  };
  LBX.tooltips = initTooltips;
  /* En pantallas táctiles el tooltip se abre con el toque: se cierra al activar el botón */
  document.addEventListener('click', (e) => {
    const el = e.target.closest?.('[data-bs-toggle="tooltip"]');
    if (el && window.bootstrap) bootstrap.Tooltip.getInstance(el)?.hide();
  });

  /* ------------------------------------------------------------------
   * 6. Visor de PDF en modal (#pdfModal)
   *    <a href="ficha.pdf" data-pdf-view data-pdf-title="Ficha técnica"
   *       data-pdf-meta="PDF · 216 KB" data-pdf-thumb="miniatura.webp" data-pdf-product="…">
   *    - Escritorio: visor nativo del navegador en un <iframe> (funciona también
   *      abriendo los archivos sin servidor).
   *    - Android, iOS o navegadores configurados para descargar los PDF: no pueden
   *      mostrarlo dentro de la página, así que se ofrece abrirlo o descargarlo.
   *    - Sin JavaScript (o con Ctrl/Cmd + clic) el enlace abre el PDF en otra pestaña.
   * ------------------------------------------------------------------ */
  const initPdfViewer = () => {
    const modalEl = $('#pdfModal');
    if (!modalEl || !window.bootstrap) return;
    const viewer = $('[data-pdf-viewer]', modalEl);
    const ua = navigator.userAgent;
    const isIOS = /iP(hone|od|ad)/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
    const canEmbed = navigator.pdfViewerEnabled !== false && !isIOS && !/Android/i.test(ua);

    const fallback = (href, title, thumb, file) => {
      const box = document.createElement('div');
      box.className = 'pdf-fallback';
      box.innerHTML = `${thumb ? `<img src="${escapeHtml(thumb)}" alt="" width="320" height="453">` : ''}
        <div>
          <p class="pdf-fallback__title">Vista previa no disponible en este dispositivo</p>
          <p>Tu navegador no muestra documentos PDF dentro de la página. Puedes abrir «${escapeHtml(title)}» a pantalla completa o descargarlo.</p>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-lbx btn-sm" href="${escapeHtml(href)}" target="_blank" rel="noopener">Abrir PDF</a>
            <a class="btn btn-lbx-outline btn-sm" href="${escapeHtml(href)}" download="${escapeHtml(file)}">Descargar</a>
          </div>
        </div>`;
      return box;
    };

    document.addEventListener('click', (e) => {
      const link = e.target.closest('a[data-pdf-view]');
      if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      const href = link.getAttribute('href');
      const title = link.dataset.pdfTitle || 'Documento';
      const file = decodeURIComponent(href.split('/').pop().split(/[?#]/)[0]);
      $('[data-pdf-title]', modalEl).textContent = title;
      $('[data-pdf-meta]', modalEl).textContent = link.dataset.pdfMeta || 'PDF';
      const product = $('[data-pdf-product]', modalEl);
      if (product) { product.textContent = link.dataset.pdfProduct || ''; product.hidden = !link.dataset.pdfProduct; }
      $$('[data-pdf-download]', modalEl).forEach((a) => { a.href = href; a.setAttribute('download', file); });
      $$('[data-pdf-open]', modalEl).forEach((a) => { a.href = href; });
      viewer.classList.toggle('is-fallback', !canEmbed);
      if (canEmbed) {
        const frame = document.createElement('iframe');
        frame.className = 'pdf-viewer__frame';
        frame.title = `Vista previa: ${title}`;
        frame.src = `${href}#view=FitH`;
        viewer.replaceChildren(frame);
      } else {
        viewer.replaceChildren(fallback(href, title, link.dataset.pdfThumb, file));
      }
      bootstrap.Modal.getOrCreateInstance(modalEl).show(link);
    });
    /* Al cerrar se descarga el documento de la memoria */
    modalEl.addEventListener('hidden.bs.modal', () => viewer.replaceChildren());
  };

  /* ------------------------------------------------------------------
   * 7. Paginación compartida (catálogo y blog)
   *    LBX.pagination(nav, { page, pages, onChange })
   *    Si el CMS pagina en el servidor, debe imprimir este mismo marcado con
   *    <a class="page-link" href="?pagina=2"> en vez de <button>.
   * ------------------------------------------------------------------ */
  const pageList = (cur, total) => {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const from = Math.max(2, cur - 1);
    const to = Math.min(total - 1, cur + 1);
    const out = [1];
    if (from > 2) out.push('…');
    for (let i = from; i <= to; i++) out.push(i);
    if (to < total - 1) out.push('…');
    out.push(total);
    return out;
  };
  LBX.pagination = (nav, { page, pages, onChange }) => {
    if (!nav) return;
    if (pages < 2) { nav.replaceChildren(); return; }
    const ul = document.createElement('ul');
    ul.className = 'pagination justify-content-center flex-wrap';
    const item = (cls = '') => { const li = document.createElement('li'); li.className = `page-item${cls}`; ul.append(li); return li; };
    const add = (html, target, { current = false, disabled = false, aria = '' } = {}) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'page-link';
      b.innerHTML = html;
      if (aria) b.setAttribute('aria-label', aria);
      if (current) b.setAttribute('aria-current', 'page');
      b.disabled = disabled;
      b.addEventListener('click', () => onChange(target));
      item(`${current ? ' active' : ''}${disabled ? ' disabled' : ''}`).append(b);
    };
    add('<svg class="icon" aria-hidden="true"><use href="#i-chevron-left"></use></svg>', page - 1, { disabled: page === 1, aria: 'Página anterior' });
    pageList(page, pages).forEach((n) => {
      if (n === '…') item(' disabled').innerHTML = '<span class="page-link" aria-hidden="true">…</span>';
      else add(String(n), n, { current: n === page, aria: `Página ${n}` });
    });
    add('<svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>', page + 1, { disabled: page === pages, aria: 'Página siguiente' });
    nav.replaceChildren(ul);
  };

  /* ------------------------------------------------------------------
   * 8. Páginas legales: el índice resalta la sección visible y en el
   *    teléfono empieza plegado.   <nav class="legal-toc" data-legal-toc>
   * ------------------------------------------------------------------ */
  const initLegalToc = () => {
    const toc = $('[data-legal-toc]');
    if (!toc) return;
    const details = $('details', toc);
    const small = window.matchMedia('(max-width: 575.98px)');
    if (details && small.matches) details.open = false;
    const links = $$('a[href^="#"]', toc);
    links.forEach((a) => a.addEventListener('click', () => { if (details && small.matches) details.open = false; }));
    if (!('IntersectionObserver' in window)) return;
    const byId = new Map(links.map((a) => [decodeURIComponent(a.hash.slice(1)), a]));
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (!en.isIntersecting) return;
      links.forEach((l) => { l.classList.remove('is-active'); l.removeAttribute('aria-current'); });
      const link = byId.get(en.target.id);
      if (link) { link.classList.add('is-active'); link.setAttribute('aria-current', 'location'); }
    }), { rootMargin: '-30% 0px -60% 0px' });
    byId.forEach((_, id) => { const section = document.getElementById(id); if (section) io.observe(section); });
  };

  /* "Volver atrás" (página 404): regresa a la página anterior del sitio; si se
     llegó desde otro sitio o escribiendo la URL, sigue el href (inicio). */
  const initBackLinks = () => {
    $$('[data-back]').forEach((el) => el.addEventListener('click', (e) => {
      let sameSite = false;
      try { sameSite = !!document.referrer && new URL(document.referrer).origin === window.location.origin; } catch { sameSite = false; }
      if (sameSite && window.history.length > 1) { e.preventDefault(); window.history.back(); }
    }));
  };

  /* Cierra el menú lateral al navegar a un ancla de la misma página */
  const initMenu = () => {
    const panel = $('#menuPanel');
    if (!panel || !window.bootstrap) return;
    $$('a[href^="#"]', panel).forEach((a) => a.addEventListener('click', () => bootstrap.Offcanvas.getOrCreateInstance(panel).hide()));
  };

  /* ------------------------------------------------------------------
   * Arranque
   * ------------------------------------------------------------------ */
  renderDistributor();
  initDistributorModal();
  initScrollUi();
  initForms();
  initFaqSearch();
  initMenu();
  initTooltips();
  initPdfViewer();
  initLegalToc();
  initBackLinks();
  reveal();
  initParallax();
  initCounters();
  $$('[data-year]').forEach((el) => { el.textContent = String(new Date().getFullYear()); });
})();
