<?php
/**
 * Bloque lubrax/product-page. Marcado: tpl-product-page, tpl-doc-card y tpl-mini-dist de producto.html,
 * más "Productos relacionados" (tarjetas del catálogo).
 *
 * @package Lubrax
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lbx_product_data' ) ) {
	return;
}

$lbx_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( 'product' !== get_post_type( $lbx_id ) ) {
	// Editor del sitio (plantilla sin producto): vista previa con el producto más reciente.
	$lbx_latest = wc_get_products( array( 'status' => 'publish', 'limit' => 1, 'return' => 'ids' ) );
	$lbx_id     = (int) ( $lbx_latest[0] ?? 0 );
	if ( ! $lbx_id ) {
		return;
	}
}

wp_enqueue_script( 'lbx-product' );

$p        = lbx_product_data( $lbx_id );
$lbx_docs = lbx_product_documents( $lbx_id );
$lbx_pres = array_values( array_filter( (array) lbx_get_field( 'presentaciones', $lbx_id ), fn( $row ) => ! empty( $row['envase'] ) ) );
$lbx_apps = array_values( array_filter( wp_list_pluck( (array) lbx_get_field( 'aplicaciones', $lbx_id ), 'texto' ) ) );
$lbx_prop = array_values( array_filter( (array) lbx_get_field( 'propiedades', $lbx_id ), fn( $row ) => ! empty( $row['propiedad'] ) ) );
$lbx_norm = trim( (string) lbx_get_field( 'normas', $lbx_id ) );

// Distribuidores que lo venden (datos cacheados del localizador).
$lbx_dists = array_values( array_filter( lbx_get_distributors(), fn( $d ) => in_array( $p['id'], $d['products'], true ) ) );

// Galería: imagen principal, galería de WooCommerce e imágenes de las presentaciones (sin repetir).
$lbx_gallery = array();
$lbx_add     = function ( int $image_id, string $alt ) use ( &$lbx_gallery ) {
	if ( $image_id && wp_attachment_is_image( $image_id ) && ! isset( $lbx_gallery[ $image_id ] ) ) {
		$lbx_gallery[ $image_id ] = array(
			'src'   => wp_get_attachment_image_url( $image_id, 'lbx-product' ),
			'thumb' => wp_get_attachment_image_url( $image_id, 'thumbnail' ),
			'alt'   => $alt,
		);
	}
};
$lbx_add( $p['image_id'], $p['name'] );
foreach ( $p['gallery_ids'] as $lbx_image ) {
	$lbx_add( $lbx_image, $p['name'] );
}
foreach ( $lbx_pres as $lbx_row ) {
	/* translators: 1: producto, 2: envase. */
	$lbx_add( (int) ( $lbx_row['imagen'] ?? 0 ), sprintf( __( '%1$s, envase %2$s', 'lubrax' ), $p['name'], $lbx_row['envase'] ) );
}
$lbx_main = $lbx_gallery ? reset( $lbx_gallery ) : array(
	'src' => wc_placeholder_img_src( 'lbx-product' ),
	'alt' => $p['name'],
);

$lbx_specs_badges = '';
foreach ( $p['sae'] as $lbx_sae ) {
	$lbx_specs_badges .= '<span class="badge-sae">' . esc_html( $lbx_sae ) . '</span>';
}
foreach ( $p['specs'] as $lbx_spec ) {
	$lbx_specs_badges .= '<span class="badge-spec">' . esc_html( $lbx_spec ) . '</span>';
}

$lbx_related = lbx_related_products( $lbx_id );
/* translators: %d: cantidad de distribuidores. */
$lbx_dist_count = sprintf( _n( '%d distribuidor', '%d distribuidores', count( $lbx_dists ), 'lubrax' ), count( $lbx_dists ) );
?>
<section class="pdp" aria-labelledby="pdpTitle">
	<div class="container-xxl">
		<nav aria-label="<?php esc_attr_e( 'Ruta de navegación', 'lubrax' ); ?>">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'lubrax' ); ?></a></li>
				<li class="breadcrumb-item"><a href="<?php echo esc_url( lbx_page_url( 'productos' ) ); ?>"><?php esc_html_e( 'Productos', 'lubrax' ); ?></a></li>
				<?php if ( $p['category_name'] ) : ?>
					<li class="breadcrumb-item"><a href="<?php echo esc_url( $p['category_url'] ); ?>"><?php echo esc_html( $p['category_name'] ); ?></a></li>
				<?php endif; ?>
				<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $p['name'] ); ?></li>
			</ol>
		</nav>
		<div class="row g-4 g-lg-5" data-product-page data-id="<?php echo esc_attr( $p['id'] ); ?>" data-distributors="<?php echo esc_attr( implode( ' ', $p['distributors'] ) ); ?>" data-map-url="<?php echo esc_url( lbx_page_url( 'distribuidores' ) ); ?>">
			<div class="col-sm-6" data-reveal="zoom">
				<div class="pdp-gallery">
					<div class="pdp-gallery__main">
						<img id="pdpMainImage" src="<?php echo esc_url( $lbx_main['src'] ); ?>" alt="<?php echo esc_attr( $lbx_main['alt'] ); ?>" width="600" height="600" fetchpriority="high" decoding="async">
					</div>
					<?php if ( count( $lbx_gallery ) > 1 ) : ?>
						<div class="pdp-gallery__thumbs" role="group" aria-label="<?php esc_attr_e( 'Imágenes del producto', 'lubrax' ); ?>">
							<?php $lbx_first = true; ?>
							<?php foreach ( $lbx_gallery as $lbx_img ) : ?>
								<?php /* translators: %s: texto alternativo de la imagen. */ ?>
								<button type="button" data-gallery-src="<?php echo esc_url( $lbx_img['src'] ); ?>" data-gallery-alt="<?php echo esc_attr( $lbx_img['alt'] ); ?>" aria-pressed="<?php echo $lbx_first ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Ver imagen: %s', 'lubrax' ), $lbx_img['alt'] ) ); ?>"><img src="<?php echo esc_url( $lbx_img['thumb'] ); ?>" alt="" width="80" height="80" loading="lazy" decoding="async"></button>
								<?php $lbx_first = false; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="col-sm-6">
				<?php if ( $p['tag_label'] ) : ?>
					<span class="pdp__tag" data-reveal="up"><?php echo esc_html( $p['tag_label'] ); ?></span>
				<?php endif; ?>
				<h1 class="pdp__title t-display" id="pdpTitle" data-reveal="up"><?php echo esc_html( $p['name'] ); ?></h1>
				<?php
				// Como la maqueta: bajo el título va la descripción. Si hay descripción corta propia, va ella
				// y la descripción completa pasa a la sección técnica.
				$lbx_desc = $p['has_excerpt'] ? $p['short'] : wp_strip_all_tags( $p['description'] );
				?>
				<?php if ( $lbx_desc ) : ?>
					<p class="pdp__desc" data-reveal="up"><?php echo esc_html( $lbx_desc ); ?></p>
				<?php endif; ?>
				<dl class="pdp-facts" data-reveal="up">
					<div><dt><?php esc_html_e( 'Viscosidad', 'lubrax' ); ?></dt><dd><?php echo esc_html( implode( ' / ', $p['sae'] ) ?: '—' ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Base', 'lubrax' ); ?></dt><dd><?php echo esc_html( implode( ' / ', $p['base'] ) ?: '—' ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Norma', 'lubrax' ); ?></dt><dd><?php echo esc_html( implode( ' / ', $p['specs'] ) ?: '—' ); ?></dd></div>
				</dl>
				<div data-reveal="up">
					<?php if ( $lbx_pres ) : ?>
						<h2 class="t-kicker mb-2" id="presTitle"><?php esc_html_e( 'Presentaciones', 'lubrax' ); ?> <span class="visually-hidden"><?php esc_html_e( 'disponibles', 'lubrax' ); ?></span><span class="text-on-dark" data-pres-selected></span></h2>
						<div class="pres-group" role="group" aria-labelledby="presTitle">
							<?php foreach ( $lbx_pres as $lbx_row ) : ?>
								<?php
								$lbx_pres_img = ! empty( $lbx_row['imagen'] ) ? (string) wp_get_attachment_image_url( (int) $lbx_row['imagen'], 'lbx-product' ) : '';
								/* translators: 1: producto, 2: envase. */
								$lbx_pres_alt = sprintf( __( '%1$s, envase %2$s', 'lubrax' ), $p['name'], $lbx_row['envase'] );
								?>
								<button type="button" class="pres-chip" data-pres="<?php echo esc_attr( $lbx_row['envase'] ); ?>"<?php echo $lbx_pres_img ? ' data-pres-image="' . esc_url( $lbx_pres_img ) . '" data-pres-alt="' . esc_attr( $lbx_pres_alt ) . '"' : ''; ?> aria-pressed="false"><?php echo esc_html( $lbx_row['envase'] ); ?></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="avail-box" data-avail-box data-state="todos">
						<span class="avail-box__status" data-avail-status aria-live="polite"><?php esc_html_e( 'Consulta disponibilidad', 'lubrax' ); ?></span>
						<button type="button" class="btn btn-link" data-avail-action><?php esc_html_e( 'Elegir mi distribuidor', 'lubrax' ); ?></button>
					</div>
					<div class="d-flex flex-wrap gap-3">
						<a class="btn btn-lbx" href="<?php echo esc_url( lbx_where_to_buy_url( $p['id'] ) ); ?>"><?php echo lbx_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Dónde comprar', 'lubrax' ); ?></a>
						<?php if ( $lbx_docs ) : ?>
							<a class="btn btn-lbx-outline" href="#documentos"><?php echo lbx_icon( 'file' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Ficha técnica', 'lubrax' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="section on-light" aria-label="<?php esc_attr_e( 'Información técnica', 'lubrax' ); ?>">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<div class="col-sm-6 col-lg-7">
				<?php if ( $p['has_excerpt'] && $p['description'] ) : ?>
					<h2 class="pdp-content__title" data-reveal="up"><?php esc_html_e( 'Descripción', 'lubrax' ); ?></h2>
					<div class="mb-5" data-reveal="up"><?php echo wp_kses_post( wpautop( $p['description'] ) ); ?></div>
				<?php endif; ?>

				<?php if ( $lbx_apps ) : ?>
					<h2 class="pdp-content__title" data-reveal="up"><?php esc_html_e( 'Aplicaciones recomendadas', 'lubrax' ); ?></h2>
					<ul class="check-list mb-5" data-reveal-stagger="0.06">
						<?php foreach ( $lbx_apps as $lbx_app ) : ?>
							<li data-reveal="up"><?php echo esc_html( $lbx_app ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $lbx_prop ) : ?>
					<h2 class="pdp-content__title" data-reveal="up"><?php esc_html_e( 'Propiedades típicas', 'lubrax' ); ?></h2>
					<div class="table-responsive" data-reveal="up">
						<table class="table spec-table">
							<?php /* translators: %s: producto. */ ?>
							<caption class="visually-hidden"><?php echo esc_html( sprintf( __( 'Propiedades típicas de %s', 'lubrax' ), $p['name'] ) ); ?></caption>
							<tbody>
								<?php foreach ( $lbx_prop as $lbx_row ) : ?>
									<tr><th scope="row"><?php echo esc_html( $lbx_row['propiedad'] ); ?></th><td><?php echo esc_html( (string) $lbx_row['valor'] ); ?></td></tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<p class="small text-body-secondary"><?php esc_html_e( 'Valores típicos de referencia, no constituyen especificación. Revisa la ficha técnica vigente.', 'lubrax' ); ?></p>
				<?php endif; ?>

				<?php if ( $lbx_specs_badges || $lbx_norm ) : ?>
					<h2 class="pdp-content__title mt-5" data-reveal="up"><?php esc_html_e( 'Especificaciones y aprobaciones', 'lubrax' ); ?></h2>
					<div class="product-card__badges" data-reveal="up"><?php echo $lbx_specs_badges; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php if ( $lbx_norm ) : ?>
						<p class="mt-3 mb-0" data-reveal="up"><?php echo nl2br( esc_html( $lbx_norm ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="col-sm-6 col-lg-5">
				<?php if ( $lbx_docs ) : ?>
					<div id="documentos">
						<h2 class="pdp-content__title" data-reveal="up"><?php esc_html_e( 'Documentos técnicos', 'lubrax' ); ?></h2>
						<ul class="list-unstyled d-grid gap-3 mb-0" data-reveal-stagger="0.1">
							<?php
							foreach ( $lbx_docs as $lbx_doc ) {
								echo lbx_doc_card( $lbx_doc, $p['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput
							}
							?>
						</ul>
						<p class="small text-body-secondary mt-2"><?php esc_html_e( '"Ver" abre el documento aquí mismo, sin salir de la página. En el teléfono puedes abrirlo a pantalla completa o descargarlo.', 'lubrax' ); ?></p>
					</div>
				<?php endif; ?>
				<div class="wholesale<?php echo $lbx_docs ? ' mt-4' : ''; ?>" data-reveal="up">
					<h3><?php echo esc_html( $attributes['wholesaleTitle'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $attributes['wholesaleText'] ?? '' ); ?></p>
					<?php echo lbx_quote_button( $lbx_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="section bg-paper on-light" aria-labelledby="whereTitle">
	<div class="container-xxl">
		<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
			<div data-reveal="up">
				<p class="t-kicker"><?php esc_html_e( 'Dónde comprar', 'lubrax' ); ?></p>
				<?php /* translators: %s: "3 distribuidores". */ ?>
				<h2 class="mb-0" id="whereTitle"><?php echo esc_html( sprintf( __( 'Disponible en %s', 'lubrax' ), $lbx_dist_count ) ); ?></h2>
			</div>
			<a class="link-arrow" href="<?php echo esc_url( lbx_where_to_buy_url( $p['id'] ) ); ?>" data-reveal="up"><?php esc_html_e( 'Ver en el mapa', 'lubrax' ); ?> <?php echo lbx_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>
		<ul class="row g-3 list-unstyled mb-0" data-reveal-stagger="0.06">
			<?php if ( $lbx_dists ) : ?>
				<?php foreach ( $lbx_dists as $lbx_dist ) : ?>
					<li class="col-sm-6 col-lg-4" data-reveal="up">
						<div class="mini-dist" data-mini-dist="<?php echo esc_attr( $lbx_dist['id'] ); ?>">
							<strong><?php echo esc_html( $lbx_dist['name'] ); ?></strong>
							<span><?php echo esc_html( implode( ' · ', array_filter( array( $lbx_dist['label'], $lbx_dist['city'] ) ) ) ); ?></span>
							<?php if ( $lbx_dist['purchase'] ) : ?>
								<span><?php echo esc_html( $lbx_dist['purchase'] ); ?></span>
							<?php endif; ?>
							<?php if ( $lbx_dist['phone'] ) : ?>
								<a href="<?php echo esc_attr( lbx_tel_href( $lbx_dist['phone'] ) ); ?>" class="small"><?php echo lbx_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $lbx_dist['phone'] ); ?></a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			<?php else : ?>
				<li class="col-12"><p class="mb-0"><?php esc_html_e( 'Este producto se vende por pedido.', 'lubrax' ); ?> <a href="<?php echo esc_url( lbx_contact_url( 'pedido' ) ); ?>"><?php esc_html_e( 'Consúltanos', 'lubrax' ); ?></a>.</p></li>
			<?php endif; ?>
		</ul>
	</div>
</section>

<?php if ( $lbx_related ) : ?>
	<section class="section on-light" aria-labelledby="relatedTitle">
		<div class="container-xxl">
			<h2 class="mb-4" id="relatedTitle" data-reveal="up"><?php echo esc_html( $attributes['relatedTitle'] ?? '' ); ?></h2>
			<ul class="product-grid product-grid--related" data-reveal-stagger="0.06">
				<?php
				foreach ( $lbx_related as $lbx_product ) {
					echo lbx_product_card( $lbx_product ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</ul>
		</div>
	</section>
<?php endif; ?>
