<?php
/**
 * Taxonomías de producto (WooCommerce): Segmento, Tipo de producto, Especificación técnica, Base,
 * Combustible y Viscosidad. Todas públicas, con URL indexable (/segmento/automotriz/) y en la REST API.
 *
 * Las categorías y etiquetas propias de WooCommerce se ocultan: el catálogo se ordena con estas taxonomías.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definición de las taxonomías. La clave es el nombre interno; "slug" es la base de la URL.
 */
function lbx_product_taxonomies(): array {
	return array(
		'segmento'       => array(
			'singular'     => __( 'Segmento', 'lubrax' ),
			'plural'       => __( 'Segmentos', 'lubrax' ),
			'slug'         => 'segmento',
			'hierarchical' => true,
		),
		'tipo_producto'  => array(
			'singular'     => __( 'Tipo de producto', 'lubrax' ),
			'plural'       => __( 'Tipos de producto', 'lubrax' ),
			'slug'         => 'tipo-de-producto',
			'hierarchical' => true,
		),
		'especificacion' => array(
			'singular'     => __( 'Especificación técnica', 'lubrax' ),
			'plural'       => __( 'Especificaciones técnicas', 'lubrax' ),
			'slug'         => 'especificacion',
			'hierarchical' => false,
		),
		'base_aceite'    => array(
			'singular'     => __( 'Base', 'lubrax' ),
			'plural'       => __( 'Bases', 'lubrax' ),
			'slug'         => 'base',
			'hierarchical' => true,
		),
		'combustible'    => array(
			'singular'     => __( 'Combustible', 'lubrax' ),
			'plural'       => __( 'Combustibles', 'lubrax' ),
			'slug'         => 'combustible',
			'hierarchical' => true,
		),
		'viscosidad'     => array(
			'singular'     => __( 'Viscosidad', 'lubrax' ),
			'plural'       => __( 'Viscosidades', 'lubrax' ),
			'slug'         => 'viscosidad',
			'hierarchical' => false,
		),
	);
}

add_action(
	'init',
	function () {
		foreach ( lbx_product_taxonomies() as $taxonomy => $tax ) {
			register_taxonomy(
				$taxonomy,
				array( 'product' ),
				array(
					'labels'            => array(
						'name'                       => $tax['plural'],
						'singular_name'              => $tax['singular'],
						'menu_name'                  => $tax['plural'],
						/* translators: %s: nombre de la taxonomía en plural. */
						'all_items'                  => sprintf( __( 'Todos: %s', 'lubrax' ), $tax['plural'] ),
						/* translators: %s: nombre de la taxonomía en singular. */
						'edit_item'                  => sprintf( __( 'Editar: %s', 'lubrax' ), $tax['singular'] ),
						/* translators: %s: nombre de la taxonomía en singular. */
						'add_new_item'               => sprintf( __( 'Agregar: %s', 'lubrax' ), $tax['singular'] ),
						/* translators: %s: nombre de la taxonomía en plural. */
						'search_items'               => sprintf( __( 'Buscar: %s', 'lubrax' ), $tax['plural'] ),
						'not_found'                  => __( 'No se encontraron términos.', 'lubrax' ),
						'separate_items_with_commas' => __( 'Separa con comas', 'lubrax' ),
						'choose_from_most_used'      => __( 'Elegir entre los más usados', 'lubrax' ),
						'back_to_items'              => __( '← Volver', 'lubrax' ),
					),
					'public'            => true,
					'hierarchical'      => $tax['hierarchical'],
					'show_admin_column' => true,
					'show_in_rest'      => true,
					'show_in_nav_menus' => true,
					// Sin query var: los parámetros del catálogo (?combustible=, ?base=…) no deben filtrar la consulta
					// principal de WordPress. Las URL /segmento/automotriz/ funcionan igual (taxonomy=…&term=…).
					'query_var'         => false,
					'rewrite'           => array(
						'slug'       => $tax['slug'],
						'with_front' => false,
					),
					// Mismas capacidades que las categorías de WooCommerce (asignables por rol).
					'capabilities'      => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
				)
			);
		}
	}
);

/**
 * Oculta las categorías y etiquetas de WooCommerce (se reemplazan por Segmento y las demás taxonomías).
 */
foreach ( array( 'product_cat', 'product_tag' ) as $lbx_wc_taxonomy ) {
	add_filter(
		'woocommerce_taxonomy_args_' . $lbx_wc_taxonomy,
		function ( array $args ) {
			$args['public']            = false;
			$args['publicly_queryable'] = false;
			$args['show_ui']           = false;
			$args['show_in_nav_menus'] = false;
			$args['show_admin_column'] = false;
			$args['rewrite']           = false;
			return $args;
		}
	);
}
unset( $lbx_wc_taxonomy );

/**
 * Términos iniciales: segmentos de la maqueta (con ícono y descripción) y los valores de las listas
 * desplegables de la plantilla Excel. Se crean una vez por versión (no pisa términos editados).
 */
function lbx_seed_terms(): void {
	$seed = array(
		'segmento'       => array(
			array( 'automotriz', 'Automotriz', 'Motores de pasajeros, SUV, pick-ups y carga. Elige gasolina o diésel.', 'auto' ),
			array( 'motocicletas', 'Motocicletas', 'Motores de 2 y 4 tiempos, uso urbano y de reparto.', 'moto' ),
			array( 'transmisiones', 'Transmisiones y engranajes', 'Cajas manuales, automáticas, diferenciales y reductores.', 'transmisiones' ),
			array( 'ferroviario', 'Ferroviario', 'Motores diésel de locomotora, transmisiones y equipo de vía.', 'ferroviario' ),
			array( 'maritimo', 'Marítimo', 'Motores marinos de cuatro tiempos, sistemas de cubierta y engranajes.', 'maritimo' ),
			array( 'industrial', 'Industrial', 'Hidráulicos, compresores, turbinas y fluidos de proceso.', 'industrial' ),
			array( 'agricola', 'Agrícola', 'Tractores, cosechadoras y equipo de labranza, aceites multiuso.', 'agricola' ),
			array( 'linea-pesada', 'Línea pesada', 'Camión de carga, maquinaria pesada y equipo fuera de carretera en servicio continuo.', 'linea-pesada' ),
		),
		'tipo_producto'  => array(
			array( 'aceite-de-motor', 'Aceite de motor' ),
			array( 'aceite-hidraulico', 'Aceite hidráulico' ),
			array( 'aceite-de-engranajes', 'Aceite de engranajes' ),
			array( 'aceite-de-transmision', 'Aceite de transmisión' ),
			array( 'atf', 'Fluido de transmisión automática (ATF)' ),
			array( 'aceite-para-compresores', 'Aceite para compresores' ),
		),
		'base_aceite'    => array(
			array( 'mineral', 'Mineral' ),
			array( 'semisintetico', 'Semisintético' ),
			array( 'sintetico', 'Sintético' ),
		),
		'combustible'    => array(
			array( 'gasolina', 'Gasolina' ),
			array( 'diesel', 'Diésel' ),
		),
		'viscosidad'     => array(
			array( '15w-40', '15W-40' ),
			array( '10w-30', '10W-30' ),
			array( '85w-140', '85W-140' ),
			array( 'sae-40', 'SAE 40' ),
			array( 'iso-vg-220', 'ISO VG 220' ),
			array( 'multivehiculo', 'Multivehículo' ),
		),
		'especificacion' => array(
			array( 'api-ci-4', 'API CI-4' ),
			array( 'api-sn', 'API SN' ),
			array( 'jaso-ma2', 'JASO MA2' ),
			array( 'api-gl-5', 'API GL-5' ),
			array( 'dexron-vi', 'Dexron VI' ),
			array( 'api-cf', 'API CF' ),
			array( 'generacion-5', 'Generación 5' ),
			array( 'din-51517-3-clp', 'DIN 51517-3 CLP' ),
		),
	);

	foreach ( $seed as $taxonomy => $terms ) {
		foreach ( $terms as $order => $term ) {
			[ $slug, $name ] = $term;
			if ( term_exists( $slug, $taxonomy ) ) {
				continue;
			}
			$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug, 'description' => $term[2] ?? '' ) );
			if ( ! is_wp_error( $created ) && 'segmento' === $taxonomy ) {
				update_term_meta( $created['term_id'], 'icono', $term[3] );
				update_term_meta( $created['term_id'], '_icono', 'field_lbx_seg_icono' );
				update_term_meta( $created['term_id'], 'orden', $order + 1 );
				update_term_meta( $created['term_id'], '_orden', 'field_lbx_seg_orden' );
			}
		}
	}
}

/**
 * Ejecuta la carga inicial y refresca las URLs cuando cambia la versión de las taxonomías.
 */
add_action(
	'init',
	function () {
		$version = '2';
		if ( get_option( 'lbx_taxonomies_version' ) === $version ) {
			return;
		}
		lbx_seed_terms();
		flush_rewrite_rules( false );
		update_option( 'lbx_taxonomies_version', $version );
	},
	20
);

/**
 * URL del ícono de un segmento: imagen subida (campo "icono_imagen") o ícono SVG del tema (campo "icono").
 */
function lbx_segment_icon_url( int $term_id ): string {
	$image = (int) get_term_meta( $term_id, 'icono_imagen', true );
	if ( $image ) {
		return (string) wp_get_attachment_url( $image );
	}

	$icon = (string) get_term_meta( $term_id, 'icono', true );

	return $icon ? lbx_asset( 'img/categories/' . sanitize_file_name( $icon ) . '.svg' ) : '';
}
