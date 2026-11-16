<?php
/**
 * Bloque lubrax/cta-band. Marcado: section.cta-band de la maqueta (productos.html, producto.html).
 *
 * Botón principal sin URL: consulta técnica del formulario de contacto. Secundario sin URL: localizador.
 * Un botón sin texto no se muestra.
 *
 * @package Lubrax
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$lbx_title     = (string) ( $attributes['title'] ?? '' );
$lbx_primary   = trim( (string) ( $attributes['primaryLabel'] ?? '' ) );
$lbx_secondary = trim( (string) ( $attributes['secondaryLabel'] ?? '' ) );
$lbx_id        = wp_unique_id( 'ctaTitle-' );
?>
<section class="cta-band" aria-labelledby="<?php echo esc_attr( $lbx_id ); ?>">
	<div class="container-xxl">
		<div class="row g-4 align-items-center">
			<div class="col-lg-7" data-reveal="left">
				<h2 class="t-display mb-2" id="<?php echo esc_attr( $lbx_id ); ?>"><?php echo esc_html( $lbx_title ); ?></h2>
				<?php if ( ! empty( $attributes['text'] ) ) : ?>
					<p class="mb-0 t-lead"><?php echo esc_html( $attributes['text'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="col-lg-5 d-flex flex-wrap gap-3 justify-content-lg-end" data-reveal="right">
				<?php if ( $lbx_primary ) : ?>
					<a class="btn btn-lbx-dark" href="<?php echo esc_url( ( $attributes['primaryUrl'] ?? '' ) ?: lbx_contact_url( 'consulta' ) ); ?>"><?php echo esc_html( $lbx_primary ); ?> <?php echo lbx_icon( 'arrow-right', 'icon icon-arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
				<?php if ( $lbx_secondary ) : ?>
					<a class="btn btn-lbx-outline-dark" href="<?php echo esc_url( ( $attributes['secondaryUrl'] ?? '' ) ?: lbx_page_url( 'distribuidores' ) ); ?>"><?php echo esc_html( $lbx_secondary ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
