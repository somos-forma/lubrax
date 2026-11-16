<?php
/**
 * Roles con privilegios mínimos.
 *
 * - Administrador Técnico (lbx_admin_tecnico): todas las capacidades del administrador.
 * - Editor de Contenido (lbx_editor_contenido): entradas, páginas, productos (con sus taxonomías), distribuidores,
 *   patrones y medios. Sin plugins, temas, editor del sitio, usuarios ni ajustes.
 *
 * Los roles se guardan en la base de datos: se sincronizan cuando cambia LBX_ROLES_VERSION.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

const LBX_ROLES_VERSION = '2';

/**
 * Capacidades del CPT distribuidor (capability_type distribuidor/distribuidores).
 */
function lbx_distributor_caps(): array {
	return array(
		'edit_distribuidores',
		'edit_others_distribuidores',
		'edit_published_distribuidores',
		'edit_private_distribuidores',
		'publish_distribuidores',
		'read_private_distribuidores',
		'delete_distribuidores',
		'delete_others_distribuidores',
		'delete_published_distribuidores',
		'delete_private_distribuidores',
	);
}

/**
 * Capacidades del Editor de Contenido.
 */
function lbx_content_editor_caps(): array {
	$caps = array(
		'read',
		'upload_files',

		// Entradas (blog) y patrones sincronizados (wp_block usa las capacidades de las entradas).
		'edit_posts',
		'edit_others_posts',
		'edit_published_posts',
		'edit_private_posts',
		'publish_posts',
		'read_private_posts',
		'delete_posts',
		'delete_others_posts',
		'delete_published_posts',
		'delete_private_posts',
		'manage_categories',

		// Páginas (Inicio, Sobre Lubrax y demás páginas armadas con patrones).
		'edit_pages',
		'edit_others_pages',
		'edit_published_pages',
		'edit_private_pages',
		'publish_pages',
		'read_private_pages',
		'delete_pages',
		'delete_others_pages',
		'delete_published_pages',
		'delete_private_pages',

		// Productos de WooCommerce y sus taxonomías (segmento, tipo, especificación, base, combustible, viscosidad).
		'edit_product',
		'read_product',
		'delete_product',
		'edit_products',
		'edit_others_products',
		'edit_published_products',
		'edit_private_products',
		'publish_products',
		'read_private_products',
		'delete_products',
		'delete_others_products',
		'delete_published_products',
		'delete_private_products',
		'manage_product_terms',
		'edit_product_terms',
		'delete_product_terms',
		'assign_product_terms',
	);

	return array_merge( $caps, lbx_distributor_caps() );
}

function lbx_sync_roles(): void {
	// Administrador y Administrador Técnico: todo, incluidos los distribuidores.
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( lbx_distributor_caps() as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	remove_role( 'lbx_admin_tecnico' );
	add_role( 'lbx_admin_tecnico', __( 'Administrador Técnico', 'lubrax' ), $admin ? $admin->capabilities : array() );

	remove_role( 'lbx_editor_contenido' );
	add_role(
		'lbx_editor_contenido',
		__( 'Editor de Contenido', 'lubrax' ),
		array_fill_keys( lbx_content_editor_caps(), true )
	);

	update_option( 'lbx_roles_version', LBX_ROLES_VERSION );
}

add_action(
	'init',
	function () {
		if ( get_option( 'lbx_roles_version' ) !== LBX_ROLES_VERSION ) {
			lbx_sync_roles();
		}
	},
	30
);

/**
 * Editor de Contenido: el escritorio solo muestra lo que puede editar.
 */
add_action(
	'admin_menu',
	function () {
		if ( ! in_array( 'lbx_editor_contenido', (array) wp_get_current_user()->roles, true ) ) {
			return;
		}
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'woocommerce' );
	},
	999
);

/**
 * Patrones: el Editor de Contenido los gestiona desde Apariencia… que requiere edit_theme_options.
 * Se le da acceso directo a la lista de patrones (wp_block) en el menú.
 */
add_action(
	'admin_menu',
	function () {
		if ( current_user_can( 'edit_theme_options' ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		add_menu_page(
			__( 'Patrones', 'lubrax' ),
			__( 'Patrones', 'lubrax' ),
			'edit_posts',
			'edit.php?post_type=wp_block',
			'',
			'dashicons-screenoptions',
			58
		);
	}
);
