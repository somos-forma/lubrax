<?php
/**
 * Bloque lubrax/error-404. Marcado: section.error-page de 404.html.
 *
 * Los textos y enlaces se editan en Opciones del tema > Página 404 (vacío: texto de la maqueta).
 * La plantilla 404 responde con estado 404 real (sin redirección: ver inc/seo.php) y noindex, follow.
 * "Volver atrás" (data-back, app.js) regresa a la página anterior del sitio; sin historial, lleva al inicio.
 * El buscador usa el buscador interno (productos, artículos y páginas).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

$lbx_button = lbx_link_value( lbx_option_field( 'e404_button' ) ) ?? array(
	'url'    => lbx_page_url( 'productos' ),
	'title'  => __( 'Ir al catálogo', 'lubrax' ),
	'target' => '',
);

$lbx_links = array_values( array_filter( array_map( fn( $row ) => lbx_link_value( $row['enlace'] ?? null ), (array) lbx_option_field( 'e404_links' ) ) ) );
if ( ! $lbx_links ) {
	foreach ( array( array( __( 'Inicio', 'lubrax' ), home_url( '/' ) ), array( __( 'Distribuidores', 'lubrax' ), lbx_page_url( 'distribuidores' ) ), array( __( 'Blog', 'lubrax' ), lbx_page_url( 'blog' ) ), array( __( 'Contacto', 'lubrax' ), lbx_page_url( 'contacto' ) ) ) as [ $lbx_label, $lbx_url ] ) {
		$lbx_links[] = array( 'url' => $lbx_url, 'title' => $lbx_label, 'target' => '' );
	}
}
?>
<section class="error-page" aria-labelledby="errorTitle">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5 align-items-center">
			<div class="col-lg-6" data-reveal="zoom">
				<p class="error-page__code" aria-hidden="true">404</p>
			</div>
			<div class="col-lg-6">
				<p class="t-kicker" data-reveal="up"><?php echo esc_html( lbx_option( 'e404_kicker' ) ); ?></p>
				<h1 class="error-page__title t-display" id="errorTitle" data-reveal="title"><?php echo lbx_title_lines( lbx_option( 'e404_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
				<p class="t-lead" data-reveal="up"><?php echo esc_html( lbx_option( 'e404_lead' ) ); ?></p>
				<div class="d-flex flex-wrap gap-3 mt-4" data-reveal="up">
					<a class="btn btn-lbx" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-back><?php echo lbx_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( lbx_option( 'e404_back' ) ); ?></a>
					<a class="btn btn-lbx-outline" href="<?php echo esc_url( $lbx_button['url'] ); ?>"<?php echo '_blank' === $lbx_button['target'] ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo lbx_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $lbx_button['title'] ); ?></a>
				</div>
				<form class="menu-search form-dark error-page__search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" data-reveal="up">
					<label for="errorSearch" class="visually-hidden"><?php esc_html_e( 'Buscar en el sitio', 'lubrax' ); ?></label>
					<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<input class="form-control" type="search" id="errorSearch" name="s" placeholder="<?php echo esc_attr( lbx_option( 'e404_search' ) ); ?>" autocomplete="off" enterkeyhint="search">
				</form>
				<nav class="error-page__links" aria-labelledby="errorLinksTitle" data-reveal="up">
					<p class="footer-title" id="errorLinksTitle"><?php echo esc_html( lbx_option( 'e404_links_title' ) ); ?></p>
					<ul>
						<?php foreach ( $lbx_links as $lbx_link ) : ?>
							<li><?php echo lbx_link_html( $lbx_link, '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</div>
		</div>
	</div>
</section>
