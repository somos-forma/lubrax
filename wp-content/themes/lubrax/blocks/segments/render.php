<?php
/**
 * Bloque SCF lubrax/segments. Marcado: section.cats + tpl-category de index.html.
 * Los segmentos (nombre, ícono, orden y cantidad) salen de la taxonomía Segmento.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lbx_segments' ) ) {
	return;
}

$lbx_d    = lbx_block_defaults( 'segments' );
$lbx_link = lbx_link_value( lbx_block_value( 'seg_enlace', 'segments' ) );
?>
<section class="cats section" aria-labelledby="catsTitle">
	<div class="cats__bg" data-parallax="0.12">
		<?php echo lbx_image_or_asset( (int) get_field( 'seg_fondo' ), $lbx_d['seg_asset'], array( 'sizes' => '100vw' ), 'full' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="container-xxl">
		<div class="row g-4 g-lg-5 align-items-center">
			<div class="col-lg-4">
				<p class="t-kicker" data-reveal="up"><?php echo esc_html( lbx_block_value( 'seg_antetitulo', 'segments' ) ); ?></p>
				<h2 class="cats__title t-huge-light" id="catsTitle" data-reveal="title"><?php echo lbx_title_lines( (string) lbx_block_value( 'seg_titulo', 'segments' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<?php echo lbx_link_html( $lbx_link, 'link-arrow', lbx_icon( 'arrow-right' ), ' data-reveal="up" data-reveal-delay="0.2"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="col-lg-8">
				<ul class="cat-grid" data-reveal-stagger="0.07">
					<?php foreach ( lbx_segments() as $lbx_segment ) : ?>
						<?php
						/* translators: %d: cantidad de productos. */
						$lbx_count = sprintf( _n( '%d producto', '%d productos', $lbx_segment->count, 'lubrax' ), $lbx_segment->count );
						$lbx_icon  = lbx_segment_icon_url( $lbx_segment->term_id );
						?>
						<li data-reveal="up">
							<a class="cat-card" href="<?php echo esc_url( get_term_link( $lbx_segment ) ); ?>">
								<?php if ( $lbx_icon ) : ?>
									<img class="cat-card__icon" src="<?php echo esc_url( $lbx_icon ); ?>" alt="" width="48" height="48">
								<?php endif; ?>
								<?php echo lbx_icon( 'arrow-up-right', 'icon cat-card__arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span>
									<span class="cat-card__name"><?php echo esc_html( $lbx_segment->name ); ?></span>
									<span class="cat-card__count"><?php echo esc_html( $lbx_count ); ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>
