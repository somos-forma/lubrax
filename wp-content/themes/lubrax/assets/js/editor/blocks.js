/*!
 * Lubrax · editor/blocks.js — interfaz del editor para los bloques dinámicos del tema.
 * Sin compilación: usa los globales wp.* que carga WordPress.
 * El marcado lo genera el render.php de cada bloque (vista previa con ServerSideRender).
 * Excepción: lubrax/legal-document guarda su contenido (bloques internos) y el render.php lo ordena.
 */
(() => {
  'use strict';

  const { registerBlockType } = wp.blocks;
  const { createElement: el, Fragment } = wp.element;
  const { InspectorControls, InnerBlocks, useBlockProps } = wp.blockEditor;
  const { PanelBody, TextControl, TextareaControl, ToggleControl, Disabled } = wp.components;
  const { __ } = wp.i18n;
  const ServerSideRender = wp.serverSideRender;

  /* Campos de la barra lateral por bloque. type: text | textarea | toggle | number | date */
  const BLOCKS = {
    'lubrax/site-header': [
      { key: 'showCta', type: 'toggle', label: __('Mostrar botón', 'lubrax') },
      { key: 'ctaLabel', type: 'text', label: __('Texto del botón', 'lubrax') },
      { key: 'ctaTipo', type: 'text', label: __('Motivo del formulario de contacto (?tipo=)', 'lubrax') }
    ],
    'lubrax/site-footer': [
      { key: 'showNewsletter', type: 'toggle', label: __('Mostrar newsletter', 'lubrax') },
      { key: 'newsletterTitle', type: 'text', label: __('Título del newsletter', 'lubrax') }
    ],
    'lubrax/page-hero': [
      { key: 'lead', type: 'textarea', label: __('Bajada (si está vacía, se usa el extracto)', 'lubrax') },
      { key: 'compact', type: 'toggle', label: __('Versión compacta', 'lubrax') },
      { key: 'channels', type: 'toggle', label: __('Canales de atención (Opciones del tema > Contacto)', 'lubrax') },
      { key: 'updated', type: 'date', label: __('Última actualización (páginas legales; vacío: no se muestra)', 'lubrax') }
    ],
    'lubrax/product-catalog': [
      { key: 'title', type: 'text', label: __('Título (en la tienda; en un segmento se usa su nombre)', 'lubrax') }
      // Banner de la columna de filtros: Opciones del tema > Banner del catálogo.
    ],
    'lubrax/product-page': [
      { key: 'wholesaleTitle', type: 'text', label: __('Recuadro de volumen: título', 'lubrax') },
      { key: 'wholesaleText', type: 'textarea', label: __('Recuadro de volumen: texto', 'lubrax') },
      { key: 'relatedTitle', type: 'text', label: __('Título de productos relacionados', 'lubrax') }
    ],
    'lubrax/distributor-locator': [],
    'lubrax/post-list': [
      { key: 'lead', type: 'textarea', label: __('Bajada (en una categoría se usa su descripción)', 'lubrax') }
    ],
    'lubrax/post-article': [],
    'lubrax/doc-library': [
      { key: 'perPage', type: 'number', label: __('Documentos por página', 'lubrax') }
    ],
    'lubrax/error-404': [], // Textos en Opciones del tema > Página 404
    'lubrax/search-results': [
      { key: 'products', type: 'number', label: __('Productos a mostrar', 'lubrax') },
      { key: 'posts', type: 'number', label: __('Artículos a mostrar', 'lubrax') }
    ],
    'lubrax/cta-band': [
      { key: 'title', type: 'text', label: __('Título', 'lubrax') },
      { key: 'text', type: 'textarea', label: __('Texto', 'lubrax') },
      { key: 'primaryLabel', type: 'text', label: __('Botón principal: texto (vacío: sin botón)', 'lubrax') },
      { key: 'primaryUrl', type: 'text', label: __('Botón principal: enlace (vacío: consulta técnica)', 'lubrax') },
      { key: 'secondaryLabel', type: 'text', label: __('Botón secundario: texto (vacío: sin botón)', 'lubrax') },
      { key: 'secondaryUrl', type: 'text', label: __('Botón secundario: enlace (vacío: distribuidores)', 'lubrax') }
    ]
  };

  const CONTROLS = { text: TextControl, textarea: TextareaControl, toggle: ToggleControl, number: TextControl, date: TextControl };

  const control = (field, attributes, setAttributes) => {
    const props = { key: field.key, label: field.label, __nextHasNoMarginBottom: true };
    if (field.type === 'toggle') {
      props.checked = !!attributes[field.key];
      props.onChange = (value) => setAttributes({ [field.key]: value });
    } else if (field.type === 'number') {
      props.type = 'number';
      props.min = 1;
      props.value = attributes[field.key] ?? '';
      props.onChange = (value) => setAttributes({ [field.key]: Math.max(1, parseInt(value, 10) || 1) });
    } else {
      if (field.type === 'date') props.type = 'date';
      props.value = attributes[field.key] || '';
      props.onChange = (value) => setAttributes({ [field.key]: value });
    }
    return el(CONTROLS[field.type], props);
  };

  /* En el editor de entradas/páginas, la vista previa recibe el ID para mostrar su título, extracto e imagen */
  const currentPostArgs = () => {
    const id = wp.data.select('core/editor')?.getCurrentPostId?.();
    return Number.isInteger(id) ? { post_id: id } : {};
  };

  /* Botón: solo los estilos de Lubrax (inc/core-blocks.php), sin "Relleno" ni "Contorno" de core */
  wp.domReady(() => wp.blocks.unregisterBlockStyle('core/button', ['fill', 'outline']));

  Object.entries(BLOCKS).forEach(([name, fields]) => {
    registerBlockType(name, {
      edit: ({ attributes, setAttributes }) => el(
        Fragment,
        null,
        fields.length ? el(
          InspectorControls,
          null,
          el(PanelBody, { title: __('Ajustes', 'lubrax') }, fields.map((f) => control(f, attributes, setAttributes)))
        ) : null,
        el('div', useBlockProps(), el(Disabled, null, el(ServerSideRender, { block: name, attributes, urlQueryArgs: currentPostArgs() })))
      ),
      save: () => null
    });
  });

  /* Documento legal: el texto se escribe dentro del bloque. Cada título H2 es una sección del índice. */
  const LEGAL_FIELDS = [
    { key: 'tocTitle', type: 'text', label: __('Título del índice', 'lubrax') },
    { key: 'showContact', type: 'toggle', label: __('Sección de contacto al final', 'lubrax') },
    { key: 'contactTitle', type: 'text', label: __('Contacto: título', 'lubrax') },
    { key: 'contactText', type: 'textarea', label: __('Contacto: texto', 'lubrax') },
    { key: 'contactEmail', type: 'text', label: __('Contacto: correo (vacío: correo de atención)', 'lubrax') }
  ];
  const LEGAL_TEMPLATE = [
    ['core/heading', { level: 2, placeholder: __('Título de la sección', 'lubrax') }],
    ['core/paragraph', { placeholder: __('Texto de la sección', 'lubrax') }]
  ];
  registerBlockType('lubrax/legal-document', {
    edit: ({ attributes, setAttributes }) => el(
      Fragment,
      null,
      el(InspectorControls, null,
        el(PanelBody, { title: __('Ajustes', 'lubrax') },
          el('p', { className: 'components-base-control__help' }, __('Cada título H2 abre una sección numerada del índice. Usa el estilo «Nota destacada» en párrafos y «Lista con check» en listas.', 'lubrax')),
          LEGAL_FIELDS.map((f) => control(f, attributes, setAttributes)))),
      el('div', useBlockProps({ className: 'legal' }),
        el(InnerBlocks, {
          allowedBlocks: ['core/heading', 'core/paragraph', 'core/list', 'core/table', 'core/quote', 'core/separator'],
          template: LEGAL_TEMPLATE
        }))
    ),
    save: () => el(InnerBlocks.Content)
  });
})();
