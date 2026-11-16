<?php
/**
 * Bloques del tema: cada carpeta de /blocks con block.json + render.php es un bloque dinámico
 * (se renderiza en PHP con el marcado de la maqueta). La interfaz del editor está en assets/js/editor/blocks.js.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		foreach ( glob( LBX_DIR . '/blocks/*/block.json' ) as $metadata ) {
			register_block_type( dirname( $metadata ) );
		}
	}
);

add_filter(
	'block_categories_all',
	function ( array $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'lubrax',
				'title' => 'Lubrax',
				'icon'  => null,
			)
		);
		return $categories;
	}
);
