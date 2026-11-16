<?php
/**
 * Title: Página Sobre Lubrax
 * Slug: lubrax/about
 * Categories: lubrax-paginas
 * Keywords: nosotros, quiénes somos, empresa, institucional
 * Post Types: page
 * Viewport Width: 1440
 * Description: Encabezado, texto e imagen, cifras, banda «Más que un lubricante», preguntas frecuentes y llamada a la acción.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

echo '<!-- wp:lubrax/page-hero ' . serialize_block_attributes( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'lead'    => 'Lubricantes de alto desempeño fabricados en Chile, con respaldo técnico y una red de distribuidores autorizados.',
		'compact' => false,
	)
) . " /-->\n\n";

require __DIR__ . '/about-intro.php';
echo "\n";
require __DIR__ . '/stats.php';
echo "\n";

foreach ( array( 'image-band', 'faq' ) as $lbx_section ) {
	echo lbx_acf_block_markup( 'lubrax/' . $lbx_section, lbx_block_defaults( $lbx_section ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

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
