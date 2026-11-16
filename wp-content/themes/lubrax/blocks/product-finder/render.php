<?php
/**
 * Bloque SCF lubrax/product-finder. Marcado: .finder-wrap de index.html (lo maneja finder.js).
 *
 * Isla JSON #lbx-product-index: [{ id, category, sae }] por producto (segmento principal y primera viscosidad).
 * Los formularios envían por GET al catálogo: ?categoria=…&sae=… y ?q=…
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lbx_product_data' ) ) {
	return;
}

wp_enqueue_script( 'lbx-finder' );

$lbx_catalog = lbx_page_url( 'productos' );
$lbx_index   = array();
$lbx_saes    = array();
foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) ) as $lbx_product ) {
	$lbx_p = lbx_product_data( $lbx_product );
	foreach ( $lbx_p['sae'] ?: array( '' ) as $lbx_sae ) {
		$lbx_index[] = array(
			'id'       => $lbx_p['id'],
			'category' => $lbx_p['category'],
			'sae'      => $lbx_sae,
		);
		if ( $lbx_sae ) {
			$lbx_saes[ $lbx_sae ] = $lbx_sae;
		}
	}
}
natcasesort( $lbx_saes );
?>
<div class="finder-wrap">
	<div class="container-xxl">
		<section class="finder on-light" data-finder data-reveal="up" aria-labelledby="finderTitle">
			<h2 class="finder__title t-display" id="finderTitle"><?php echo esc_html( lbx_block_value( 'finder_titulo', 'product-finder' ) ); ?></h2>
			<ul class="nav nav-tabs" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="tabCategory" data-bs-toggle="tab" data-bs-target="#paneCategory" type="button" role="tab" aria-controls="paneCategory" aria-selected="true"><?php esc_html_e( 'Por categoría y viscosidad', 'lubrax' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="tabFree" data-bs-toggle="tab" data-bs-target="#paneFree" type="button" role="tab" aria-controls="paneFree" aria-selected="false"><?php esc_html_e( 'Búsqueda libre', 'lubrax' ); ?></button>
				</li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane fade show active" id="paneCategory" role="tabpanel" aria-labelledby="tabCategory" tabindex="0">
					<form class="row g-3 g-lg-4 align-items-end form-line" id="finderCategory" action="<?php echo esc_url( $lbx_catalog ); ?>" method="get">
						<div class="col-md-6 col-lg">
							<label class="form-label" for="fcCategory"><?php esc_html_e( 'Categoría', 'lubrax' ); ?></label>
							<select class="form-select" id="fcCategory" name="categoria">
								<option value=""><?php esc_html_e( 'Todas las categorías', 'lubrax' ); ?></option>
								<?php foreach ( lbx_segments( true ) as $lbx_segment ) : ?>
									<option value="<?php echo esc_attr( $lbx_segment->slug ); ?>"><?php echo esc_html( $lbx_segment->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-6 col-lg">
							<label class="form-label" for="fcSae"><?php esc_html_e( 'Viscosidad SAE', 'lubrax' ); ?></label>
							<select class="form-select" id="fcSae" name="sae">
								<option value=""><?php esc_html_e( 'Todas', 'lubrax' ); ?></option>
								<?php foreach ( $lbx_saes as $lbx_sae ) : ?>
									<option value="<?php echo esc_attr( $lbx_sae ); ?>"><?php echo esc_html( $lbx_sae ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-12 col-lg-auto">
							<button class="btn btn-lbx-dark w-100" type="submit"><span data-finder-count><?php esc_html_e( 'Ver productos', 'lubrax' ); ?></span></button>
						</div>
					</form>
				</div>
				<div class="tab-pane fade" id="paneFree" role="tabpanel" aria-labelledby="tabFree" tabindex="0">
					<form class="row g-3 g-lg-4 align-items-end form-line" action="<?php echo esc_url( $lbx_catalog ); ?>" method="get" role="search">
						<div class="col-lg">
							<label class="form-label" for="ffQuery"><?php esc_html_e( 'Producto, viscosidad o norma', 'lubrax' ); ?></label>
							<input class="form-control" type="search" id="ffQuery" name="q" placeholder="<?php esc_attr_e( 'Ej.: Valora, 15W-40 o API CK-4', 'lubrax' ); ?>" autocomplete="off" enterkeyhint="search" required>
						</div>
						<div class="col-12 col-lg-auto">
							<button class="btn btn-lbx-dark w-100" type="submit"><?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Buscar', 'lubrax' ); ?></button>
						</div>
					</form>
				</div>
			</div>
			<script type="application/json" id="lbx-product-index"><?php echo wp_json_encode( $lbx_index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>
		</section>
	</div>
</div>
