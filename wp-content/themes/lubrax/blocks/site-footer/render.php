<?php
/**
 * Bloque lubrax/site-footer. Marcado: <footer class="site-footer"> de la maqueta (index.html).
 *
 * @package Lubrax
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$lbx_phone   = lbx_option( 'phone' );
$lbx_email   = lbx_option( 'email' );
$lbx_privacy = get_privacy_policy_url();
?>
<footer class="site-footer">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<div class="col-lg-4">
				<img class="site-footer__logo" src="<?php echo esc_url( lbx_asset( 'img/brand/lubrax.svg' ) ); ?>" alt="Lubrax" width="324" height="40" loading="lazy">
				<p class="mb-4"><?php echo esc_html( lbx_option( 'footer_text' ) ); ?></p>
				<?php lbx_social_list(); ?>
			</div>
			<div class="col-sm-6 col-lg-2">
				<h2 class="footer-title"><?php esc_html_e( 'Dirección', 'lubrax' ); ?></h2>
				<address><?php echo nl2br( esc_html( lbx_option( 'address' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></address>
			</div>
			<div class="col-sm-6 col-lg-2">
				<h2 class="footer-title"><?php esc_html_e( 'Contacto', 'lubrax' ); ?></h2>
				<p><a href="<?php echo esc_attr( lbx_tel_href( $lbx_phone ) ); ?>"><?php echo esc_html( 'T ' . $lbx_phone ); ?></a><br><a href="mailto:<?php echo esc_attr( $lbx_email ); ?>"><?php echo esc_html( $lbx_email ); ?></a></p>
			</div>
			<div class="col-lg-4">
				<?php if ( ! empty( $attributes['showNewsletter'] ) ) : ?>
					<h2 class="footer-title" id="newsletterTitle"><?php echo esc_html( $attributes['newsletterTitle'] ?? '' ); ?></h2>
					<?php
					// Formulario "Newsletter" de Contact Form 7 (inc/contact.php): las suscripciones quedan en CFDB7.
					$lbx_newsletter = lbx_form_html(
						lbx_form_id( 'newsletter' ),
						array(
							'html_class' => 'newsletter form-dark needs-validation',
							'html_title' => (string) ( $attributes['newsletterTitle'] ?? '' ),
						)
					);
					?>
					<?php if ( $lbx_newsletter ) : ?>
						<?php echo $lbx_newsletter; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML de Contact Form 7. ?>
					<?php else : ?>
					<form class="newsletter form-dark needs-validation" action="#" method="post" novalidate aria-labelledby="newsletterTitle">
						<label for="newsletterEmail" class="visually-hidden"><?php esc_html_e( 'Correo electrónico', 'lubrax' ); ?></label>
						<input type="email" class="form-control" id="newsletterEmail" name="email" placeholder="<?php esc_attr_e( 'Tu correo electrónico', 'lubrax' ); ?>" autocomplete="email" required>
						<button class="btn btn-lbx btn-sm" type="submit"><?php esc_html_e( 'Suscribirme', 'lubrax' ); ?></button>
						<div class="invalid-feedback"><?php esc_html_e( 'Ingresa un correo válido.', 'lubrax' ); ?></div>
					</form>
					<?php endif; ?>
				<?php endif; ?>
				<div class="footer-pills">
					<a href="<?php echo esc_url( lbx_page_url( 'distribuidores' ) ); ?>"><?php esc_html_e( 'Buscar distribuidor', 'lubrax' ); ?></a>
					<a href="<?php echo esc_url( lbx_contact_url( 'distribuidor' ) ); ?>"><?php esc_html_e( 'Quiero ser distribuidor', 'lubrax' ); ?></a>
				</div>
			</div>
		</div>

		<nav class="footer-row" aria-labelledby="footerExplore">
			<h2 class="footer-title" id="footerExplore"><?php esc_html_e( 'Explora', 'lubrax' ); ?></h2>
			<?php
			echo lbx_nav_menu( // phpcs:ignore WordPress.Security.EscapeOutput
				'footer-explore',
				'',
				array(
					array( __( 'Catálogo', 'lubrax' ), lbx_page_url( 'productos' ) ),
					array( __( 'Quiénes somos', 'lubrax' ), lbx_page_url( 'nosotros' ) ),
					array( __( 'Blog', 'lubrax' ), lbx_page_url( 'blog' ) ),
				)
			);
			?>
		</nav>

		<div class="footer-bottom">
			<p class="mb-0">&copy; <span data-year><?php echo esc_html( wp_date( 'Y' ) ); ?></span> <?php esc_html_e( 'Lubrax. Todos los derechos reservados.', 'lubrax' ); ?></p>
			<?php
			echo lbx_nav_menu( // phpcs:ignore WordPress.Security.EscapeOutput
				'footer-legal',
				'',
				array_filter(
					array(
						$lbx_privacy ? array( __( 'Política de privacidad', 'lubrax' ), $lbx_privacy ) : null,
						array( __( 'Términos y condiciones', 'lubrax' ), lbx_page_url( 'terminos' ) ),
					)
				)
			);
			?>
		</div>
	</div>
</footer>
