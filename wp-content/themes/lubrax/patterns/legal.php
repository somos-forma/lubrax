<?php
/**
 * Title: Página legal
 * Slug: lubrax/legal
 * Categories: lubrax-paginas
 * Keywords: legal, privacidad, términos, condiciones, políticas
 * Post Types: page
 * Viewport Width: 1440
 * Description: Encabezado compacto con fecha de actualización, documento legal con índice lateral y llamada a la acción.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

echo '<!-- wp:lubrax/page-hero ' . serialize_block_attributes( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'lead'    => 'Resumen del documento en una o dos líneas.',
		'compact' => true,
		'updated' => wp_date( 'Y-m' ),
	)
) . " /-->\n\n";
?>
<!-- wp:lubrax/legal-document -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Título de la primera sección</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Texto de la sección. Cada título H2 abre una sección numerada en el índice lateral.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Título de la segunda sección</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"is-style-lbx-checks"} -->
<ul class="wp-block-list is-style-lbx-checks"><!-- wp:list-item -->
<li>Lista con el estilo «Lista con check».</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph {"className":"is-style-lbx-note"} -->
<p class="is-style-lbx-note"><strong>Nota destacada.</strong> Párrafo con el estilo «Nota destacada».</p>
<!-- /wp:paragraph -->
<!-- /wp:lubrax/legal-document -->

<?php
echo '<!-- wp:lubrax/cta-band ' . serialize_block_attributes( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'title'          => '¿Quieres ser distribuidor Lubrax?',
		'text'           => 'Súmate a la red de distribuidores autorizados y accede a capacitación, material de punto de venta y soporte técnico.',
		'primaryLabel'   => 'Quiero ser distribuidor',
		'primaryUrl'     => wp_make_link_relative( lbx_contact_url( 'distribuidor' ) ),
		'secondaryLabel' => 'Contacto',
		'secondaryUrl'   => wp_make_link_relative( lbx_page_url( 'contacto' ) ),
	)
) . ' /-->';
