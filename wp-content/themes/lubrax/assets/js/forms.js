/*!
 * Lubrax · forms.js — envío de formularios de Contact Form 7 (nuevo, no estaba en la maqueta)
 *
 * app.js valida con Bootstrap y emite 'lbx:formsubmit'. Si el formulario tiene data-cf7-endpoint
 * (inc/contact.php), aquí se cancela el envío normal y se manda por la API REST de CF7:
 *
 *   mail_sent          → [data-form-success] del contenedor [data-form-wrap] (o un toast: newsletter)
 *   validation_failed  → marca los campos con el mensaje del servidor (setCustomValidity + .was-validated)
 *   spam / mail_failed → [data-form-error] (o un toast)
 *
 * También emite en el contenedor .wpcf7 los eventos de CF7 (wpcf7submit, wpcf7mailsent, wpcf7invalid,
 * wpcf7spam, wpcf7mailfailed) para analítica y para que Turnstile y reCAPTCHA renueven su token.
 */
(() => {
  'use strict';

  if (!window.LBX) return;
  const { $, $$, reduceMotion } = LBX.utils;
  const EVENTS = { mail_sent: 'wpcf7mailsent', validation_failed: 'wpcf7invalid', spam: 'wpcf7spam', mail_failed: 'wpcf7mailfailed' };

  const wrapOf = (form) => form.closest('[data-form-wrap]');

  const showError = (form, message) => {
    const box = $('[data-form-error]', wrapOf(form) || form);
    if (!box) { LBX.toast(message); return; }
    box.textContent = message;
    box.hidden = false;
  };

  const clearServerErrors = (form) => {
    $$('[data-server-invalid]', form).forEach((field) => {
      field.setCustomValidity('');
      field.removeAttribute('data-server-invalid');
    });
    const box = $('[data-form-error]', wrapOf(form) || form);
    if (box) box.hidden = true;
  };

  /* Errores del servidor: el campo queda :invalid hasta que se edite. Si no tiene .invalid-feedback, se crea. */
  const markInvalid = (form, fields) => {
    fields.forEach(({ field, message }) => {
      const input = form.elements[field] || form.elements[`${field}[]`];
      const el = input instanceof RadioNodeList ? input[0] : input;
      if (!el) return;
      el.setCustomValidity(message);
      el.setAttribute('data-server-invalid', '');
      el.addEventListener('input', () => el.setCustomValidity(''), { once: true });
      el.addEventListener('change', () => el.setCustomValidity(''), { once: true });
      const holder = el.closest('.form-check') || el.closest('.wpcf7-form-control-wrap')?.parentElement;
      if (holder && !$('.invalid-feedback', holder)) {
        const fb = document.createElement('div');
        fb.className = 'invalid-feedback';
        fb.textContent = message;
        holder.append(fb);
      }
    });
    form.classList.add('was-validated');
    form.querySelector(':invalid')?.focus();
  };

  const showSuccess = (form, data) => {
    const wrap = wrapOf(form);
    const box = wrap && $('[data-form-success]', wrap);
    if (!box) {
      LBX.toast(data.message);
    } else {
      const caseBox = $('[data-form-case]', box);
      if (caseBox && data.lbx_case) {
        $('strong', caseBox).textContent = data.lbx_case;
        caseBox.hidden = false;
      }
      form.closest('.wpcf7').hidden = true;
      box.hidden = false;
      box.focus({ preventScroll: true });
      box.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    }
    form.reset();
    form.classList.remove('was-validated');
    $('#contactType', form)?.dispatchEvent(new Event('change'));
  };

  document.addEventListener('lbx:formsubmit', async (e) => {
    const { form } = e.detail;
    const endpoint = form.dataset.cf7Endpoint;
    if (!endpoint) return;
    e.preventDefault();
    if (form.getAttribute('aria-busy') === 'true') return;

    clearServerErrors(form);
    const button = $('[type="submit"]', form);
    form.setAttribute('aria-busy', 'true');
    if (button) button.disabled = true;

    let data;
    try {
      const response = await fetch(endpoint, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      data = await response.json();
      if (!data.status) throw new Error(data.message || response.statusText);
    } catch {
      data = { status: 'mail_failed', message: 'No pudimos enviar el formulario. Revisa tu conexión e inténtalo de nuevo.' };
    } finally {
      form.removeAttribute('aria-busy');
      if (button) button.disabled = false;
    }

    if (data.status === 'mail_sent') showSuccess(form, data);
    else if (data.status === 'validation_failed') { markInvalid(form, data.invalid_fields || []); showError(form, data.message); }
    else showError(form, data.message);

    const container = form.closest('.wpcf7') || form;
    ['wpcf7submit', EVENTS[data.status]].filter(Boolean).forEach((type) => {
      container.dispatchEvent(new CustomEvent(type, { bubbles: true, detail: { contactFormId: Number(form.elements._wpcf7?.value), apiResponse: data } }));
    });
  });

  /* "Enviar otro mensaje" */
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-form-reset]');
    if (!btn) return;
    const wrap = btn.closest('[data-form-wrap]');
    const box = btn.closest('[data-form-success]');
    const container = $('.wpcf7', wrap);
    if (!box || !container) return;
    box.hidden = true;
    container.hidden = false;
    $('select, input:not([type="hidden"])', container)?.focus();
  });
})();
