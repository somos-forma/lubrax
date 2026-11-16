<?php
/**
 * Imágenes: subidas JPEG/PNG se guardan como WebP (también sus tamaños). AVIF y WebP se aceptan tal cual.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'image_editor_output_format',
	function ( array $formats ) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
		return $formats;
	}
);

add_filter(
	'wp_editor_set_quality',
	function ( int $quality, string $mime_type ) {
		return in_array( $mime_type, array( 'image/webp', 'image/avif' ), true ) ? 80 : $quality;
	},
	10,
	2
);

// Las imágenes más grandes se reducen a 2000 px (el hero de la maqueta usa 1200–1600 px).
add_filter( 'big_image_size_threshold', fn() => 2000 );

add_action(
	'after_setup_theme',
	function () {
		// Tamaños de la maqueta: tarjetas del blog (640 × 360) y hero de páginas (1600 px).
		add_image_size( 'lbx-card', 640, 360, true );
		add_image_size( 'lbx-hero', 1600, 0 );
	}
);
