<?php
// Uso: wp eval-file wp-content/themes/lubrax/tools/plantilla-excel/export_data.php > export.json
defined( 'ABSPATH' ) || exit;
// Exporta productos, distribuidores y listas de opciones en el formato del importador (para generar el Excel).
$names = fn( $id, $tax ) => implode( ', ', wp_get_post_terms( $id, $tax, array( 'fields' => 'names' ) ) );
$url   = fn( $id ) => $id ? (string) wp_get_attachment_url( (int) $id ) : '';

$props = array( 'Índice de viscosidad', 'Viscosidad a 100 °C', 'Viscosidad a 40 °C', 'Punto de inflamación', 'Punto de fluidez', 'Intervalo de cambio sugerido' );
$other_props = array();

$products = array();
foreach ( wc_get_products( array( 'status' => array( 'publish', 'draft' ), 'limit' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $p ) {
	$id   = $p->get_id();
	$docs = (array) lbx_get_field( 'documentos', $id );
	$doc  = function ( $type ) use ( $docs, $url ) {
		foreach ( $docs as $d ) {
			if ( ( $d['tipo'] ?? '' ) === $type ) {
				return $url( is_array( $d['archivo'] ?? null ) ? $d['archivo']['ID'] : ( $d['archivo'] ?? 0 ) );
			}
		}
		return '';
	};
	$prop_values = array();
	foreach ( (array) lbx_get_field( 'propiedades', $id ) as $row ) {
		if ( in_array( $row['propiedad'] ?? '', $props, true ) ) {
			$prop_values[ $row['propiedad'] ] = $row['valor'];
		} elseif ( ! empty( $row['propiedad'] ) ) {
			$other_props[ $row['propiedad'] ] = true;
		}
	}
	$dists = array_map( 'get_the_title', array_filter( array_map( 'intval', (array) get_post_meta( $id, 'distribuidores', true ) ) ) );
	$products[] = array(
		'Nombre Producto'                                => $p->get_name(),
		'SKU'                                            => $p->get_sku(),
		'Categoría'                                      => $names( $id, 'segmento' ),
		'Tipo de producto'                               => $names( $id, 'tipo_producto' ),
		'Tipo combustible'                               => $names( $id, 'combustible' ),
		'Grado de viscosidad'                            => $names( $id, 'viscosidad' ),
		'Especificaciones, Normativas o Certificaciones' => $names( $id, 'especificacion' ),
		'Base'                                           => $names( $id, 'base_aceite' ),
		'Presentación Envase'                            => implode( ', ', array_filter( wp_list_pluck( (array) lbx_get_field( 'presentaciones', $id ), 'envase' ) ) ),
		'Aplicaciones recomendadas'                      => implode( "\n", array_filter( wp_list_pluck( (array) lbx_get_field( 'aplicaciones', $id ), 'texto' ) ) ),
		'Normas y homologaciones'                        => (string) lbx_get_field( 'normas', $id ),
		'Descripción corta'                              => wp_strip_all_tags( $p->get_short_description() ),
		'Descripción'                                    => wp_strip_all_tags( $p->get_description() ),
		'Ruta url de imagen'                             => $url( $p->get_image_id() ),
		'Ficha técnica'                                  => $doc( 'ficha-tecnica' ),
		'Hoja de seguridad (sds)'                        => $doc( 'hoja-seguridad' ),
		'Distribuido por'                                => implode( ', ', $dists ),
		'Destacado'                                      => $p->is_featured() ? 'Sí' : 'No',
		'props'                                          => $prop_values,
	);
}

$distributors = array();
foreach ( get_posts( array( 'post_type' => 'distribuidor', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $d ) {
	$m        = fn( $k ) => get_post_meta( $d->ID, $k, true );
	$list     = fn( $k ) => implode( ', ', array_filter( (array) $m( $k ) ) );
	$distributors[] = array(
		'Nombre Distribuidor'       => $d->post_title,
		'Zona'                      => (string) $m( 'zona' ),
		'Tipo de compra'            => $list( 'tipo_compra' ),
		'Dirección'                 => (string) $m( 'direccion' ),
		'Región'                    => (string) $m( 'region' ),
		'Comuna'                    => (string) $m( 'ciudad' ),
		'Link google maps'          => (string) $m( 'link_maps' ),
		'Latitud'                   => (string) $m( 'lat' ),
		'Longitud'                  => (string) $m( 'lng' ),
		'Teléfono'                  => (string) $m( 'telefono' ),
		'Correo'                    => (string) $m( 'email' ),
		'Horario'                   => (string) $m( 'horario' ),
		'Página web'                => (string) $m( 'web' ),
		'Portafolio de productos'   => implode( ', ', array_map( 'get_the_title', array_filter( array_map( 'intval', (array) $m( 'productos' ) ) ) ) ),
		'Capacidad logística'       => $list( 'capacidad' ),
		'Presentaciones que ofrece' => $list( 'presentaciones' ),
	);
}

$terms   = fn( $tax ) => get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'names', 'orderby' => 'name' ) );
$choices = fn( $key ) => array_values( (array) ( acf_get_field( $key )['choices'] ?? array() ) );
$segments = array_map( fn( $t ) => $t->name, lbx_segments() );

echo wp_json_encode(
	array(
		'products'     => $products,
		'distributors' => $distributors,
		'other_props'  => array_keys( $other_props ),
		'lists'        => array(
			'Categoría'           => $segments,
			'Tipo de producto'    => $terms( 'tipo_producto' ),
			'Tipo combustible'    => $terms( 'combustible' ),
			'Grado de viscosidad' => $terms( 'viscosidad' ),
			'Especificaciones'    => $terms( 'especificacion' ),
			'Base'                => $terms( 'base_aceite' ),
			'Envases'             => $choices( 'field_lbx_prod_pres_envase' ),
			'Capacidad logística' => $choices( 'field_lbx_dist_capacidad' ),
			'Tipo de compra'      => $choices( 'field_lbx_dist_tipo_compra' ),
		),
		'comunas'      => lbx_comunas(),
	),
	JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
