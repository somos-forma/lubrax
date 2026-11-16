/*!
 * Lubrax · blog.js — filtros, orden y paginación del blog (blog.html)
 * Misma lógica y componentes visuales que el catálogo de productos.
 *
 * Marcado que imprime el CMS:
 *   <ul id="blogGrid">
 *     <li><article class="post-card" data-post data-id data-title data-category="mantenimiento"
 *          data-category-name="Mantenimiento" data-tags="viscosidad aceite-de-motor" data-date="2026-10-14">…</article></li>
 *   </ul>
 *   Etiquetas: <a href="blog.html?etiqueta=viscosidad" data-tag="viscosidad">#Viscosidad</a>
 *
 * Los chips de categoría se arman solos desde las tarjetas.
 * URL: ?categoria=…&etiqueta=…&q=…&orden=recientes|antiguos|titulo&pagina=…
 * Si el CMS pagina en el servidor, puede imprimir solo la página pedida y el
 * mismo marcado de paginación (ver LBX.pagination en app.js).
 *
 * WordPress (cambio respecto de la maqueta): en el archivo de una categoría o etiqueta, el filtro inicial
 * viene en el <ul id="blogGrid"> (data-cat / data-tag) y la URL del archivo se mantiene mientras ese
 * filtro siga activo. data-base-url es la ruta del blog completo.
 */
(() => {
  'use strict';

  const grid = document.getElementById('blogGrid');
  if (!grid || !window.LBX) return;
  const { $, $$, normalize, params, reduceMotion } = LBX.utils;

  const PAGE_SIZE = 6;
  const items = $$(':scope > li', grid).map((li, i) => {
    const card = li.querySelector('[data-post]');
    const d = card.dataset;
    return {
      li, card, order: i,
      title: d.title || '', category: d.category || '', categoryName: d.categoryName || '',
      tags: (d.tags || '').split(/\s+/).filter(Boolean), date: d.date || '',
      text: normalize(card.textContent)
    };
  });

  const tagLinks = $$('[data-tag]');
  const tagNames = {};
  tagLinks.forEach((a) => { tagNames[a.dataset.tag] = a.textContent.trim().replace(/^#/, ''); });
  const categories = [...new Map(items.filter((it) => it.category).map((it) => [it.category, it.categoryName])).entries()]
    .sort((a, b) => a[1].localeCompare(b[1], 'es'));
  const catName = (id) => categories.find(([c]) => c === id)?.[1] || id;

  /* ------------------------------------------------------------------ */
  const state = { cat: '', tag: '', q: '', sort: 'recientes', page: 1 };
  const archive = { base: grid.dataset.baseUrl || window.location.pathname, cat: grid.dataset.cat || '', tag: grid.dataset.tag || '', path: window.location.pathname };

  const readUrl = () => {
    const p = params();
    state.cat = categories.some(([c]) => c === p.get('categoria')) ? p.get('categoria') : archive.cat;
    state.tag = p.get('etiqueta') || archive.tag;
    state.q = p.get('q') || '';
    state.sort = ['recientes', 'antiguos', 'titulo'].includes(p.get('orden')) ? p.get('orden') : 'recientes';
    state.page = Math.max(1, parseInt(p.get('pagina'), 10) || 1);
  };
  const writeUrl = () => {
    const p = new URLSearchParams();
    const onArchive = (archive.cat && state.cat === archive.cat) || (archive.tag && state.tag === archive.tag);
    if (state.cat && !(onArchive && state.cat === archive.cat)) p.set('categoria', state.cat);
    if (state.tag && !(onArchive && state.tag === archive.tag)) p.set('etiqueta', state.tag);
    if (state.q) p.set('q', state.q);
    if (state.sort !== 'recientes') p.set('orden', state.sort);
    if (state.page > 1) p.set('pagina', state.page);
    const qs = p.toString();
    history.replaceState(null, '', (onArchive ? archive.path : archive.base) + (qs ? `?${qs}` : '') + window.location.hash);
  };

  const matches = (it, skip) => {
    if (skip !== 'cat' && state.cat && it.category !== state.cat) return false;
    if (state.tag && !it.tags.includes(state.tag)) return false;
    if (state.q && !normalize(state.q).split(/\s+/).every((w) => it.text.includes(w))) return false;
    return true;
  };
  const SORTERS = {
    recientes: (a, b) => b.date.localeCompare(a.date) || a.order - b.order,
    antiguos: (a, b) => a.date.localeCompare(b.date) || a.order - b.order,
    titulo: (a, b) => a.title.localeCompare(b.title, 'es')
  };

  /* --- Chips de categoría (radio: una a la vez) --- */
  const catBox = $('[data-blog-categories]');
  const chip = (value, label) => {
    const el = document.createElement('label');
    el.className = 'chip-check';
    el.innerHTML = '<input type="radio" name="blogCategoria"><span></span>';
    const input = el.querySelector('input');
    input.value = value;
    el.querySelector('span').append(label, ' ', Object.assign(document.createElement('small'), { className: 'facet-count' }));
    input.addEventListener('change', () => { state.cat = value; state.page = 1; update(); });
    return el;
  };
  catBox?.replaceChildren(chip('', 'Todos'), ...categories.map(([id, name]) => chip(id, name)));
  const syncCategories = () => {
    const counts = {};
    let all = 0;
    items.filter((it) => matches(it, 'cat')).forEach((it) => { counts[it.category] = (counts[it.category] || 0) + 1; all++; });
    $$('input', catBox || document.createElement('div')).forEach((input) => {
      input.checked = input.value === state.cat;
      const n = input.value ? counts[input.value] || 0 : all;
      input.disabled = n === 0 && !input.checked;
      input.parentElement.querySelector('.facet-count').textContent = `(${n})`;
    });
  };

  /* --- Etiquetas del costado: filtran sin recargar --- */
  const syncTags = () => tagLinks.forEach((a) => {
    const on = a.dataset.tag === state.tag;
    a.classList.toggle('is-active', on);
    if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
  });
  tagLinks.forEach((a) => a.addEventListener('click', (e) => {
    e.preventDefault();
    state.tag = state.tag === a.dataset.tag ? '' : a.dataset.tag;
    state.page = 1;
    update();
    grid.closest('section')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
  }));

  /* --- Filtros activos (mismo componente que el catálogo) --- */
  const filterTag = (text, onRemove) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'filter-tag';
    b.innerHTML = '<span></span><svg class="icon" aria-hidden="true"><use href="#i-x"></use></svg>';
    b.firstChild.textContent = text;
    b.setAttribute('aria-label', `Quitar filtro: ${text}`);
    b.addEventListener('click', () => { onRemove(); state.page = 1; update(); });
    return b;
  };
  const search = $('#blogSearch');
  const resetFilters = () => { state.cat = ''; state.tag = ''; state.q = ''; state.page = 1; if (search) search.value = ''; };
  const renderActive = () => {
    const box = $('[data-active-filters]');
    if (!box) return;
    const tags = [];
    if (state.cat) tags.push(filterTag(catName(state.cat), () => { state.cat = ''; }));
    if (state.tag) tags.push(filterTag(`#${tagNames[state.tag] || state.tag}`, () => { state.tag = ''; }));
    if (state.q) tags.push(filterTag(`«${state.q}»`, () => { state.q = ''; if (search) search.value = ''; }));
    if (tags.length > 1) {
      const clear = Object.assign(document.createElement('button'), { type: 'button', className: 'filter-tag filter-tag--clear', textContent: 'Limpiar filtros' });
      clear.addEventListener('click', () => { resetFilters(); update(); });
      tags.push(clear);
    }
    box.replaceChildren(...tags);
  };

  /* ------------------------------------------------------------------ */
  const update = () => {
    syncCategories();
    syncTags();
    const result = items.filter((it) => matches(it)).sort(SORTERS[state.sort]);
    const pages = Math.max(1, Math.ceil(result.length / PAGE_SIZE));
    state.page = Math.min(state.page, pages);
    const visible = new Set(result.slice((state.page - 1) * PAGE_SIZE, state.page * PAGE_SIZE));
    items.forEach((it) => { it.li.hidden = !visible.has(it); });
    result.forEach((it) => grid.append(it.li));

    const heading = $('[data-blog-heading]');
    if (heading) {
      heading.textContent = state.cat ? catName(state.cat)
        : state.tag ? `#${tagNames[state.tag] || state.tag}`
        : state.q ? `Resultados para «${state.q}»` : 'Todos los artículos';
    }
    const count = $('[data-blog-count]');
    if (count) count.textContent = `${result.length} ${result.length === 1 ? 'artículo' : 'artículos'}${pages > 1 ? ` · página ${state.page} de ${pages}` : ''}`;
    const empty = $('[data-blog-empty]');
    if (empty) empty.hidden = result.length > 0;
    renderActive();
    LBX.pagination($('[data-blog-pagination]'), {
      page: state.page, pages,
      onChange: (n) => { state.page = n; update(); grid.closest('section')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' }); }
    });
    writeUrl();
    LBX.reveal?.(grid);
  };

  /* --- Eventos --- */
  if (search) {
    let t;
    search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { state.q = search.value.trim(); state.page = 1; update(); }, 220); });
    search.form?.addEventListener('submit', (e) => { e.preventDefault(); state.q = search.value.trim(); state.page = 1; update(); });
  }
  const sortSel = $('#blogSort');
  sortSel?.addEventListener('change', () => { state.sort = sortSel.value; state.page = 1; update(); });
  $$('[data-blog-reset]').forEach((b) => b.addEventListener('click', () => { resetFilters(); update(); }));

  /* Arranque */
  readUrl();
  if (search) search.value = state.q;
  if (sortSel) sortSel.value = state.sort;
  update();
})();
