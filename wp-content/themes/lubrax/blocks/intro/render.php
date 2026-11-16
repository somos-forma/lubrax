<?php
/**
 * Bloque SCF lubrax/intro. Marcado: section.intro ("Catálogo regional") de index.html.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

$lbx_d     = lbx_block_defaults( 'intro' );
$lbx_id    = wp_unique_id( 'introTitle-' );
$lbx_link1 = lbx_link_value( lbx_block_value( 'intro_boton', 'intro' ) );
$lbx_link2 = lbx_link_value( lbx_block_value( 'intro_enlace', 'intro' ) );
?>
<section class="intro section" aria-labelledby="<?php echo esc_attr( $lbx_id ); ?>">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5 align-items-center">
			<div class="col-sm-6" data-reveal="zoom">
				<div class="intro__media" data-parallax="0.08">
					<?php echo lbx_image_or_asset( (int) get_field( 'intro_imagen' ), $lbx_d['intro_asset'], array( 'alt' => $lbx_d['intro_alt'], 'sizes' => '(min-width: 992px) 560px, 90vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="intro__panel" data-reveal="right">
					<p class="t-kicker"><?php echo esc_html( lbx_block_value( 'intro_antetitulo', 'intro' ) ); ?></p>
					<h2 class="intro__title t-display" id="<?php echo esc_attr( $lbx_id ); ?>" data-reveal="title"><?php echo lbx_title_lines( (string) lbx_block_value( 'intro_titulo', 'intro' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
					<?php echo lbx_paragraphs( (string) lbx_block_value( 'intro_texto', 'intro' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php if ( $lbx_link1 || $lbx_link2 ) : ?>
						<div class="d-flex flex-wrap align-items-center gap-4 mt-4">
							<?php echo lbx_link_html( $lbx_link1, 'btn btn-lbx' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php echo lbx_link_html( $lbx_link2, 'link-arrow link-arrow--yellow', lbx_icon( 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
