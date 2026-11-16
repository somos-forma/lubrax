<?php
/**
 * Bloque SCF lubrax/contact-services. Marcado: sección "¿Cómo te ayudamos?" de contacto.html.
 * Los botones llevan data-contact-type: contact.js elige el motivo y baja al formulario (sin JS, ?tipo= en la URL).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

$lbx_d     = lbx_block_defaults( 'contact-services' );
$lbx_items = ( function_exists( 'get_field' ) ? get_field( 'srv_items' ) : null ) ?: $lbx_d['srv_items'];
$lbx_id    = wp_unique_id( 'servicesTitle-' );
$lbx_text  = (string) lbx_block_value( 'srv_texto', 'contact-services' );
?>
<section class="section on-light" aria-labelledby="<?php echo esc_attr( $lbx_id ); ?>">
	<div class="container-xxl">
		<div class="row align-items-end g-3 mb-4 mb-lg-5">
			<div class="col-lg-6" data-reveal="up">
				<p class="t-kicker"><?php echo esc_html( lbx_block_value( 'srv_antetitulo', 'contact-services' ) ); ?></p>
				<h2 class="t-display mb-0" id="<?php echo esc_attr( $lbx_id ); ?>"><?php echo esc_html( lbx_block_value( 'srv_titulo', 'contact-services' ) ); ?></h2>
			</div>
			<?php if ( $lbx_text ) : ?>
				<p class="col-lg-6 mb-0 text-body-secondary" data-reveal="up"><?php echo esc_html( $lbx_text ); ?></p>
			<?php endif; ?>
		</div>
		<div class="row g-4">
			<?php foreach ( $lbx_items as $lbx_item ) : ?>
				<?php
				$lbx_tipo   = sanitize_key( $lbx_item['tipo'] ?? '' );
				$lbx_accent = ! empty( $lbx_item['destacado'] );
				?>
				<div class="col-sm-6 col-xl-3" data-reveal="up">
					<article class="service-card<?php echo $lbx_accent ? ' service-card--accent' : ''; ?>">
						<span class="service-card__icon"><?php echo lbx_icon( sanitize_key( $lbx_item['icono'] ?? 'check-circle' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h3><?php echo esc_html( $lbx_item['titulo'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $lbx_item['texto'] ?? '' ); ?></p>
						<?php if ( ! empty( $lbx_item['boton'] ) ) : ?>
							<a class="btn <?php echo $lbx_accent ? 'btn-lbx-dark' : 'btn-lbx-outline-dark'; ?> btn-sm" href="<?php echo esc_url( lbx_contact_url( $lbx_tipo ) ); ?>"<?php echo $lbx_tipo ? ' data-contact-type="' . esc_attr( $lbx_tipo ) . '"' : ''; ?>><?php echo esc_html( $lbx_item['boton'] ); ?></a>
						<?php endif; ?>
					</article>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
