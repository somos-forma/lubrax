<?php
/**
 * Bloque SCF lubrax/contact-form. Marcado: sección #formulario de contacto.html.
 *
 * El formulario lo imprime Contact Form 7 (plantilla con el marcado de la maqueta, ver inc/contact.php).
 * contact.js muestra los campos según el motivo; forms.js lo envía y muestra [data-form-success] con el
 * número de caso o [data-form-error].
 *
 * @package Lubrax
 *
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$lbx_d       = lbx_block_defaults( 'contact-form' );
$lbx_form_id = (int) ( function_exists( 'get_field' ) ? get_field( 'cf_formulario', false, false ) : 0 ) ?: lbx_form_id( 'contact' );
$lbx_form    = lbx_form_html(
	$lbx_form_id,
	array(
		'html_id'    => 'contactForm',
		'html_class' => 'needs-validation',
	)
);
$lbx_items   = ( function_exists( 'get_field' ) ? get_field( 'cf_faq_items' ) : null ) ?: $lbx_d['cf_faq_items'];
$lbx_items   = array_values( array_filter( $lbx_items, fn( $item ) => ! empty( $item['pregunta'] ) && ! empty( $item['respuesta'] ) ) );
$lbx_faq     = wp_unique_id( 'attentionFaq-' );
$lbx_note    = (string) lbx_block_value( 'cf_info_nota', 'contact-form' );

if ( empty( $is_preview ) ) {
	wp_enqueue_script( 'lbx-contact' );
}
?>
<section class="section bg-paper on-light" id="formulario" aria-labelledby="formTitle">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<div class="col-lg-7">
				<div class="contact-form" data-reveal="up" data-form-wrap>
					<h2 class="contact-form__title" id="formTitle"><?php echo esc_html( lbx_block_value( 'cf_titulo', 'contact-form' ) ); ?></h2>
					<?php if ( $lbx_form ) : ?>
						<p class="text-body-secondary"><?php esc_html_e( 'Los campos marcados con', 'lubrax' ); ?> <span aria-hidden="true">*</span><span class="visually-hidden"><?php esc_html_e( 'asterisco', 'lubrax' ); ?></span> <?php esc_html_e( 'son obligatorios.', 'lubrax' ); ?></p>
						<?php echo $lbx_form; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML de Contact Form 7. ?>
						<div class="alert alert-danger mt-3 mb-0" data-form-error role="alert" hidden></div>
						<div class="form-success" data-form-success hidden tabindex="-1" role="status">
							<?php echo lbx_icon( 'check-circle', 'icon mb-3 fs-1' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<h3><?php echo esc_html( lbx_block_value( 'cf_exito_titulo', 'contact-form' ) ); ?></h3>
							<p data-form-case hidden><?php esc_html_e( 'Tu número de caso es', 'lubrax' ); ?> <strong></strong></p>
							<p><?php echo esc_html( lbx_block_value( 'cf_exito_texto', 'contact-form' ) ); ?></p>
							<button type="button" class="btn btn-lbx btn-sm" data-form-reset><?php esc_html_e( 'Enviar otro mensaje', 'lubrax' ); ?></button>
						</div>
					<?php else : ?>
						<p class="text-body-secondary">
							<?php
							/* translators: %s: correo de atención. */
							printf( esc_html__( 'Escríbenos a %s y te responderemos a la brevedad.', 'lubrax' ), '<a href="mailto:' . esc_attr( lbx_option( 'email' ) ) . '">' . esc_html( lbx_option( 'email' ) ) . '</a>' );
							?>
						</p>
						<?php if ( ! empty( $is_preview ) ) : ?>
							<p class="small text-danger"><?php esc_html_e( 'Activa Contact Form 7 y elige el formulario en Opciones del tema > Formularios.', 'lubrax' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="col-lg-5">
				<div class="info-card mb-4" data-reveal="up">
					<h3><?php echo lbx_icon( 'factory' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( lbx_block_value( 'cf_info_titulo', 'contact-form' ) ); ?></h3>
					<p class="mb-2"><?php echo esc_html( lbx_block_value( 'cf_info_texto', 'contact-form' ) ); ?></p>
					<?php if ( $lbx_note ) : ?>
						<p class="mb-3 text-body-secondary small"><?php echo esc_html( $lbx_note ); ?></p>
					<?php endif; ?>
					<?php echo lbx_link_html( lbx_link_value( lbx_block_value( 'cf_info_enlace', 'contact-form' ) ), 'link-arrow', lbx_icon( 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<?php if ( $lbx_items ) : ?>
					<div class="faq" data-reveal="up">
						<h2 class="h4 mb-3"><?php echo esc_html( lbx_block_value( 'cf_faq_titulo', 'contact-form' ) ); ?></h2>
						<div class="accordion" id="<?php echo esc_attr( $lbx_faq ); ?>">
							<?php foreach ( $lbx_items as $lbx_i => $lbx_item ) : ?>
								<?php $lbx_uid = $lbx_faq . '-' . ( $lbx_i + 1 ); ?>
								<div class="accordion-item" data-reveal="up">
									<h3 class="accordion-header">
										<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $lbx_uid ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $lbx_uid ); ?>"><?php echo esc_html( $lbx_item['pregunta'] ); ?></button>
									</h3>
									<div id="<?php echo esc_attr( $lbx_uid ); ?>" class="accordion-collapse collapse" data-bs-parent="#<?php echo esc_attr( $lbx_faq ); ?>">
										<div class="accordion-body"><?php echo nl2br( esc_html( $lbx_item['respuesta'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<?php echo lbx_link_html( lbx_link_value( lbx_block_value( 'cf_faq_enlace', 'contact-form' ) ), 'link-arrow mt-4', lbx_icon( 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
