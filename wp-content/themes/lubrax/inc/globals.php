<?php
/**
 * Marcado global que no es contenido editable: sprite SVG, menú lateral (offcanvas),
 * modal de distribuidor activo y botón "volver arriba". Mismo marcado que la maqueta.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sprite de íconos, al inicio del <body>. Se usa con lbx_icon( 'search' ).
 */
add_action(
	'wp_body_open',
	function () {
		$sprite = LBX_DIR . '/assets/icons/sprite.svg';
		if ( is_readable( $sprite ) ) {
			echo file_get_contents( $sprite ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG estático del tema.
		}
	},
	1
);

/**
 * <main id="main">: tabindex="-1" para que "Saltar al contenido" y "Volver arriba" le den el foco.
 */
add_filter(
	'render_block_core/group',
	function ( string $html ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() && 'MAIN' === $tags->get_tag() && 'main' === $tags->get_attribute( 'id' ) ) {
			$tags->set_attribute( 'tabindex', '-1' );
			return $tags->get_updated_html();
		}
		return $html;
	}
);

/**
 * Opciones del modal de distribuidor. El CPT Distribuidores (Paso 2) las imprime con este filtro:
 * <option value="zona-talca" data-label="Zona Talca" data-region="Maule" data-lat="…" data-lng="…">Nombre</option>
 */
function lbx_distributor_options_html(): string {
	return (string) apply_filters( 'lbx_distributor_options_html', '' );
}

add_action( 'wp_footer', 'lbx_render_global_ui', 5 );

/**
 * Menú lateral, modal de distribuidor y "volver arriba".
 */
function lbx_render_global_ui(): void {
	$catalog_url = lbx_page_url( 'productos' );
	$whatsapp    = lbx_option( 'whatsapp' );
	?>
	<div class="offcanvas offcanvas-end menu-panel" tabindex="-1" id="menuPanel" aria-labelledby="menuPanelTitle">
		<div class="offcanvas-header">
			<img src="<?php echo esc_url( lbx_asset( 'img/brand/lubrax.svg' ) ); ?>" alt="" width="112" height="14" loading="lazy">
			<h2 class="visually-hidden" id="menuPanelTitle"><?php esc_html_e( 'Menú', 'lubrax' ); ?></h2>
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php esc_attr_e( 'Cerrar menú', 'lubrax' ); ?>"></button>
		</div>
		<div class="offcanvas-body">
			<form class="menu-search form-dark" role="search" action="<?php echo esc_url( $catalog_url ); ?>" method="get">
				<label for="menuSearch" class="visually-hidden"><?php esc_html_e( 'Buscar productos', 'lubrax' ); ?></label>
				<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<input class="form-control" type="search" id="menuSearch" name="q" placeholder="<?php esc_attr_e( 'Producto, viscosidad o norma', 'lubrax' ); ?>" autocomplete="off" enterkeyhint="search">
			</form>
			<nav aria-label="<?php esc_attr_e( 'Menú del sitio', 'lubrax' ); ?>">
				<?php
				echo lbx_nav_menu( // phpcs:ignore WordPress.Security.EscapeOutput
					'offcanvas',
					'menu-links',
					array(
						array( __( 'Inicio', 'lubrax' ), home_url( '/' ) ),
						array( __( 'Productos', 'lubrax' ), $catalog_url ),
						array( __( 'Distribuidores', 'lubrax' ), lbx_page_url( 'distribuidores' ) ),
						array( __( 'Quiénes somos', 'lubrax' ), lbx_page_url( 'nosotros' ) ),
						array( __( 'Blog', 'lubrax' ), lbx_page_url( 'blog' ) ),
						array( __( 'Contacto', 'lubrax' ), lbx_page_url( 'contacto' ) ),
					)
				);
				?>
			</nav>
			<?php echo lbx_nav_menu( 'offcanvas-secondary', 'menu-secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="menu-contact">
				<p class="footer-title"><?php esc_html_e( 'Atención al cliente', 'lubrax' ); ?></p>
				<p class="mb-1"><a href="<?php echo esc_attr( lbx_tel_href( lbx_option( 'phone' ) ) ); ?>"><?php echo esc_html( lbx_option( 'phone' ) ); ?></a></p>
				<p class="mb-1"><a href="mailto:<?php echo esc_attr( lbx_option( 'email' ) ); ?>"><?php echo esc_html( lbx_option( 'email' ) ); ?></a></p>
				<?php if ( $whatsapp ) : ?>
					<p class="mb-0"><a href="<?php echo esc_url( lbx_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( 'WhatsApp ' . $whatsapp ); ?><?php echo lbx_new_tab_label(); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
				<?php endif; ?>
			</div>
			<?php lbx_social_list(); ?>
			<a class="btn btn-lbx mt-auto" href="<?php echo esc_url( lbx_contact_url( 'distribuidor' ) ); ?>"><?php esc_html_e( 'Quiero ser distribuidor', 'lubrax' ); ?> <?php echo lbx_icon( 'arrow-right', 'icon icon-arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>
	</div>

	<div class="modal fade modal-dark" id="distributorModal" tabindex="-1" aria-labelledby="distributorModalTitle" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form class="modal-content form-dark" novalidate>
				<div class="modal-header border-0 pb-0">
					<h2 class="modal-title h4" id="distributorModalTitle"><?php esc_html_e( 'Elige tu distribuidor', 'lubrax' ); ?></h2>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Cerrar', 'lubrax' ); ?>"></button>
				</div>
				<div class="modal-body">
					<p class="dist-modal__intro"><?php esc_html_e( 'Verás el catálogo y la disponibilidad de productos de tu zona. Ahora estás viendo:', 'lubrax' ); ?> <strong class="dist-modal__current" data-dist-fullname><?php esc_html_e( 'todos los distribuidores', 'lubrax' ); ?></strong>.</p>
					<div class="mb-3">
						<label class="form-label" for="distributorRegion"><?php esc_html_e( 'Región', 'lubrax' ); ?></label>
						<select class="form-select" id="distributorRegion">
							<option value=""><?php esc_html_e( 'Todas las regiones', 'lubrax' ); ?></option>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label" for="distributorSelect"><?php esc_html_e( 'Distribuidor', 'lubrax' ); ?></label>
						<select class="form-select" id="distributorSelect" name="distribuidor">
							<option value=""><?php esc_html_e( 'Todos los distribuidores', 'lubrax' ); ?></option>
							<?php echo lbx_distributor_options_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado al generarse. ?>
						</select>
					</div>
					<button type="button" class="link-arrow link-arrow--yellow btn-reset" data-dist-geolocate><?php echo lbx_icon( 'locate' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Usar mi ubicación', 'lubrax' ); ?></button>
					<p class="small mt-2 mb-0" data-dist-geo-status aria-live="polite"></p>
				</div>
				<div class="modal-footer border-0 pt-0 justify-content-between">
					<button type="button" class="btn btn-lbx-outline btn-sm" data-dist-clear><?php esc_html_e( 'Ver todos', 'lubrax' ); ?></button>
					<button type="submit" class="btn btn-lbx"><?php esc_html_e( 'Confirmar', 'lubrax' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<button type="button" class="back-to-top" id="backToTop" aria-label="<?php esc_attr_e( 'Volver arriba', 'lubrax' ); ?>"><?php echo lbx_icon( 'arrow-up' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	<?php
}

/**
 * Lista de redes sociales (footer y menú lateral).
 */
function lbx_social_list(): void {
	$links = lbx_social_links();
	if ( ! $links ) {
		return;
	}

	echo '<ul class="social-links">';
	foreach ( $links as $network => $link ) {
		printf(
			'<li><a href="%1$s" target="_blank" rel="noopener">%2$s<span class="visually-hidden">%3$s</span></a></li>',
			esc_url( $link['url'] ),
			lbx_icon( $network ), // phpcs:ignore WordPress.Security.EscapeOutput
			/* translators: %s: red social. */
			esc_html( sprintf( __( '%s (abre en una pestaña nueva)', 'lubrax' ), $link['label'] ) )
		);
	}
	echo '</ul>';
}
