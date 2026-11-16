<?php
/**
 * Carga de estilos y scripts.
 *
 * Orden igual que en la maqueta (todos con defer): bootstrap.bundle → app.js → script de la página.
 * Los estilos se encolan en prioridad 20, después de los estilos globales de theme.json, para que
 * main.css gane los empates de especificidad.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versión de un asset: fecha de modificación en local (evita caché al desarrollar), versión del tema en producción.
 */
function lbx_asset_version( string $path ): string {
	if ( 'production' !== wp_get_environment_type() ) {
		$file = LBX_DIR . '/assets/' . $path;
		return file_exists( $file ) ? (string) filemtime( $file ) : LBX_VERSION;
	}
	return LBX_VERSION;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$defer = array(
			'strategy'  => 'defer',
			'in_footer' => false,
		);

		// Estilos.
		wp_enqueue_style( 'lbx-bootstrap', lbx_asset( 'vendor/bootstrap/bootstrap.min.css' ), array(), '5.3.3' );
		wp_enqueue_style( 'lbx-main', lbx_asset( 'css/main.css' ), array( 'lbx-bootstrap' ), lbx_asset_version( 'css/main.css' ) );
		wp_enqueue_style( 'lbx-wp-compat', lbx_asset( 'css/wp-compat.css' ), array( 'lbx-main' ), lbx_asset_version( 'css/wp-compat.css' ) );
		// Componentes nuevos que no estaban en la maqueta (repositorio de documentos…), con los tokens de main.css.
		wp_enqueue_style( 'lbx-components', lbx_asset( 'css/components.css' ), array( 'lbx-main' ), lbx_asset_version( 'css/components.css' ) );

		// Núcleo (todas las páginas).
		wp_enqueue_script( 'lbx-bootstrap', lbx_asset( 'vendor/bootstrap/bootstrap.bundle.min.js' ), array(), '5.3.3', $defer );
		wp_enqueue_script( 'lbx-app', lbx_asset( 'js/app.js' ), array( 'lbx-bootstrap' ), lbx_asset_version( 'js/app.js' ), $defer );

		// Scripts de página: se registran aquí y se encolan desde cada plantilla o bloque que los necesita.
		$page_scripts = array(
			'lbx-slider'  => 'js/slider.js',
			'lbx-finder'  => 'js/finder.js',
			'lbx-catalog' => 'js/catalog.js',
			'lbx-product' => 'js/product.js',
			'lbx-locator' => 'js/locator.js',
			'lbx-blog'    => 'js/blog.js',
			'lbx-contact' => 'js/contact.js',
			'lbx-docs'    => 'js/docs.js', // Repositorio de documentación técnica (nuevo, no estaba en la maqueta).
			'lbx-forms'   => 'js/forms.js', // Envío de formularios de Contact Form 7 (nuevo, ver inc/contact.php).
		);
		// Leaflet (solo en el localizador de distribuidores; se carga como dependencia de lbx-locator).
		wp_register_style( 'lbx-leaflet', lbx_asset( 'vendor/leaflet/leaflet.css' ), array(), '1.9.4' );
		wp_register_script( 'lbx-leaflet', lbx_asset( 'vendor/leaflet/leaflet.js' ), array(), '1.9.4', $defer );

		$page_deps = array( 'lbx-locator' => array( 'lbx-app', 'lbx-leaflet' ) );
		foreach ( $page_scripts as $handle => $path ) {
			wp_register_script( $handle, lbx_asset( $path ), $page_deps[ $handle ] ?? array( 'lbx-app' ), lbx_asset_version( $path ), $defer );
		}
	},
	20
);

/**
 * <head>: clase "js" antes del primer pintado (activa las animaciones de scroll), metadatos y precarga de fuentes.
 */
add_action(
	'wp_head',
	function () {
		echo "<script>document.documentElement.classList.add('js')</script>\n";
		echo '<meta name="theme-color" content="#032b1a">' . "\n";
		echo '<meta name="format-detection" content="telephone=no">' . "\n";

		// Misma URL que genera theme.json para @font-face, así el navegador reutiliza la descarga.
		foreach ( array( 'saira-var.woff2', 'archivo-var.woff2' ) as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( lbx_asset( 'fonts/' . $font ) )
			);
		}
	},
	1
);

/**
 * Favicon del tema mientras no se configure un ícono del sitio (Ajustes > Generales).
 */
add_action(
	'wp_head',
	function () {
		if ( has_site_icon() ) {
			return;
		}
		printf( '<link rel="icon" href="%s" sizes="32x32">' . "\n", esc_url( lbx_asset( 'img/favicon/favicon-32.png' ) ) );
		printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( lbx_asset( 'img/favicon/favicon.svg' ) ) );
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( lbx_asset( 'img/favicon/apple-touch-icon.png' ) ) );
	},
	8
);

/**
 * Editor: script de los bloques del tema (ver inc/blocks.php).
 */
add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'lbx-editor-blocks',
			lbx_asset( 'js/editor/blocks.js' ),
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-server-side-render', 'wp-i18n' ),
			lbx_asset_version( 'js/editor/blocks.js' ),
			true
		);
	}
);
