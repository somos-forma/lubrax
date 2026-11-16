/*!
 * Lubrax · docs.js — repositorio de documentación técnica (bloque lubrax/doc-library)
 * Nuevo en WordPress: no estaba en la maqueta. Usa sus utilidades (LBX.utils, LBX.pagination, LBX.reveal).
 *
 * Marcado (lo imprime el bloque):
 *   <ul id="docGrid" data-per-page="12">
 *     <li data-doc data-product="slug" data-segments="automotriz linea-pesada" data-type="ficha-tecnica"
 *         data-search="nombre viscosidad normas…"> tarjeta tpl-doc-card </li>
 *   #docSearch · #docSegment · #docType · [data-docs-reset] · [data-docs-count] · [data-docs-empty] · [data-docs-pagination]
 *
 * URL: ?q= · ?segmento= · ?tipo= · ?producto= · ?pagina=
 * Los visores PDF los abre app.js (enlaces con data-pdf-view).
 */
(() => {
  'use strict';

  const grid = document.getElementById('docGrid');
  if (!grid || !window.LBX) return;
  const { $, $$, normalize, params } = LBX.utils;

  const PAGE_SIZE = parseInt(grid.dataset.perPage, 10) || 12;
  const search = $('#docSearch');
  const segmentSel = $('#docSegment');
  const typeSel = $('#docType');
  const countEl = $('[data-docs-count]');
  const emptyEl = $('[data-docs-empty]');
  const pager = $('[data-docs-pagination]');

  const items = $$(':scope > li[data-doc]', grid).map((li) => ({
    li,
    product: li.dataset.product || '',
    segments: (li.dataset.segments || '').split(/\s+/).filter(Boolean),
    type: li.dataset.type || '',
    text: normalize(`${li.dataset.search || ''} ${li.textContent}`)
  }));

  const state = { q: '', segment: '', type: '', product: '', page: 1 };

  const readUrl = () => {
    const p = params();
    state.q = p.get('q') || '';
    state.segment = p.get('segmento') || '';
    state.type = p.get('tipo') || '';
    state.product = p.get('producto') || '';
    state.page = Math.max(1, parseInt(p.get('pagina'), 10) || 1);
  };
  const writeUrl = () => {
    const p = new URLSearchParams();
    if (state.q) p.set('q', state.q);
    if (state.segment) p.set('segmento', state.segment);
    if (state.type) p.set('tipo', state.type);
    if (state.product) p.set('producto', state.product);
    if (state.page > 1) p.set('pagina', state.page);
    const qs = p.toString();
    history.replaceState(null, '', (qs ? `?${qs}` : window.location.pathname) + window.location.hash);
  };

  const matches = (it) => (!state.segment || it.segments.includes(state.segment))
    && (!state.type || it.type === state.type)
    && (!state.product || it.product === state.product)
    && (!state.q || normalize(state.q).split(/\s+/).every((w) => it.text.includes(w)));

  const update = () => {
    const result = items.filter(matches);
    const pages = Math.max(1, Math.ceil(result.length / PAGE_SIZE));
    state.page = Math.min(state.page, pages);
    const visible = new Set(result.slice((state.page - 1) * PAGE_SIZE, state.page * PAGE_SIZE));
    items.forEach((it) => { it.li.hidden = !visible.has(it); });

    if (countEl) {
      countEl.textContent = `${result.length} ${result.length === 1 ? 'documento' : 'documentos'}${pages > 1 ? ` · página ${state.page} de ${pages}` : ''}`;
    }
    if (emptyEl) emptyEl.hidden = result.length > 0;
    LBX.pagination(pager, {
      page: state.page, pages,
      onChange: (n) => { state.page = n; update(); grid.closest('section')?.scrollIntoView({ behavior: LBX.utils.reduceMotion ? 'auto' : 'smooth', block: 'start' }); }
    });
    writeUrl();
    LBX.reveal?.(grid);
  };

  /* --- Eventos --- */
  let t;
  search?.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { state.q = search.value.trim(); state.page = 1; update(); }, 200); });
  search?.form?.addEventListener('submit', (e) => { e.preventDefault(); state.q = search.value.trim(); state.page = 1; update(); });
  segmentSel?.addEventListener('change', () => { state.segment = segmentSel.value; state.page = 1; update(); });
  typeSel?.addEventListener('change', () => { state.type = typeSel.value; state.page = 1; update(); });
  $('[data-docs-reset]')?.addEventListener('click', (e) => {
    e.preventDefault();
    Object.assign(state, { q: '', segment: '', type: '', product: '', page: 1 });
    if (search) search.value = '';
    if (segmentSel) segmentSel.value = '';
    if (typeSel) typeSel.value = '';
    update();
  });

  /* --- Arranque --- */
  readUrl();
  if (search) search.value = state.q;
  if (segmentSel) segmentSel.value = state.segment;
  if (typeSel) typeSel.value = state.type;
  update();
})();
