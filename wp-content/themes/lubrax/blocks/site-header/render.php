<?php
/**
 * Bloque lubrax/site-header. Marcado: <header class="site-header"> de la maqueta (index.html).
 *
 * @package Lubrax
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$lbx_cta_label = trim( (string) ( $attributes['ctaLabel'] ?? '' ) );
?>
<header class="site-header">
	<div class="container-xxl">
		<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( lbx_asset( 'img/brand/lubrax.svg' ) ); ?>" alt="<?php esc_attr_e( 'Lubrax, ir al inicio', 'lubrax' ); ?>" width="324" height="40">
		</a>

		<button type="button" class="dist-picker" data-bs-toggle="modal" data-bs-target="#distributorModal" aria-haspopup="dialog">
			<?php echo lbx_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="dist-picker__text">
				<span class="dist-picker__label" data-dist-label><?php esc_html_e( 'Distribuidor', 'lubrax' ); ?></span>
				<span class="dist-picker__name" data-dist-name><?php esc_html_e( 'Todos', 'lubrax' ); ?></span>
			</span>
			<span class="visually-hidden"><?php esc_html_e( '. Cambiar distribuidor', 'lubrax' ); ?></span>
		</button>

		<nav class="main-nav" aria-label="<?php esc_attr_e( 'Principal', 'lubrax' ); ?>">
			<?php
			echo lbx_nav_menu( // phpcs:ignore WordPress.Security.EscapeOutput
				'primary',
				'',
				array(
					array( __( 'Inicio', 'lubrax' ), home_url( '/' ) ),
					array( __( 'Productos', 'lubrax' ), lbx_page_url( 'productos' ) ),
					array( __( 'Distribuidores', 'lubrax' ), lbx_page_url( 'distribuidores' ) ),
					array( __( 'Blog', 'lubrax' ), lbx_page_url( 'blog' ) ),
					array( __( 'Contacto', 'lubrax' ), lbx_page_url( 'contacto' ) ),
				)
			);
			?>
		</nav>

		<div class="header-actions">
			<?php if ( ! empty( $attributes['showCta'] ) && $lbx_cta_label ) : ?>
				<a class="btn btn-lbx-outline btn-sm d-none d-xl-inline-flex" href="<?php echo esc_url( lbx_contact_url( (string) ( $attributes['ctaTipo'] ?? '' ) ) ); ?>"><?php echo esc_html( $lbx_cta_label ); ?></a>
			<?php endif; ?>
			<button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuPanel" aria-controls="menuPanel" aria-label="<?php esc_attr_e( 'Abrir menú', 'lubrax' ); ?>">
				<span></span><span></span>
			</button>
		</div>
	</div>
</header>
