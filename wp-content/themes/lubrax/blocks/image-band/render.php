<?php
/**
 * Bloque SCF lubrax/image-band. Marcado: section.image-band ("Más que un lubricante") de nosotros.html.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

$lbx_d        = lbx_block_defaults( 'image-band' );
$lbx_services = ( function_exists( 'get_field' ) ? get_field( 'band_servicios' ) : null ) ?: $lbx_d['band_servicios'];
$lbx_id       = wp_unique_id( 'bandTitle-' );
$lbx_pillars  = wp_unique_id( 'pillarsTitle-' );
$lbx_button   = lbx_link_value( lbx_block_value( 'band_boton', 'image-band' ) );
?>
<section class="image-band" aria-labelledby="<?php echo esc_attr( $lbx_id ); ?>">
	<div class="image-band__bg" data-parallax="0.15">
		<?php echo lbx_image_or_asset( (int) get_field( 'band_imagen' ), $lbx_d['band_asset'], array( 'sizes' => '100vw' ), 'full' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<div class="col-lg-6">
				<p class="t-kicker" data-reveal="up"><?php echo esc_html( lbx_block_value( 'band_antetitulo', 'image-band' ) ); ?></p>
				<h2 class="t-display mb-5 text-white" id="<?php echo esc_attr( $lbx_pillars ); ?>"><?php echo esc_html( lbx_block_value( 'band_titulo', 'image-band' ) ); ?></h2>
				<div class="row g-4">
					<?php foreach ( array_values( $lbx_services ) as $lbx_i => $lbx_service ) : ?>
						<div class="col-sm-6 col-lg-6" data-reveal="up" data-reveal-delay="<?php echo esc_attr( (string) round( $lbx_i * 0.08, 2 ) ); ?>">
							<article class="service-card">
								<span class="service-card__icon"><?php echo lbx_icon( sanitize_key( $lbx_service['icono'] ?? 'check-circle' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<h3 class="text-white"><?php echo esc_html( $lbx_service['titulo'] ?? '' ); ?></h3>
								<p class="mb-0 text-white"><?php echo esc_html( $lbx_service['texto'] ?? '' ); ?></p>
							</article>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="col-lg-6 flex-brand">
				<h2 class="image-band__title t-display" id="<?php echo esc_attr( $lbx_id ); ?>" data-reveal="title"><?php echo lbx_title_lines( (string) lbx_block_value( 'band_titulo_grande', 'image-band' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<?php echo lbx_link_html( $lbx_button, 'btn btn-lbx mt-3', lbx_icon( 'arrow-right', 'icon icon-arrow' ), ' data-reveal="up"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
	</div>
</section>
