/*!
 * Lubrax · contact.js — formulario de contacto (contacto.html)
 *
 *   <select id="contactType" name="tipo">  <option value="distribuidor" data-hint="…">
 *   <fieldset data-show-for="distribuidor reclamo" hidden disabled>…</fieldset>
 *       Un <fieldset disabled> no se valida ni se envía: los campos condicionales
 *       pueden llevar "required" sin bloquear los otros tipos de solicitud.
 *   [data-type-hint]                       ayuda bajo el selector (data-hint de la opción)
 *   [data-contact-type="reclamo"]          botones que eligen el tipo y bajan al formulario
 *   ?tipo=distribuidor&producto=valora-5w-30  preselecciona desde otras páginas
 */
(() => {
  'use strict';

  const form = document.getElementById('contactForm');
  if (!form || !window.LBX) return;
  const { $, $$, params, reduceMotion } = LBX.utils;
  const type = $('#contactType', form);
  const hint = $('[data-type-hint]', form);
  const blocks = $$('[data-show-for]', form);

  const sync = () => {
    const v = type.value;
    blocks.forEach((b) => {
      const on = b.dataset.showFor.split(/\s+/).includes(v);
      b.hidden = !on;
      b.disabled = !on;
    });
    if (hint) hint.textContent = type.selectedOptions[0]?.dataset.hint || '';
  };
  type.addEventListener('change', sync);

  /* Preselección desde la URL */
  const p = params();
  const wanted = p.get('tipo');
  if (wanted && [...type.options].some((o) => o.value === wanted)) type.value = wanted;
  const prod = p.get('producto');
  if (prod) $$('select[name="producto"]', form).forEach((s) => { if ([...s.options].some((o) => o.value === prod)) s.value = prod; });
  sync();

  /* Si hay distribuidor o región elegidos en el header, se usa como región por defecto */
  const zone = $('[name="zona"]', form);
  const fillZone = () => { const r = LBX.distributor?.scope().region; if (zone && r && !zone.value) zone.value = r; };
  fillZone();
  document.addEventListener('lbx:distributorchange', fillZone);

  /* Accesos directos desde las tarjetas de servicio */
  $$('[data-contact-type]').forEach((btn) => btn.addEventListener('click', (e) => {
    e.preventDefault();
    type.value = btn.dataset.contactType;
    sync();
    const target = document.getElementById('formulario') || form;
    target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    setTimeout(() => $('input:not([type="hidden"]), textarea', form)?.focus({ preventScroll: true }), reduceMotion ? 0 : 600);
  }));
})();
