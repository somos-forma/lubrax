/*!
 * Lubrax · catalog.js — filtros, orden y paginación del catálogo (productos.html)
 *
 * Trabaja sobre las tarjetas que ya están en el DOM (las imprime el CMS):
 *   <li><article class="product-card" data-product data-id data-name data-category
 *        data-category-name data-fuel data-base data-sae data-specs="API SP|ILSAC GF-6A"
 *        data-distributors="id1 id2" data-featured="1">…</article></li>
 *
 * Los filtros de base, viscosidad, especificación y combustible se construyen solos
 * a partir de esos atributos. La URL refleja el estado (?categoria=…&sae=…&q=…),
 * así los enlaces desde el home y el buscador llegan filtrados.
 * La disponibilidad sigue la zona del header (un distribuidor o una región completa).
 *
 * WordPress (cambios respecto de la maqueta):
 *   - Filtro "Tipo de producto" (data-type, ?tipo=).
 *   - data-category admite varios segmentos (separados por espacio) y data-type, data-fuel,
 *     data-base y data-sae varios valores (separados por "|").
 *   - URL indexable por segmento: el segmento elegido va en la ruta (/segmento/automotriz/?sae=…)
 *     en vez de ?categoria= (que se sigue aceptando al llegar).
 *   - En el archivo de una taxonomía, el filtro inicial viene en el <ul id="productGrid">:
 *     data-cat (segmento) o data-facet-key + data-facet-value (otra taxonomía). data-base-url es la
 *     ruta del catálogo completo.
 */
(() => {
  'use strict';

  const grid = document.getElementById('productGrid');
  if (!grid || !window.LBX) return;
  const { $, $$, normalize, slug, store, params } = LBX.utils;

  const PAGE_SIZE = 9;
  const list = (v) => (v || '').split('|').map((s) => s.trim()).filter(Boolean);
  const items = $$(':scope > li', grid).map((li, i) => {
    const c = li.querySelector('[data-product]');
    const d = c.dataset;
    return {
      li, card: c, order: i,
      id: d.id, name: d.name || '', cats: (d.category || '').split(/\s+/).filter(Boolean), categoryName: d.categoryName || '',
      type: list(d.type), fuel: list(d.fuel), base: list(d.base), sae: list(d.sae),
      specs: list(d.specs),
      dists: (d.distributors || '').split(/\s+/).filter(Boolean),
      featured: d.featured === '1',
      text: normalize(c.textContent + ' ' + (d.specs || '') + ' ' + (d.sae || '') + ' ' + (d.type || ''))
    };
  });

  /* Valores posibles de cada filtro (orden alfabético natural) */
  const sortVals = (a, b) => a.localeCompare(b, 'es', { numeric: true });
  const FACETS = {
    fuel: { label: 'Combustible', get: (it) => it.fuel },
    type: { label: 'Tipo de producto', get: (it) => it.type },
    base: { label: 'Tipo de base', get: (it) => it.base },
    sae: { label: 'Viscosidad', get: (it) => it.sae },
    spec: { label: 'Especificación', get: (it) => it.specs }
  };
  const URLKEY = { fuel: 'combustible', type: 'tipo', base: 'base', sae: 'sae', spec: 'espec' };

  /* Archivo de taxonomía (WordPress): filtro inicial y rutas */
  const archive = {
    base: grid.dataset.baseUrl || window.location.pathname,
    cat: grid.dataset.cat || '',
    facetKey: grid.dataset.facetKey || '',
    facetValue: grid.dataset.facetValue || '',
    path: window.location.pathname
  };
  const values = {};
  Object.keys(FACETS).forEach((k) => { values[k] = [...new Set(items.flatMap(FACETS[k].get))].sort(sortVals); });

  const catNames = {};
  $$('.cat-list a[data-cat]').forEach((a) => { if (a.dataset.cat) catNames[a.dataset.cat] = a.dataset.name || a.firstChild.textContent.trim(); });

  /* ------------------------------------------------------------------ */
  const state = { cat: '', fuel: new Set(), type: new Set(), base: new Set(), sae: new Set(), spec: new Set(), q: '', sort: 'destacados', page: 1, mine: true };

  const readUrl = () => {
    const p = params();
    state.cat = p.get('categoria') || archive.cat;
    Object.keys(URLKEY).forEach((k) => {
      let wanted = (p.get(URLKEY[k]) || '').split(',').map((s) => s.trim()).filter(Boolean);
      if (!wanted.length && k === archive.facetKey) wanted = [archive.facetValue];
      state[k] = new Set(values[k].filter((v) => wanted.includes(slug(v)) || wanted.includes(v)));
    });
    state.q = p.get('q') || '';
    state.sort = p.get('orden') || 'destacados';
    state.page = Math.max(1, parseInt(p.get('pagina'), 10) || 1);
    state.mine = p.get('disponibles') !== '0';
  };

  /* Ruta según el estado: segmento elegido → su URL; filtro de la taxonomía del archivo intacto →
     la URL del archivo; si no, el catálogo completo. */
  const catPath = (cat) => {
    const a = cat && $(`.cat-list a[data-cat="${CSS.escape(cat)}"]`);
    return a ? new URL(a.href, window.location.href).pathname : '';
  };
  const onFacetArchive = () => !state.cat && archive.facetKey
    && state[archive.facetKey].size === 1 && state[archive.facetKey].has(archive.facetValue);

  const writeUrl = () => {
    const p = new URLSearchParams();
    let path = catPath(state.cat);
    const facetArchive = !path && onFacetArchive();
    if (state.cat && !path) p.set('categoria', state.cat);
    if (facetArchive) path = archive.path;
    Object.keys(URLKEY).forEach((k) => {
      if (state[k].size && !(facetArchive && k === archive.facetKey)) p.set(URLKEY[k], [...state[k]].map(slug).join(','));
    });
    if (state.q) p.set('q', state.q);
    if (state.sort !== 'destacados') p.set('orden', state.sort);
    if (state.page > 1) p.set('pagina', state.page);
    if (!state.mine) p.set('disponibles', '0');
    const qs = p.toString();
    history.replaceState(null, '', (path || archive.base) + (qs ? `?${qs}` : '') + window.location.hash);
  };

  /* ------------------------------------------------------------------ */
  const zone = () => LBX.distributor?.scope() || { type: 'all', ids: [] };
  const inZone = (it, z) => it.dists.some((id) => z.ids.includes(id));
  const matches = (it, skip) => {
    if (skip !== 'cat' && state.cat && !it.cats.includes(state.cat)) return false;
    for (const k of Object.keys(FACETS)) {
      if (k === skip || !state[k].size) continue;
      if (!FACETS[k].get(it).some((v) => state[k].has(v))) return false;
    }
    if (state.q && !normalize(state.q).split(/\s+/).every((w) => it.text.includes(w))) return false;
    const z = zone();
    if (skip !== 'mine' && z.type !== 'all' && state.mine && !inZone(it, z)) return false;
    return true;
  };

  const SORTERS = {
    destacados: (a, b) => (b.featured - a.featured) || (a.order - b.order),
    'nombre-asc': (a, b) => a.name.localeCompare(b.name, 'es'),
    'nombre-desc': (a, b) => b.name.localeCompare(a.name, 'es'),
    viscosidad: (a, b) => sortVals(a.sae[0] || '', b.sae[0] || '') || a.name.localeCompare(b.name, 'es')
  };

  /* --- Construcción de los grupos de filtros --- */
  const buildFacets = () => {
    Object.keys(FACETS).forEach((k) => {
      const box = $(`[data-facet="${k}"]`);
      if (!box) return;
      if (!values[k].length && k !== 'fuel') box.closest('fieldset')?.setAttribute('hidden', '');
      box.replaceChildren(...values[k].map((v) => {
        const label = document.createElement('label');
        label.className = 'chip-check';
        label.innerHTML = '<input type="checkbox"><span></span>';
        const input = label.querySelector('input');
        input.value = v;
        input.name = k;
        label.querySelector('span').append(v, ' ', Object.assign(document.createElement('small'), { className: 'facet-count' }));
        input.addEventListener('change', () => {
          if (input.checked) state[k].add(v); else state[k].delete(v);
          state.page = 1; update();
        });
        return label;
      }));
      const more = $(`[data-facet-more="${k}"]`);
      if (more) {
        const many = values[k].length > 8;
        more.hidden = !many;
        box.classList.toggle('is-collapsed', many);
        more.addEventListener('click', () => {
          const collapsed = box.classList.toggle('is-collapsed');
          more.textContent = collapsed ? `Ver ${values[k].length - 8} más` : 'Ver menos';
          more.setAttribute('aria-expanded', String(!collapsed));
        });
        more.textContent = `Ver ${values[k].length - 8} más`;
      }
    });
  };

  const syncFacets = () => {
    Object.keys(FACETS).forEach((k) => {
      const counts = {};
      items.filter((it) => matches(it, k)).forEach((it) => FACETS[k].get(it).forEach((v) => { counts[v] = (counts[v] || 0) + 1; }));
      $$(`[data-facet="${k}"] input`).forEach((input) => {
        input.checked = state[k].has(input.value);
        const n = counts[input.value] || 0;
        input.disabled = n === 0 && !input.checked;
        input.parentElement.querySelector('.facet-count').textContent = `(${n})`;
      });
    });
    const fuelGroup = $('[data-filter-group="fuel"]');
    if (fuelGroup) {
      const show = state.cat === 'automotriz' || (!state.cat && state.fuel.size > 0);
      fuelGroup.hidden = !show;
      if (!show && state.fuel.size) state.fuel.clear();
    }
    /* contadores de categorías */
    const catCounts = {};
    let all = 0;
    items.filter((it) => matches(it, 'cat')).forEach((it) => { it.cats.forEach((c) => { catCounts[c] = (catCounts[c] || 0) + 1; }); all++; });
    $$('.cat-list a[data-cat]').forEach((a) => {
      const c = a.dataset.cat;
      a.querySelector('.count').textContent = c ? (catCounts[c] || 0) : all;
      if (state.cat === c) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
    });
  };

  /* --- Etiquetas de filtros activos --- */
  const tag = (text, onRemove) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'filter-tag';
    b.innerHTML = '<span></span><svg class="icon" aria-hidden="true"><use href="#i-x"></use></svg>';
    b.firstChild.textContent = text;
    b.setAttribute('aria-label', `Quitar filtro: ${text}`);
    b.addEventListener('click', () => { onRemove(); state.page = 1; update(); });
    return b;
  };
  const renderTags = () => {
    const box = $('[data-active-filters]');
    if (!box) return;
    const tags = [];
    if (state.cat) tags.push(tag(catNames[state.cat] || state.cat, () => { state.cat = ''; }));
    Object.keys(FACETS).forEach((k) => state[k].forEach((v) => tags.push(tag(v, () => state[k].delete(v)))));
    if (state.q) tags.push(tag(`«${state.q}»`, () => { state.q = ''; const s = $('#catalogSearch'); if (s) s.value = ''; }));
    if (tags.length > 1) {
      const clear = document.createElement('button');
      clear.type = 'button';
      clear.className = 'filter-tag filter-tag--clear';
      clear.textContent = 'Limpiar filtros';
      clear.addEventListener('click', () => { resetFilters(); update(); });
      tags.push(clear);
    }
    box.replaceChildren(...tags);
  };
  const resetFilters = () => {
    state.cat = ''; state.q = ''; state.page = 1;
    Object.keys(FACETS).forEach((k) => state[k].clear());
    const s = $('#catalogSearch'); if (s) s.value = '';
  };

  /* --- Disponibilidad según la zona del header --- */
  const renderAvailability = () => {
    const z = zone();
    const banner = $('[data-dist-banner]');
    if (banner) banner.hidden = z.type === 'all';
    const sw = $('#onlyMine');
    if (sw) {
      sw.checked = state.mine;
      const label = $('label[for="onlyMine"]');
      if (label) label.textContent = z.type === 'region' ? 'Solo productos disponibles en esta región' : 'Solo productos de este distribuidor';
    }
    items.forEach((it) => {
      if (z.type === 'all') { delete it.card.dataset.avail; return; }
      const ok = inZone(it, z);
      it.card.dataset.avail = ok ? 'si' : 'no';
      const label = it.card.querySelector('[data-avail-label]');
      if (label) label.textContent = ok ? 'Disponible' : 'Consultar';
    });
  };

  /* ------------------------------------------------------------------ */
  const update = () => {
    syncFacets();
    renderAvailability();
    const result = items.filter((it) => matches(it)).sort(SORTERS[state.sort] || SORTERS.destacados);
    const pages = Math.max(1, Math.ceil(result.length / PAGE_SIZE));
    state.page = Math.min(state.page, pages);
    const visible = new Set(result.slice((state.page - 1) * PAGE_SIZE, state.page * PAGE_SIZE));
    items.forEach((it) => { it.li.hidden = !visible.has(it); });
    result.forEach((it) => grid.append(it.li));          // reordena según el criterio elegido

    const heading = $('[data-catalog-heading]');
    if (heading) heading.textContent = state.q ? `Resultados para «${state.q}»` : state.cat ? (catNames[state.cat] || 'Catálogo') : onFacetArchive() ? archive.facetValue : 'Todo el catálogo';
    const count = $('[data-catalog-count]');
    if (count) count.textContent = `${result.length} ${result.length === 1 ? 'producto' : 'productos'}${pages > 1 ? ` · página ${state.page} de ${pages}` : ''}`;
    const empty = $('[data-catalog-empty]');
    if (empty) empty.hidden = result.length > 0;
    renderTags();
    LBX.pagination($('[data-catalog-pagination]'), {
      page: state.page, pages,
      onChange: (n) => { state.page = n; update(); grid.closest('section')?.scrollIntoView({ behavior: LBX.utils.reduceMotion ? 'auto' : 'smooth', block: 'start' }); }
    });
    writeUrl();
    LBX.reveal?.(grid);
  };

  /* --- Eventos --- */
  $$('.cat-list a[data-cat]').forEach((a) => a.addEventListener('click', (e) => {
    e.preventDefault();
    state.cat = a.dataset.cat;
    state.page = 1;
    update();
    const panel = document.getElementById('filters');
    if (panel && window.bootstrap && window.matchMedia('(max-width: 991.98px)').matches) bootstrap.Offcanvas.getInstance(panel)?.hide();
  }));

  const search = $('#catalogSearch');
  if (search) {
    let t;
    search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { state.q = search.value.trim(); state.page = 1; update(); }, 220); });
    search.form?.addEventListener('submit', (e) => { e.preventDefault(); state.q = search.value.trim(); state.page = 1; update(); });
  }
  const sortSel = $('#catalogSort');
  sortSel?.addEventListener('change', () => { state.sort = sortSel.value; state.page = 1; update(); });
  $('#onlyMine')?.addEventListener('change', (e) => { state.mine = e.target.checked; state.page = 1; update(); });
  $$('[data-catalog-reset]').forEach((b) => b.addEventListener('click', () => { resetFilters(); update(); }));
  document.addEventListener('lbx:distributorchange', () => { state.mine = true; state.page = 1; update(); });

  /* Vista cuadrícula / lista */
  const viewBtns = $$('[data-view]');
  const applyView = (v) => {
    grid.classList.toggle('product-grid--list', v === 'list');
    viewBtns.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.view === v)));
  };
  applyView(store.get('lbx.view') === 'list' ? 'list' : 'grid');
  viewBtns.forEach((b) => b.addEventListener('click', () => { applyView(b.dataset.view); store.set('lbx.view', b.dataset.view); }));

  /* Arranque */
  readUrl();
  buildFacets();
  if (search) search.value = state.q;
  if (sortSel) sortSel.value = state.sort;
  update();
})();
