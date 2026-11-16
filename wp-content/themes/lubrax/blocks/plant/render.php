<?php
/**
 * Bloque SCF lubrax/plant. Marcado: section.plant ("Planta de Lubricantes y Especialidades") de index.html.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

$lbx_d    = lbx_block_defaults( 'plant' );
$lbx_id   = wp_unique_id( 'plantTitle-' );
$lbx_link = lbx_link_value( lbx_block_value( 'plant_enlace', 'plant' ) );
$lbx_card = (string) lbx_block_value( 'plant_card_titulo', 'plant' );
?>
<section class="plant section on-light" aria-labelledby="<?php echo esc_attr( $lbx_id ); ?>">
	<div class="container-xxl">
		<div class="row g-4 g-lg-0 align-items-center">
			<div class="col-lg-4 pe-lg-5">
				<p class="t-kicker t-kicker--muted" data-reveal="up"><?php echo esc_html( lbx_block_value( 'plant_antetitulo', 'plant' ) ); ?></p>
				<h2 class="plant__title t-tight" id="<?php echo esc_attr( $lbx_id ); ?>" data-reveal="title"><?php echo lbx_title_lines( (string) lbx_block_value( 'plant_titulo', 'plant' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<?php echo lbx_paragraphs( (string) lbx_block_value( 'plant_texto', 'plant' ), ' data-reveal="up"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo lbx_link_html( $lbx_link, 'link-arrow', lbx_icon( 'arrow-right' ), ' data-reveal="up"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="col-md-7 col-lg-5">
				<div class="plant__media" data-reveal="clip">
					<?php echo lbx_image_or_asset( (int) get_field( 'plant_imagen' ), $lbx_d['plant_asset'], array( 'alt' => $lbx_d['plant_alt'], 'sizes' => '(min-width: 992px) 42vw, (min-width: 768px) 58vw, 100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<?php if ( $lbx_card ) : ?>
				<div class="col-md-5 col-lg-3">
					<div class="plant__card" data-reveal="right" data-reveal-delay="0.2">
						<?php $lbx_badge = (string) lbx_block_value( 'plant_insignia', 'plant' ); ?>
						<?php if ( $lbx_badge ) : ?>
							<span class="plant__badge"><?php echo lbx_icon( 'droplet' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $lbx_badge ); ?></span>
						<?php endif; ?>
						<?php echo lbx_image_or_asset( (int) get_field( 'plant_card_imagen' ), $lbx_d['plant_card_asset'], array( 'alt' => $lbx_d['plant_card_alt'], 'sizes' => '240px' ), 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<h3><?php echo esc_html( $lbx_card ); ?></h3>
						<p class="mb-0"><?php echo esc_html( lbx_block_value( 'plant_card_texto', 'plant' ) ); ?></p>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
