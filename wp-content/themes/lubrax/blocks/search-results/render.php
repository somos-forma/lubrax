<?php
/**
 * Bloque lubrax/search-results: buscador interno (?s=). Nuevo, no estaba en la maqueta: usa sus componentes
 * (encabezado de página, buscador del catálogo, tarjetas de producto y de artículo).
 *
 * Resultados agrupados: productos (también por viscosidad, norma o segmento: lbx_search_product_ids()),
 * artículos del blog y páginas. Los enlaces "Ver todos" abren el catálogo y el blog con el mismo texto (?q=).
 * WordPress marca la página de resultados con noindex.
 *
 * @package Lubrax
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$lbx_query = trim( get_search_query( false ) );

$lbx_products = function_exists( 'lbx_search_product_ids' ) ? lbx_search_product_ids( $lbx_query ) : array();
$lbx_posts    = '' === $lbx_query ? array() : get_posts( array( 'post_type' => 'post', 's' => $lbx_query, 'posts_per_page' => -1, 'fields' => 'ids' ) );
$lbx_pages    = '' === $lbx_query ? array() : get_posts(
	array(
		'post_type'      => 'page',
		's'              => $lbx_query,
		'posts_per_page' => 6,
		'post__not_in'   => function_exists( 'wc_get_page_id' ) ? array_filter( array_map( 'wc_get_page_id', array( 'cart', 'checkout', 'myaccount' ) ), fn( $id ) => $id > 0 ) : array(),
	)
);
$lbx_total    = count( $lbx_products ) + count( $lbx_posts ) + count( $lbx_pages );

$lbx_max_products = max( 1, (int) ( $attributes['products'] ?? 8 ) );
$lbx_max_posts    = max( 1, (int) ( $attributes['posts'] ?? 6 ) );
?>
<section class="page-hero page-hero--compact" aria-labelledby="pageTitle">
	<div class="page-hero__bg">
		<img src="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?>" srcset="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero-768.webp' ) ); ?> 768w, <?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?> 1600w" sizes="100vw" alt="" width="1600" height="893" decoding="async" fetchpriority="high">
	</div>
	<div class="container-xxl">
		<h1 class="page-hero__title t-display" id="pageTitle" data-reveal="title"><span class="line"><span><?php esc_html_e( 'Buscar', 'lubrax' ); ?></span></span></h1>
		<nav aria-label="<?php esc_attr_e( 'Ruta de navegación', 'lubrax' ); ?>" data-reveal="up" data-reveal-delay="0.15">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'lubrax' ); ?></a></li>
				<li class="breadcrumb-item active" aria-current="page"><?php esc_html_e( 'Búsqueda', 'lubrax' ); ?></li>
			</ol>
		</nav>
		<?php if ( '' !== $lbx_query ) : ?>
			<p class="page-hero__lead t-lead mb-0" data-reveal="up" data-reveal-delay="0.25" role="status">
				<?php
				/* translators: 1: cantidad de resultados, 2: texto buscado. */
				echo esc_html( sprintf( _n( '%1$d resultado para «%2$s»', '%1$d resultados para «%2$s»', $lbx_total, 'lubrax' ), $lbx_total, $lbx_query ) );
				?>
			</p>
		<?php endif; ?>
	</div>
</section>

<section class="section on-light search-results" aria-label="<?php esc_attr_e( 'Resultados', 'lubrax' ); ?>">
	<div class="container-xxl">
		<form class="catalog__search search-results__form mb-5" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" data-reveal="up">
			<label for="siteSearch" class="visually-hidden"><?php esc_html_e( 'Buscar en el sitio', 'lubrax' ); ?></label>
			<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<input class="form-control" type="search" id="siteSearch" name="s" value="<?php echo esc_attr( $lbx_query ); ?>" placeholder="<?php esc_attr_e( 'Producto, viscosidad, norma o artículo', 'lubrax' ); ?>" autocomplete="off" enterkeyhint="search">
		</form>

		<?php if ( '' !== $lbx_query && ! $lbx_total ) : ?>
			<div class="search-results__empty" data-reveal="up">
				<h2 class="h4"><?php esc_html_e( 'No encontramos resultados', 'lubrax' ); ?></h2>
				<p class="text-body-secondary"><?php esc_html_e( 'Prueba con otra palabra, el nombre de un producto, una viscosidad (15W-40) o una norma (API CK-4).', 'lubrax' ); ?></p>
				<div class="d-flex flex-wrap gap-3">
					<a class="btn btn-lbx-dark" href="<?php echo esc_url( lbx_page_url( 'productos' ) ); ?>"><?php esc_html_e( 'Ir al catálogo', 'lubrax' ); ?> <?php echo lbx_icon( 'arrow-right', 'icon icon-arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<a class="btn btn-lbx-outline-dark" href="<?php echo esc_url( lbx_contact_url( 'consulta' ) ); ?>"><?php esc_html_e( 'Consulta técnica', 'lubrax' ); ?></a>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $lbx_products ) : ?>
			<section class="search-results__group" aria-labelledby="searchProducts">
				<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
					<h2 class="mb-0" id="searchProducts" data-reveal="up"><?php esc_html_e( 'Productos', 'lubrax' ); ?> <span class="search-results__count">(<?php echo esc_html( (string) count( $lbx_products ) ); ?>)</span></h2>
					<?php if ( count( $lbx_products ) > $lbx_max_products ) : ?>
						<a class="link-arrow" href="<?php echo esc_url( add_query_arg( 'q', rawurlencode( $lbx_query ), lbx_page_url( 'productos' ) ) ); ?>" data-reveal="up"><?php esc_html_e( 'Ver todos en el catálogo', 'lubrax' ); ?> <?php echo lbx_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
				</div>
				<ul class="product-grid" data-reveal-stagger="0.06">
					<?php
					foreach ( array_slice( $lbx_products, 0, $lbx_max_products ) as $lbx_product ) {
						echo lbx_product_card( $lbx_product ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $lbx_posts ) : ?>
			<section class="search-results__group" aria-labelledby="searchPosts">
				<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
					<h2 class="mb-0" id="searchPosts" data-reveal="up"><?php esc_html_e( 'Artículos', 'lubrax' ); ?> <span class="search-results__count">(<?php echo esc_html( (string) count( $lbx_posts ) ); ?>)</span></h2>
					<?php if ( count( $lbx_posts ) > $lbx_max_posts ) : ?>
						<a class="link-arrow" href="<?php echo esc_url( add_query_arg( 'q', rawurlencode( $lbx_query ), lbx_page_url( 'blog' ) ) ); ?>" data-reveal="up"><?php esc_html_e( 'Ver todos en el blog', 'lubrax' ); ?> <?php echo lbx_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
				</div>
				<ul class="post-grid" data-reveal-stagger="0.06">
					<?php
					foreach ( array_slice( $lbx_posts, 0, $lbx_max_posts ) as $lbx_post ) {
						echo lbx_post_card( $lbx_post ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $lbx_pages ) : ?>
			<section class="search-results__group" aria-labelledby="searchPages">
				<h2 class="mb-4" id="searchPages" data-reveal="up"><?php esc_html_e( 'Páginas', 'lubrax' ); ?> <span class="search-results__count">(<?php echo esc_html( (string) count( $lbx_pages ) ); ?>)</span></h2>
				<ul class="search-results__pages">
					<?php foreach ( $lbx_pages as $lbx_page ) : ?>
						<li data-reveal="up">
							<a href="<?php echo esc_url( get_permalink( $lbx_page ) ); ?>"><?php echo esc_html( get_the_title( $lbx_page ) ); ?></a>
							<?php if ( has_excerpt( $lbx_page ) ) : ?>
								<p class="mb-0 text-body-secondary"><?php echo esc_html( get_the_excerpt( $lbx_page ) ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</div>
</section>
