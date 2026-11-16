<?php
/**
 * Lubrax · functions.php
 *
 * Solo carga los módulos de /inc. Cada archivo agrupa una responsabilidad.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

define( 'LBX_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'LBX_DIR', get_template_directory() );
define( 'LBX_URI', get_template_directory_uri() );

$lbx_modules = array(
	'helpers',       // Funciones de plantilla: lbx_asset(), lbx_icon(), lbx_page_url()...
	'setup',         // Soportes del tema, menús y estilos del editor.
	'theme-options', // Opciones del tema (SCF, con pestañas): lbx_option().
	'nav-walker',    // Walker de menús con el marcado de la maqueta.
	'assets',        // Bootstrap, main.css, JS y precargas.
	'globals',       // Sprite SVG, menú lateral, modal de distribuidor y "volver arriba".
	'blocks',        // Bloques del tema (header, footer, encabezado de página).
	'media',         // WebP/AVIF y tamaños de imagen.
	'cleanup',       // Limpieza de <head> y recursos innecesarios.
	'post-types',    // CPT Distribuidores.
	'taxonomies',    // Segmento, Tipo de producto, Especificación, Base, Combustible y Viscosidad.
	'distributors',  // Datos de distribuidores para el front (modal, mapa, ficha) y coordenadas.
	'woocommerce',   // WooCommerce en modo catálogo (solo cotización).
	'products',      // Datos de producto: tarjeta, ficha, documentos PDF y Schema Product.
	'blog',          // Blog: tarjeta de artículo, barra lateral y Schema BlogPosting.
	'acf-blocks',    // Bloques SCF (home, Sobre Lubrax): helpers y contenido por defecto de la maqueta.
	'core-blocks',   // Bloques core con el diseño de la maqueta (botones, animaciones, cifras).
	'contact',       // Formularios con Contact Form 7: motivos, enrutamiento por área, antispam y CFDB7.
	'seo',           // SEO: metadatos sociales, canónicas, sitemap, Organization y 404 sin redirección.
	'roles',         // Administrador Técnico y Editor de Contenido.
	'importer/importer', // Importador Excel (Productos > Importar desde Excel y WP-CLI).
);

foreach ( $lbx_modules as $lbx_module ) {
	require_once LBX_DIR . '/inc/' . $lbx_module . '.php';
}
unset( $lbx_modules, $lbx_module );
