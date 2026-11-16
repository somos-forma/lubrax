/*!
 * Lubrax · locator.js — distribuidores (distribuidores.html)
 * Leaflet + OpenStreetMap (sin API key). Para otro proveedor: TILE_URL / TILE_OPTIONS.
 *
 * Lee el marcado que imprime el CMS:
 *   <article class="dist-card" data-distributor data-id data-name data-label data-region data-city
 *            data-lat data-lng data-products="id1 id2">
 *     [data-field="purchase|label|address|hours|phone|packs|route|web"]   datos del perfil
 *     [data-products-preview]                                            muestra de productos (vista Listado)
 *     [data-action="profile|select|focus"]                               botones
 *   </article>
 *   <select id="locProduct"> con <optgroup label="Categoría"><option value="id" data-url="/producto/…">
 *   #distProfile                                                         modal del perfil ampliado
 *
 * Vistas: data-view="map" (listado + mapa; en móvil solo mapa) o "list" (tarjetas en cuadrícula).
 * URL: ?producto=id · ?region=… · ?vista=mapa|listado · ?perfil=id (abre el perfil)
 * Si en el header hay una región elegida, el filtro de región arranca con ella.
 *
 * WordPress: el proveedor del mapa base se configura en #distMap (Opciones del tema > Mapa):
 *   data-tile-url, data-tile-attribution y data-tile-max-zoom. Sin ellos se usa OpenStreetMap.
 */
(() => {
  'use strict';

  const root = document.getElementById('locator');
  if (!root || !window.LBX) return;
  const { $, $$, normalize, haversineKm, formatKm, geolocate, params, escapeHtml, reduceMotion, store } = LBX.utils;

  const tileConfig = document.getElementById('distMap')?.dataset || {};
  const TILE_URL = tileConfig.tileUrl || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
  const TILE_OPTIONS = {
    maxZoom: parseInt(tileConfig.tileMaxZoom, 10) || 18,
    attribution: tileConfig.tileAttribution || '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    referrerPolicy: 'strict-origin-when-cross-origin'
  };
  const IS_LOCAL_FILE = window.location.protocol === 'file:'; // OSM exige Referer: con file:// no se pide el mapa base
  const VIEW_KEY = 'lbx.locView';
  const PREVIEW_MAX = 6;                                       // productos visibles en la tarjeta (vista Listado)

  const list = $('#distList');
  const layout = $('.locator__layout', root);
  const productSel = $('#locProduct');
  const regionSel = $('#locRegion');
  const searchInput = $('#locSearch');
  const countEl = $('[data-locator-count]');
  const mapEl = $('#distMap');
  const profileEl = document.getElementById('distProfile');
  const desktop = window.matchMedia('(min-width: 992px)');
  const motion = reduceMotion ? 'auto' : 'smooth';

  /* Productos (desde el selector): id → { name, group, url } en el orden del catálogo */
  const catalog = new Map();
  $$('option', productSel || document.createElement('select')).forEach((o) => {
    if (!o.value) return;
    const group = o.parentElement.tagName === 'OPTGROUP' ? o.parentElement.label : 'Productos';
    catalog.set(o.value, { name: o.textContent.trim(), group, url: o.dataset.url || `producto.html?id=${encodeURIComponent(o.value)}` });
  });
  const productName = (id) => catalog.get(id)?.name || '';

  const field = (card, name) => card.querySelector(`[data-field="${name}"]`);
  const text = (el) => (el ? el.textContent.trim() : '');
  const cards = $$('[data-distributor]', list).map((card) => ({
    card, li: card.closest('li') || card,
    id: card.dataset.id, name: card.dataset.name || '', label: card.dataset.label || text(field(card, 'label')),
    region: card.dataset.region || '', city: card.dataset.city || '',
    lat: parseFloat(card.dataset.lat), lng: parseFloat(card.dataset.lng),
    products: (card.dataset.products || '').split(/\s+/).filter(Boolean),
    text: normalize(card.textContent),
    preview: $('[data-products-preview]', card)
  }));
  const byId = (id) => cards.find((c) => c.id === id);

  let user = null;
  let activeId = null;
  let map = null;
  const markers = new Map();

  /* Regiones a partir de las tarjetas */
  if (regionSel) {
    const regions = [...new Set(cards.map((c) => c.region).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es'));
    regionSel.replaceChildren(new Option('Todas las regiones', ''), ...regions.map((r) => new Option(r, r)));
  }
  const p = params();
  if (productSel && catalog.has(p.get('producto'))) productSel.value = p.get('producto');
  if (regionSel && p.get('region')) regionSel.value = p.get('region');

  /* Región elegida en el header: el listado arranca filtrado por ella (y la sigue si cambia) */
  let headerRegion = '';
  const syncHeaderRegion = () => {
    const z = LBX.distributor?.scope();
    if (!regionSel || !z) return;
    if (z.type === 'region') { regionSel.value = z.region; headerRegion = z.region; }
    else if (headerRegion && regionSel.value === headerRegion) { regionSel.value = ''; headerRegion = ''; }
  };
  if (!p.get('region')) syncHeaderRegion();

  /* ------------------------------------------------------------------ */
  const filtered = () => {
    const prod = productSel?.value || '';
    const region = regionSel?.value || '';
    const q = normalize(searchInput?.value || '');
    let out = cards.filter((c) => (!prod || c.products.includes(prod)) && (!region || c.region === region) && (!q || c.text.includes(q)));
    if (user) out = out.map((c) => Object.assign(c, { km: haversineKm(user, c) })).sort((a, b) => a.km - b.km);
    return out;
  };

  /* Chips de productos en la tarjeta (el elegido en el filtro va primero y resaltado) */
  const renderPreview = (c, prod) => {
    if (!c.preview) return;
    const ids = (prod && c.products.includes(prod) ? [prod, ...c.products.filter((x) => x !== prod)] : c.products).filter((id) => catalog.has(id));
    const items = ids.slice(0, PREVIEW_MAX).map((id) => {
      const li = document.createElement('li');
      const a = Object.assign(document.createElement('a'), { className: `dist-product${id === prod ? ' is-match' : ''}`, href: catalog.get(id).url, textContent: catalog.get(id).name });
      li.append(a);
      return li;
    });
    if (ids.length > PREVIEW_MAX) {
      const li = document.createElement('li');
      const b = Object.assign(document.createElement('button'), { type: 'button', className: 'dist-product dist-product--more', textContent: `+${ids.length - PREVIEW_MAX} más` });
      b.dataset.action = 'profile';
      b.setAttribute('aria-label', `Ver los ${ids.length} productos de ${c.name}`);
      li.append(b);
      items.push(li);
    }
    c.preview.replaceChildren(...items);
  };

  const render = ({ fit = true } = {}) => {
    const items = filtered();
    const visible = new Set(items);
    const prod = productSel?.value || '';
    const mine = LBX.distributor?.get()?.id;
    cards.forEach((c) => {
      c.li.hidden = !visible.has(c);
      const avail = $('[data-avail-text]', c.card);
      if (avail) {
        avail.textContent = prod ? `✓ Tiene ${productName(prod)}` : `${c.products.length} productos disponibles`;
        avail.classList.toggle('is-match', !!prod);
      }
      const dist = $('[data-distance]', c.card);
      if (dist) dist.textContent = user && typeof c.km === 'number' ? formatKm(c.km) : '';
      c.card.classList.toggle('is-mine', c.id === mine);
      const chip = $('[data-mine-chip]', c.card);
      if (chip) chip.hidden = c.id !== mine;
      const sel = $('[data-action="select"]', c.card);
      if (sel) sel.hidden = c.id === mine;
      renderPreview(c, prod);
    });
    items.forEach((c) => list.append(c.li));             // orden (cercanía) en el DOM
    if (countEl) {
      countEl.textContent = items.length
        ? `${items.length} ${items.length === 1 ? 'distribuidor' : 'distribuidores'}${prod ? ` con ${productName(prod)}` : ''}${user ? ' · ordenados por cercanía' : ''}`
        : 'No encontramos distribuidores con ese criterio.';
    }
    syncMarkers(items, fit);
    syncProfile();
  };

  /* --- Mapa --- */
  const pinIcon = (active) => window.L.divIcon({ className: '', html: `<span class="map-pin${active ? ' is-active' : ''}"></span>`, iconSize: [36, 36], iconAnchor: [18, 42], popupAnchor: [0, -38] });
  const initMap = () => {
    if (!window.L || !mapEl) { $('.locator__fallback', root)?.removeAttribute('hidden'); return; }
    map = window.L.map(mapEl, { scrollWheelZoom: false }).setView([-35.5, -71.5], 5);
    if (IS_LOCAL_FILE) $('.locator__notice', root)?.removeAttribute('hidden');
    else window.L.tileLayer(TILE_URL, TILE_OPTIONS).addTo(map);
    map.on('focus', () => map.scrollWheelZoom.enable());
    map.on('blur', () => map.scrollWheelZoom.disable());
    cards.forEach((c) => {
      if (Number.isNaN(c.lat)) return;
      const m = window.L.marker([c.lat, c.lng], { icon: pinIcon(false), title: c.name, alt: c.name, riseOnHover: true });
      const route = field(c.card, 'route')?.href || `https://www.google.com/maps/dir/?api=1&destination=${c.lat},${c.lng}`;
      m.bindPopup(`<strong>${escapeHtml(c.name)}</strong>${escapeHtml(c.city)} · ${escapeHtml(c.region)}
        <span class="map-popup__links"><button type="button" class="map-popup__btn" data-profile-open="${escapeHtml(c.id)}">Ver perfil</button>
        <a href="${escapeHtml(route)}" target="_blank" rel="noopener">Ver ruta</a></span>`);
      m.on('click', () => setActive(c.id, { fromMap: true }));
      markers.set(c.id, m);
    });
  };
  const fitTo = (items) => {
    if (!map) return;
    const pts = items.filter((c) => !Number.isNaN(c.lat)).map((c) => [c.lat, c.lng]);
    if (user) pts.push([user.lat, user.lng]);
    if (pts.length) map.fitBounds(window.L.latLngBounds(pts), { padding: [48, 48], maxZoom: 11, animate: !reduceMotion });
  };
  const syncMarkers = (items, fit) => {
    if (!map) return;
    const ids = new Set(items.map((c) => c.id));
    markers.forEach((m, id) => (ids.has(id) ? m.addTo(map) : m.remove()));
    if (fit && items.length && layout.dataset.view === 'map') fitTo(items);
  };
  const setActive = (id, { fromMap = false } = {}) => {
    if (activeId && markers.has(activeId)) markers.get(activeId).setIcon(pinIcon(false));
    activeId = id;
    cards.forEach((c) => c.card.classList.toggle('is-active', c.id === id));
    const m = markers.get(id);
    if (m) m.setIcon(pinIcon(true)).setZIndexOffset(1000);
    if (fromMap) {
      const c = byId(id);
      if (c && desktop.matches) c.card.scrollIntoView({ block: 'nearest', behavior: motion });
      return;
    }
    if (!map || !m) return;
    const fly = () => { map.flyTo(m.getLatLng(), 13, { animate: !reduceMotion, duration: 0.8 }); m.openPopup(); };
    if (layout.dataset.view !== 'map') {                 // desde "Listado": cambia a la vista de mapa
      setView('map', { fit: false });
      layout.scrollIntoView({ block: 'start', behavior: motion });
      setTimeout(fly, 120);
    } else fly();
  };

  /* --- Vistas: Mapa / Listado --- */
  const viewBtns = $$('[data-loc-view]', root);
  function setView(v, { save = false, fit = true } = {}) {
    layout.dataset.view = v;
    viewBtns.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.locView === v)));
    if (save) store.set(VIEW_KEY, v);
    if (v === 'map' && map) setTimeout(() => { map.invalidateSize(); if (fit) fitTo(filtered()); }, 60);
  }
  viewBtns.forEach((b) => b.addEventListener('click', () => setView(b.dataset.locView, { save: true })));

  /* --- Perfil ampliado (modal) --- */
  const P = (k) => profileEl?.querySelector(`[data-profile="${k}"]`);
  const setLink = (el, href) => { if (!el) return; el.hidden = !href; if (href) el.href = href; };
  function syncProfile() {
    if (!profileEl?.dataset.id) return;
    const mine = LBX.distributor?.get()?.id === profileEl.dataset.id;
    P('mine').hidden = !mine;
    const btn = profileEl.querySelector('[data-profile-action="select"]');
    btn.disabled = mine;
    P('select-label').textContent = mine ? 'Es tu distribuidor' : 'Elegir como mi distribuidor';
  }
  const openProfile = (id, trigger) => {
    const c = byId(id);
    if (!c || !profileEl || !window.bootstrap) return;
    const prod = productSel?.value || '';
    profileEl.dataset.id = id;
    P('name').textContent = c.name;
    P('label').textContent = c.label;
    ['purchase', 'address', 'hours', 'packs'].forEach((k) => { P(k).textContent = text(field(c.card, k)); });
    const phone = field(c.card, 'phone');
    P('phone').textContent = text(phone);
    setLink(P('phone'), phone?.getAttribute('href'));
    setLink(P('call'), phone?.getAttribute('href'));
    setLink(P('route'), field(c.card, 'route')?.getAttribute('href'));
    const web = field(c.card, 'web')?.getAttribute('href');
    setLink(P('web'), web);
    if (P('web')) {                                         // muestra el dominio y oculta la fila si no hay sitio
      P('web').textContent = web ? web.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '') : '';
      const row = P('web').closest('dl > div');
      if (row) row.hidden = !web;
    }
    P('distance').textContent = user && typeof c.km === 'number' ? `a ${formatKm(c.km)}` : '';
    P('match').hidden = !prod;
    P('match').textContent = prod ? (c.products.includes(prod) ? `✓ Tiene ${productName(prod)}` : `No trabaja ${productName(prod)}`) : '';
    /* Productos agrupados por categoría, en el orden del catálogo */
    const groups = new Map();
    catalog.forEach((info, pid) => {
      if (!c.products.includes(pid)) return;
      if (!groups.has(info.group)) groups.set(info.group, []);
      groups.get(info.group).push([pid, info]);
    });
    const total = [...groups.values()].reduce((n, g) => n + g.length, 0);
    P('count').textContent = `(${total})`;
    P('products').replaceChildren(...[...groups].map(([name, items]) => {
      const box = document.createElement('div');
      box.className = 'dist-profile__group';
      box.innerHTML = `<p class="dist-profile__cat">${escapeHtml(name)}</p><ul class="dist-profile__products">${items.map(([pid, info]) =>
        `<li><a class="dist-product${pid === prod ? ' is-match' : ''}" href="${escapeHtml(info.url)}">${escapeHtml(info.name)}</a></li>`).join('')}</ul>`;
      return box;
    }));
    syncProfile();
    bootstrap.Modal.getOrCreateInstance(profileEl).show(trigger);
  };
  profileEl?.addEventListener('click', (e) => {
    const action = e.target.closest('[data-profile-action]')?.dataset.profileAction;
    const id = profileEl.dataset.id;
    if (action === 'select') {
      LBX.distributor.set(id);
      LBX.toast(`${LBX.distributor.get().label} es ahora tu distribuidor.`);
    }
    if (action === 'map') {
      profileEl.addEventListener('hidden.bs.modal', () => setActive(id), { once: true });
      bootstrap.Modal.getInstance(profileEl)?.hide();
    }
  });

  /* --- Eventos --- */
  list.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const id = btn.closest('[data-distributor]').dataset.id;
    if (btn.dataset.action === 'profile') openProfile(id, btn);
    if (btn.dataset.action === 'focus') setActive(id);
    if (btn.dataset.action === 'select') {
      LBX.distributor.set(id);
      LBX.toast(`${LBX.distributor.get().label} es ahora tu distribuidor.`);
    }
  });
  document.addEventListener('click', (e) => {                 // "Ver perfil" dentro del globo del mapa
    const b = e.target.closest('[data-profile-open]');
    if (b) openProfile(b.dataset.profileOpen, b);
  });
  let t;
  searchInput?.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => render(), 180); });
  searchInput?.form?.addEventListener('submit', (e) => { e.preventDefault(); render(); });
  productSel?.addEventListener('change', () => render());
  regionSel?.addEventListener('change', () => render());
  document.addEventListener('lbx:distributorchange', () => {
    const before = regionSel?.value;
    syncHeaderRegion();
    render({ fit: regionSel?.value !== before });
  });

  const geoBtn = $('[data-loc-geolocate]', root);
  if (geoBtn && !('geolocation' in navigator)) geoBtn.hidden = true;
  geoBtn?.addEventListener('click', async () => {
    geoBtn.disabled = true;
    if (countEl) countEl.textContent = 'Buscando tu ubicación…';
    try {
      user = await geolocate();
      if (map) window.L.circleMarker([user.lat, user.lng], { radius: 8, color: '#fff', weight: 3, fillColor: '#1a73e8', fillOpacity: 1 }).addTo(map).bindTooltip('Tu ubicación');
      render();
    } catch {
      if (countEl) countEl.textContent = 'No pudimos obtener tu ubicación. Usa la región o el buscador.';
    } finally { geoBtn.disabled = false; }
  });

  /* --- Arranque --- */
  const urlView = { mapa: 'map', listado: 'list' }[p.get('vista')];
  const saved = store.get(VIEW_KEY);
  setView(urlView || (saved === 'map' || saved === 'list' ? saved : (desktop.matches ? 'map' : 'list')));
  initMap();
  render();
  const mine = LBX.distributor?.get();
  if (mine && map && byId(mine.id) && !p.get('producto') && layout.dataset.view === 'map') setTimeout(() => setActive(mine.id, { fromMap: true }), 300);
  if (p.get('perfil') && byId(p.get('perfil'))) openProfile(p.get('perfil'));
})();
