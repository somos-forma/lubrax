<?php
/**
 * Title: Página de contacto
 * Slug: lubrax/contact
 * Categories: lubrax-paginas
 * Keywords: contacto, formulario, atención, servicios
 * Post Types: page
 * Viewport Width: 1440
 * Description: Encabezado con canales de atención, servicios, formulario de Contact Form 7 con preguntas de atención y llamada a la acción.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

echo '<!-- wp:lubrax/page-hero ' . serialize_block_attributes( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'lead'     => 'Atención al cliente para pedidos, disponibilidad, garantías y reclamos. Un ejecutivo responde cada mensaje y te acompaña hasta resolverlo.',
		'compact'  => false,
		'channels' => true,
	)
) . " /-->\n\n";

foreach ( array( 'contact-services', 'contact-form' ) as $lbx_section ) {
	echo lbx_acf_block_markup( 'lubrax/' . $lbx_section, lbx_block_defaults( $lbx_section ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

echo '<!-- wp:lubrax/cta-band ' . serialize_block_attributes( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'title'          => 'Descarga la documentación',
		'text'           => 'Fichas técnicas y hojas de seguridad de cada producto, disponibles en su ficha del catálogo.',
		'primaryLabel'   => 'Ir al catálogo',
		'primaryUrl'     => wp_make_link_relative( lbx_page_url( 'productos' ) ),
		'secondaryLabel' => 'Documentación técnica',
		'secondaryUrl'   => wp_make_link_relative( lbx_page_url( 'documentacion-tecnica' ) ),
	)
) . ' /-->';
