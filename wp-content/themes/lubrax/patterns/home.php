<?php
/**
 * Title: Página de inicio
 * Slug: lubrax/home
 * Categories: lubrax-paginas
 * Keywords: inicio, home, portada, slider
 * Post Types: page
 * Viewport Width: 1440
 * Description: Slider, catálogo regional, categorías, buscador, productos destacados, planta, noticias y llamada a la acción.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

foreach ( array( 'hero-slider', 'intro', 'segments', 'product-finder', 'featured-products', 'plant', 'latest-posts' ) as $lbx_section ) {
	echo lbx_acf_block_markup( 'lubrax/' . $lbx_section, lbx_block_defaults( $lbx_section ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

echo '<!-- wp:lubrax/cta-band ' . serialize_block_attributes( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'title'          => '¿Quieres ser distribuidor Lubrax?',
		'text'           => 'Súmate a la red de distribuidores autorizados y accede a capacitación, material de punto de venta y soporte técnico.',
		'primaryLabel'   => 'Quiero ser distribuidor',
		'primaryUrl'     => wp_make_link_relative( lbx_contact_url( 'distribuidor' ) ),
		'secondaryLabel' => 'Buscar distribuidor',
	)
) . ' /-->';
