<?php
/**
 * Soportes del tema, ubicaciones de menú y estilos del editor.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'lubrax', LBX_DIR . '/languages' );

		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

		// El extracto de una página es la bajada de su encabezado (bloque lubrax/page-hero).
		add_post_type_support( 'page', 'excerpt' );

		// Solo los patrones del tema: sin los de core ni los del directorio remoto.
		remove_theme_support( 'core-block-patterns' );

		// Los menús se editan en Apariencia > Menús y los imprime el walker con el marcado de la maqueta.
		register_nav_menus(
			array(
				'primary'             => __( 'Header: menú principal', 'lubrax' ),
				'offcanvas'           => __( 'Menú lateral: enlaces', 'lubrax' ),
				'offcanvas-secondary' => __( 'Menú lateral: enlaces secundarios', 'lubrax' ),
				'footer-explore'      => __( 'Footer: Explora', 'lubrax' ),
				'footer-legal'        => __( 'Footer: enlaces legales', 'lubrax' ),
			)
		);

		// El editor usa los mismos estilos que el sitio.
		add_theme_support( 'editor-styles' );
		add_editor_style(
			array(
				'assets/vendor/bootstrap/bootstrap.min.css',
				'assets/css/main.css',
				'assets/css/wp-compat.css',
				'assets/css/editor.css',
			)
		);
	}
);

add_filter( 'should_load_remote_block_patterns', '__return_false' );

// Patrones del tema (/patterns): páginas completas y secciones.
add_action(
	'init',
	function () {
		register_block_pattern_category( 'lubrax-paginas', array( 'label' => __( 'Lubrax: páginas', 'lubrax' ) ) );
		register_block_pattern_category( 'lubrax-secciones', array( 'label' => __( 'Lubrax: secciones', 'lubrax' ) ) );
	}
);
