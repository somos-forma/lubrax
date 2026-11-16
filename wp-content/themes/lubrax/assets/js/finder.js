/*!
 * Lubrax · finder.js — buscador "¿Qué estás buscando?" del home
 *
 * Pestaña "Por categoría y viscosidad": lista las viscosidades de la categoría
 * elegida y muestra en el botón cuántos productos coinciden.
 * Dato que imprime el CMS en la página (isla JSON):
 *   <script type="application/json" id="lbx-product-index">[{ id, category, sae }]</script>
 * Sin esa isla el formulario sigue funcionando (sin conteo). La "Búsqueda libre"
 * es un formulario GET normal hacia productos.html?q=…
 */
(() => {
  'use strict';

  const finder = document.querySelector('[data-finder]');
  if (!finder) return;
  const form = finder.querySelector('#finderCategory');
  if (!form) return;

  let index = [];
  try { index = JSON.parse(document.getElementById('lbx-product-index')?.textContent || '[]'); } catch { index = []; }

  const cat = form.querySelector('[name="categoria"]');
  const sae = form.querySelector('[name="sae"]');
  const btnLabel = form.querySelector('[data-finder-count]');
  const saeFor = (c) => [...new Set(index.filter((p) => !c || p.category === c).map((p) => p.sae))].sort((a, b) => a.localeCompare(b, 'es', { numeric: true }));
  const fillSae = () => {
    if (!index.length) return;
    const current = sae.value;
    const values = saeFor(cat.value);
    sae.replaceChildren(new Option('Todas', ''), ...values.map((v) => new Option(v, v)));
    if (values.includes(current)) sae.value = current;
  };
  const update = () => {
    if (!index.length || !btnLabel) return;
    const n = index.filter((p) => (!cat.value || p.category === cat.value) && (!sae.value || p.sae === sae.value)).length;
    btnLabel.textContent = n ? `Ver ${n} ${n === 1 ? 'producto' : 'productos'}` : 'Sin resultados';
  };

  cat.addEventListener('change', () => { fillSae(); update(); });
  sae.addEventListener('change', update);
  form.addEventListener('submit', () => {                 // envío GET nativo; sin campos vacíos en la URL
    [cat, sae].forEach((f) => { if (!f.value) f.disabled = true; });
  });
  window.addEventListener('pageshow', () => { cat.disabled = false; sae.disabled = false; });

  fillSae();
  update();
})();
