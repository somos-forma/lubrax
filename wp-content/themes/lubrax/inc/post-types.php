<?php
/**
 * CPT Distribuidores.
 *
 * No tiene página propia: se muestra en el localizador (/distribuidores/), en el modal del header y en la
 * ficha de producto. Capacidades propias (edit_distribuidores…) para asignarlas por rol (inc/roles.php).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'distribuidor',
			array(
				'labels'              => array(
					'name'               => __( 'Distribuidores', 'lubrax' ),
					'singular_name'      => __( 'Distribuidor', 'lubrax' ),
					'menu_name'          => __( 'Distribuidores', 'lubrax' ),
					'add_new'            => __( 'Agregar distribuidor', 'lubrax' ),
					'add_new_item'       => __( 'Agregar distribuidor', 'lubrax' ),
					'edit_item'          => __( 'Editar distribuidor', 'lubrax' ),
					'new_item'           => __( 'Nuevo distribuidor', 'lubrax' ),
					'view_item'          => __( 'Ver distribuidor', 'lubrax' ),
					'search_items'       => __( 'Buscar distribuidores', 'lubrax' ),
					'not_found'          => __( 'No hay distribuidores.', 'lubrax' ),
					'not_found_in_trash' => __( 'No hay distribuidores en la papelera.', 'lubrax' ),
					'all_items'          => __( 'Todos los distribuidores', 'lubrax' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => true,
				'menu_position'       => 56,
				'menu_icon'           => 'dashicons-location-alt',
				'capability_type'     => array( 'distribuidor', 'distribuidores' ),
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'revisions' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}
);

// Solo campos: pantalla clásica con los metaboxes de SCF (sin editor de bloques).
add_filter(
	'use_block_editor_for_post_type',
	fn( $use, $post_type ) => 'distribuidor' === $post_type ? false : $use,
	10,
	2
);

add_filter(
	'enter_title_here',
	fn( $text, $post ) => 'distribuidor' === $post->post_type ? __( 'Nombre del distribuidor', 'lubrax' ) : $text,
	10,
	2
);

/**
 * Columnas del listado: zona, región, ciudad y cantidad de productos.
 */
add_filter(
	'manage_distribuidor_posts_columns',
	function ( array $columns ) {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );

		$columns['lbx_zona']      = __( 'Zona', 'lubrax' );
		$columns['lbx_region']    = __( 'Región', 'lubrax' );
		$columns['lbx_ciudad']    = __( 'Ciudad', 'lubrax' );
		$columns['lbx_productos'] = __( 'Productos', 'lubrax' );
		$columns['lbx_mapa']      = __( 'Mapa', 'lubrax' );

		if ( $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}
);

add_action(
	'manage_distribuidor_posts_custom_column',
	function ( string $column, int $post_id ) {
		switch ( $column ) {
			case 'lbx_zona':
				echo esc_html( (string) get_post_meta( $post_id, 'zona', true ) );
				break;
			case 'lbx_region':
				echo esc_html( (string) get_post_meta( $post_id, 'region', true ) );
				break;
			case 'lbx_ciudad':
				echo esc_html( (string) get_post_meta( $post_id, 'ciudad', true ) );
				break;
			case 'lbx_productos':
				echo esc_html( (string) count( array_filter( (array) get_post_meta( $post_id, 'productos', true ) ) ) );
				break;
			case 'lbx_mapa':
				$has_coords = '' !== (string) get_post_meta( $post_id, 'lat', true ) && '' !== (string) get_post_meta( $post_id, 'lng', true );
				echo $has_coords ? '✔' : '<span style="color:#b32d2e">' . esc_html__( 'Sin coordenadas', 'lubrax' ) . '</span>';
				break;
		}
	},
	10,
	2
);
