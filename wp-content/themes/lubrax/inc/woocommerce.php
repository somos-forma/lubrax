<?php
/**
 * WooCommerce en modo catálogo: solo se muestran productos y se pide cotización.
 *
 * Desactiva: precios, carrito, checkout, mi cuenta, pagos, envíos, cupones, reseñas, pedidos y la Store API
 * de carrito/checkout. El botón de compra se reemplaza por "Cotizar" (formulario de contacto, como la maqueta).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

add_action( 'after_setup_theme', fn() => add_theme_support( 'woocommerce' ) );

/**
 * URL de cotización de un producto: contacto?tipo=industrial&producto={slug}#formulario (maqueta producto.html).
 */
function lbx_quote_url( $product = null ): string {
	$product = wc_get_product( $product ?: get_the_ID() );
	$url     = add_query_arg(
		array(
			'tipo'     => 'industrial',
			'producto' => $product ? $product->get_slug() : '',
		),
		lbx_page_url( 'contacto' )
	);
	return $url . '#formulario';
}

function lbx_quote_button( $product = null, string $class = 'btn btn-lbx btn-sm' ): string {
	return sprintf(
		'<a class="%1$s" href="%2$s">%3$s</a>',
		esc_attr( $class ),
		esc_url( lbx_quote_url( $product ) ),
		esc_html__( 'Cotizar volumen', 'lubrax' )
	);
}

/* ------------------------------------------------------------------
 * 1. Sin compra: no se puede comprar, sin precios ni pagos.
 * ------------------------------------------------------------------ */
add_filter( 'woocommerce_is_purchasable', '__return_false' );
add_filter( 'woocommerce_get_price_html', '__return_empty_string' );
add_filter( 'woocommerce_payment_gateways', '__return_empty_array' );
add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
add_filter( 'woocommerce_coupons_enabled', '__return_false' );
add_filter( 'woocommerce_product_tabs', fn( $tabs ) => array_diff_key( $tabs, array( 'reviews' => 1 ) ), 98 );

// ?add-to-cart=ID no hace nada.
add_action(
	'wp_loaded',
	fn() => remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 ),
	1
);

// Botones de compra → "Cotizar" (plantillas clásicas y bloques de WooCommerce).
add_filter( 'woocommerce_loop_add_to_cart_link', fn( $html, $product ) => lbx_quote_button( $product ), 10, 2 );
add_action(
	'woocommerce_single_product_summary',
	function () {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	},
	1
);
add_action( 'woocommerce_single_product_summary', fn() => print( lbx_quote_button() ), 30 ); // phpcs:ignore WordPress.Security.EscapeOutput
foreach ( array( 'woocommerce/product-button', 'woocommerce/add-to-cart-form', 'woocommerce/add-to-cart-with-options' ) as $lbx_block ) {
	add_filter( 'render_block_' . $lbx_block, fn( $html, $block, $instance ) => lbx_quote_button( $instance->context['postId'] ?? null ), 10, 3 );
}
foreach ( array( 'woocommerce/product-price', 'woocommerce/mini-cart', 'woocommerce/customer-account', 'woocommerce/cart-link' ) as $lbx_block ) {
	add_filter( 'render_block_' . $lbx_block, '__return_empty_string' );
}
unset( $lbx_block );

/* ------------------------------------------------------------------
 * 2. Sin carrito, checkout ni cuenta: sus URLs llevan al catálogo y la Store API no los expone.
 * ------------------------------------------------------------------ */
add_action(
	'template_redirect',
	function () {
		if ( is_cart() || is_checkout() || is_account_page() ) {
			wp_safe_redirect( lbx_page_url( 'productos' ), 301 );
			exit;
		}
	}
);

add_filter(
	'rest_endpoints',
	function ( array $endpoints ) {
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( preg_match( '#^/wc/store(/v\d+)?/(cart|checkout|order|batch)#', $route ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
);

// Sin sesiones ni cookies de carrito para visitantes.
add_filter( 'woocommerce_persistent_cart_enabled', '__return_false' );
add_action(
	'wp_enqueue_scripts',
	function () {
		foreach ( array( 'wc-cart-fragments', 'wc-add-to-cart', 'wc-cart', 'wc-checkout', 'woocommerce', 'sourcebuster-js', 'wc-order-attribution' ) as $handle ) {
			wp_dequeue_script( $handle );
		}
	},
	100
);

// Estilos de WooCommerce: el diseño del catálogo y la ficha viene de main.css (no se usan sus bloques).
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
$lbx_dequeue_wc_styles = function () {
	foreach ( array( 'woocommerce-blocktheme', 'woocommerce-inline', 'wc-blocks-style' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
};
add_action( 'wp_enqueue_scripts', $lbx_dequeue_wc_styles, 100 );
add_action( 'wp_footer', $lbx_dequeue_wc_styles, 1 ); // wc-blocks-style se encola tarde.
unset( $lbx_dequeue_wc_styles );

/* ------------------------------------------------------------------
 * 3. Admin: sin precios, envíos, pedidos, clientes ni marketing.
 * ------------------------------------------------------------------ */
add_filter( 'product_type_selector', fn() => array( 'simple' => __( 'Producto simple', 'lubrax' ) ) );
add_filter( 'product_type_options', '__return_empty_array' );
add_filter(
	'woocommerce_product_data_tabs',
	fn( $tabs ) => array_diff_key( $tabs, array_flip( array( 'shipping', 'linked_product', 'attribute', 'variations', 'advanced' ) ) )
);
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( $screen && 'product' === $screen->post_type ) {
			echo '<style>.options_group.pricing,._tax_status_field,._tax_class_field,#woocommerce-product-data .show_if_simple ._manage_stock_field,.inventory_sold_individually{display:none!important}</style>';
		}
	}
);
add_filter( 'manage_edit-product_columns', fn( $columns ) => array_diff_key( $columns, array_flip( array( 'price', 'product_cat', 'product_tag', 'is_in_stock' ) ) ), 20 );

add_action(
	'admin_menu',
	function () {
		remove_submenu_page( 'woocommerce', 'wc-orders' );
		remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_order' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/customers' );
		remove_submenu_page( 'woocommerce', 'wc-reports' );
		remove_submenu_page( 'woocommerce', 'coupons-moved' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/extensions' );
		remove_menu_page( 'woocommerce-marketing' );
		remove_menu_page( 'wc-admin&path=/analytics/overview' );
		remove_menu_page( 'admin.php?page=wc-settings&tab=checkout' );
		remove_submenu_page( 'edit.php?post_type=product', 'product_attributes' );
		remove_submenu_page( 'edit.php?post_type=product', 'product-reviews' );
	},
	999
);

// Sin avisos de configuración de pagos/envíos ni sugerencias del marketplace.
add_filter( 'woocommerce_admin_features', fn( $features ) => array_values( array_diff( $features, array( 'marketing', 'analytics', 'payments', 'shipping-setting-tour', 'remote-inbox-notifications', 'activity-panels' ) ) ) );
add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
add_filter( 'woocommerce_show_admin_notice', '__return_false' );
