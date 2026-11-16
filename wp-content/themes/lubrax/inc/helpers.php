<?php
/**
 * Funciones de plantilla compartidas.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL de un archivo dentro de /assets del tema.
 */
function lbx_asset( string $path ): string {
	return LBX_URI . '/assets/' . ltrim( $path, '/' );
}

/**
 * Ícono del sprite SVG (ver inc/globals.php). Decorativo: aria-hidden.
 */
function lbx_icon( string $id, string $class = 'icon' ): string {
	return sprintf(
		'<svg class="%1$s" aria-hidden="true" focusable="false"><use href="#i-%2$s"></use></svg>',
		esc_attr( $class ),
		esc_attr( $id )
	);
}

/**
 * Texto accesible para enlaces que abren en otra pestaña.
 */
function lbx_new_tab_label(): string {
	return '<span class="visually-hidden"> ' . esc_html__( '(abre en una pestaña nueva)', 'lubrax' ) . '</span>';
}

/**
 * "800 363 776" -> "tel:800363776". Conserva el "+" inicial.
 */
function lbx_tel_href( string $phone ): string {
	return 'tel:' . preg_replace( '/(?!^\+)[^\d]/', '', trim( $phone ) );
}

/**
 * Enlace de WhatsApp del sitio (Opciones del tema): https://wa.me/56939171917.
 */
function lbx_whatsapp_url(): string {
	return 'https://wa.me/' . preg_replace( '/\D/', '', lbx_option( 'whatsapp' ) );
}

/**
 * URL de una página clave del sitio, por su slug. Si la página no existe, usa /{slug}/.
 *
 * El catálogo usa la tienda de WooCommerce cuando está activa.
 */
function lbx_page_url( string $slug ): string {
	static $cache = array();

	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}

	if ( 'productos' === $slug && function_exists( 'wc_get_page_permalink' ) ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop && home_url( '/' ) !== $shop ) {
			return $cache[ $slug ] = $shop;
		}
	}

	$page = get_page_by_path( $slug );

	return $cache[ $slug ] = $page ? get_permalink( $page ) : home_url( user_trailingslashit( $slug ) );
}

/**
 * Título del encabezado de página según el contexto (entrada/página, blog, archivo o búsqueda).
 */
function lbx_page_hero_title( int $post_id = 0 ): string {
	if ( is_search() ) {
		/* translators: %s: términos buscados. */
		return sprintf( __( 'Resultados para “%s”', 'lubrax' ), get_search_query() );
	}
	if ( is_home() && ! is_front_page() ) {
		return get_the_title( (int) get_option( 'page_for_posts' ) );
	}
	if ( is_archive() ) {
		return wp_strip_all_tags( get_the_archive_title() );
	}
	if ( is_404() ) {
		return __( 'Página no encontrada', 'lubrax' );
	}

	return get_the_title( $post_id );
}

/**
 * Clave SCF de un campo según el tipo de contenido de $post_id.
 *
 * Escribir siempre por clave: con update_field( 'nombre' ) en un registro nuevo, SCF busca el campo por nombre
 * en todos los grupos y puede elegir el equivocado ("presentaciones" existe en producto y en distribuidor).
 */
function lbx_field_key( string $name, int $post_id ): string {
	$keys = array(
		'product'      => array(
			'presentaciones' => 'field_lbx_prod_presentaciones',
			'aplicaciones'   => 'field_lbx_prod_aplicaciones',
			'normas'         => 'field_lbx_prod_normas',
			'propiedades'    => 'field_lbx_prod_propiedades',
			'documentos'     => 'field_lbx_prod_documentos',
			'distribuidores' => 'field_lbx_prod_distribuidores',
			'relacionados'   => 'field_lbx_prod_relacionados',
		),
		'distribuidor' => array(
			'zona'           => 'field_lbx_dist_zona',
			'tipo_compra'    => 'field_lbx_dist_tipo_compra',
			'presentaciones' => 'field_lbx_dist_presentaciones',
			'capacidad'      => 'field_lbx_dist_capacidad',
			'telefono'       => 'field_lbx_dist_telefono',
			'email'          => 'field_lbx_dist_email',
			'web'            => 'field_lbx_dist_web',
			'horario'        => 'field_lbx_dist_horario',
			'direccion'      => 'field_lbx_dist_direccion',
			'region'         => 'field_lbx_dist_region',
			'ciudad'         => 'field_lbx_dist_ciudad',
			'link_maps'      => 'field_lbx_dist_maps',
			'lat'            => 'field_lbx_dist_lat',
			'lng'            => 'field_lbx_dist_lng',
			'productos'      => 'field_lbx_dist_productos',
		),
	);

	return $keys[ get_post_type( $post_id ) ][ $name ] ?? $name;
}

/**
 * get_field() por clave (ver lbx_field_key()). Sin SCF activo devuelve null.
 */
function lbx_get_field( string $name, int $post_id, bool $format = true ) {
	return function_exists( 'get_field' ) ? get_field( lbx_field_key( $name, $post_id ), $post_id, $format ) : null;
}

/**
 * update_field() por clave (ver lbx_field_key()).
 */
function lbx_update_field( string $name, $value, int $post_id ): bool {
	return (bool) update_field( lbx_field_key( $name, $post_id ), $value, $post_id );
}

/**
 * URL del formulario de contacto con un motivo preseleccionado (?tipo=…#formulario).
 */
function lbx_contact_url( string $tipo = '' ): string {
	$url = lbx_page_url( 'contacto' );

	if ( $tipo ) {
		$url = add_query_arg( 'tipo', $tipo, $url );
	}

	return $url . '#formulario';
}
