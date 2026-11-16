/*!
 * Lubrax · product.js — detalle de producto (producto.html)
 *
 * Marcado esperado (lo imprime el CMS):
 *   [data-product-page data-id data-distributors="id1 id2"]
 *   #pdpMainImage                         imagen principal
 *   [data-gallery-src] (botones)          miniaturas
 *   [data-pres][data-pres-image]          presentaciones (cambian la imagen)
 *   [data-avail-box]                      disponibilidad según la zona del header
 *   [data-mini-dist="id"]                 distribuidores que venden el producto
 *   Los documentos PDF se abren en el visor modal de app.js (enlaces con data-pdf-view).
 */
(() => {
  'use strict';

  const page = document.querySelector('[data-product-page]');
  if (!page || !window.LBX) return;
  const { $, $$ } = LBX.utils;
  const dists = (page.dataset.distributors || '').split(/\s+/).filter(Boolean);

  /* --- Galería y presentaciones --- */
  const main = $('#pdpMainImage');
  const swap = (src, alt) => {
    if (!main || !src || main.getAttribute('src') === src) return;
    main.classList.add('is-swapping');
    const img = new Image();
    img.onload = img.onerror = () => {
      main.src = src;
      if (alt) main.alt = alt;
      requestAnimationFrame(() => main.classList.remove('is-swapping'));
    };
    img.src = src;
  };
  const thumbs = $$('[data-gallery-src]');
  const pressThumb = (src) => thumbs.forEach((t) => t.setAttribute('aria-pressed', String(t.dataset.gallerySrc === src)));
  thumbs.forEach((t) => t.addEventListener('click', () => { swap(t.dataset.gallerySrc, t.dataset.galleryAlt); pressThumb(t.dataset.gallerySrc); }));

  const pres = $$('[data-pres]');
  const presLabel = $('[data-pres-selected]');
  pres.forEach((b) => b.addEventListener('click', () => {
    pres.forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
    if (presLabel) presLabel.textContent = b.dataset.pres;
    if (b.dataset.presImage) { swap(b.dataset.presImage, b.dataset.presAlt); pressThumb(b.dataset.presImage); }
  }));

  /* --- Disponibilidad según la zona del header (distribuidor, región o todos) --- */
  const box = $('[data-avail-box]');
  const plural = (n) => `${n} ${n === 1 ? 'distribuidor' : 'distribuidores'}`;
  /* WordPress: URL del localizador en data-map-url (en la maqueta, distribuidores.html) */
  const MAP = page.dataset.mapUrl || 'distribuidores.html';
  const baseMapUrl = () => `${MAP}${MAP.includes('?') ? '&' : '?'}producto=${encodeURIComponent(page.dataset.id)}`;
  let mapUrl = baseMapUrl();
  const render = () => {
    const z = LBX.distributor?.scope() || { type: 'all', ids: [] };
    const inZone = dists.filter((id) => z.ids.includes(id));
    $$('[data-mini-dist]').forEach((el) => el.classList.toggle('is-mine', z.type !== 'all' && z.ids.includes(el.dataset.miniDist)));
    if (!box) return;
    const status = $('[data-avail-status]', box);
    const action = $('[data-avail-action]', box);
    mapUrl = baseMapUrl();
    if (z.type === 'all') {
      box.dataset.state = 'todos';
      status.textContent = dists.length ? `Disponible en ${plural(dists.length)}` : 'Consulta disponibilidad';
      action.textContent = 'Elegir mi distribuidor';
      action.dataset.mode = 'modal';
    } else if (z.type === 'region') {
      box.dataset.state = inZone.length ? 'si' : 'no';
      status.textContent = inZone.length ? `Disponible en ${plural(inZone.length)} de ${z.region}` : `No disponible en ${z.region}`;
      action.textContent = inZone.length ? 'Ver distribuidores de la región' : 'Ver distribuidores que lo tienen';
      action.dataset.mode = 'map';
      if (inZone.length) mapUrl += `&region=${encodeURIComponent(z.region)}`;
    } else if (inZone.length) {
      box.dataset.state = 'si';
      status.textContent = `Disponible en ${z.label}`;
      action.textContent = 'Cambiar distribuidor';
      action.dataset.mode = 'modal';
    } else {
      box.dataset.state = 'no';
      status.textContent = `No disponible en ${z.label}`;
      action.textContent = 'Ver distribuidores que lo tienen';
      action.dataset.mode = 'map';
    }
  };
  $('[data-avail-action]', box || document)?.addEventListener('click', (e) => {
    const btn = e.currentTarget;
    if (btn.dataset.mode === 'map') { window.location.href = mapUrl; return; }
    if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(document.getElementById('distributorModal')).show(btn);
  });
  document.addEventListener('lbx:distributorchange', render);
  render();
})();
