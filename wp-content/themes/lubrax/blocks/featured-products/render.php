<?php
/**
 * Bloque SCF lubrax/featured-products. Tarjetas del catálogo (tpl-product-card) de los productos destacados;
 * si no hay destacados, los más recientes. Encabezado con el formato de "Dónde comprar" (producto.html).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lbx_product_card' ) ) {
	return;
}

$lbx_limit    = max( 1, (int) lbx_block_value( 'feat_cantidad', 'featured-products' ) );
$lbx_products = wc_get_products( array( 'status' => 'publish', 'featured' => true, 'limit' => $lbx_limit, 'orderby' => 'menu_order title', 'order' => 'ASC' ) )
	?: wc_get_products( array( 'status' => 'publish', 'limit' => $lbx_limit ) );
if ( ! $lbx_products ) {
	return;
}

$lbx_id   = wp_unique_id( 'featuredTitle-' );
$lbx_link = lbx_link_value( lbx_block_value( 'feat_enlace', 'featured-products' ) );
?>
<section class="section on-light" aria-labelledby="<?php echo esc_attr( $lbx_id ); ?>">
	<div class="container-xxl">
		<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
			<div data-reveal="up">
				<p class="t-kicker"><?php echo esc_html( lbx_block_value( 'feat_antetitulo', 'featured-products' ) ); ?></p>
				<h2 class="mb-0" id="<?php echo esc_attr( $lbx_id ); ?>"><?php echo esc_html( lbx_block_value( 'feat_titulo', 'featured-products' ) ); ?></h2>
			</div>
			<?php echo lbx_link_html( $lbx_link, 'link-arrow', lbx_icon( 'arrow-right' ), ' data-reveal="up"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<ul class="product-grid" data-reveal-stagger="0.06">
			<?php
			foreach ( $lbx_products as $lbx_product ) {
				echo lbx_product_card( $lbx_product ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</ul>
	</div>
</section>
