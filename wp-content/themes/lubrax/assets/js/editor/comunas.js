/*!
 * Lubrax · editor/comunas.js — select dependiente Región → Comuna en la ficha del distribuidor.
 *
 * El campo "Comuna" trae todas las comunas agrupadas por región (<optgroup label="Región">, inc/distributors.php).
 * Al elegir una región solo quedan sus comunas; si se elige la comuna sin región, la región se completa sola.
 */
(() => {
  'use strict';

  if (!window.acf) return;

  acf.addAction('ready', () => {
    const region = acf.getField('field_lbx_dist_region');
    const comuna = acf.getField('field_lbx_dist_ciudad');
    if (!region?.$el?.length || !comuna?.$el?.length) return;

    const select = comuna.$input()[0];
    const empty = select.querySelector('option[value=""]')?.cloneNode(true);
    const groups = [...select.querySelectorAll('optgroup')].map((g) => g.cloneNode(true));

    const regionOf = (value) => groups.find((g) => [...g.children].some((o) => o.value === value))?.label || '';

    const filter = () => {
      const current = select.value;
      const selected = region.val();
      const visible = groups.filter((g) => !selected || g.label === selected).map((g) => g.cloneNode(true));
      select.replaceChildren(...[empty?.cloneNode(true), ...visible].filter(Boolean));
      select.value = [...select.options].some((o) => o.value === current) ? current : '';
    };

    region.$input().on('change', filter);
    comuna.$input().on('change', () => {
      const parent = regionOf(select.value);
      if (parent && !region.val()) {
        region.val(parent);
        region.$input().trigger('change');
      }
    });
    filter();
  });
})();
