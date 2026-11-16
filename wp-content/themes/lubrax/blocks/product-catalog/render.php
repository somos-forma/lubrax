<?php
/**
 * Bloque lubrax/product-catalog. Marcado: productos.html de la maqueta (page-hero + section.catalog).
 *
 * Imprime todas las tarjetas; catalog.js filtra, ordena y pagina en el navegador. En el archivo de una
 * taxonomía (/segmento/automotriz/, /viscosidad/15w-40/…) el filtro inicial llega en data-* y las tarjetas
 * que no corresponden se imprimen con "hidden" (correcto también sin JavaScript).
 *
 * @package Lubrax
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lbx_product_card' ) ) {
	return;
}

wp_enqueue_script( 'lbx-catalog' );

// Taxonomía → clave del filtro en catalog.js.
$lbx_facets = array(
	'tipo_producto'  => 'type',
	'combustible'    => 'fuel',
	'base_aceite'    => 'base',
	'viscosidad'     => 'sae',
	'especificacion' => 'spec',
);

$lbx_term = is_tax( array_merge( array( 'segmento' ), array_keys( $lbx_facets ) ) ) ? get_queried_object() : null;
$lbx_shop = lbx_page_url( 'productos' );
$lbx_grid = array(
	'id'                    => 'productGrid',
	'class'                 => 'product-grid',
	'data-base-url'         => wp_parse_url( $lbx_shop, PHP_URL_PATH ),
	'data-reveal-stagger'   => '0.06',
);
if ( $lbx_term && 'segmento' === $lbx_term->taxonomy ) {
	$lbx_grid['data-cat'] = $lbx_term->slug;
} elseif ( $lbx_term ) {
	$lbx_grid['data-facet-key']   = $lbx_facets[ $lbx_term->taxonomy ];
	$lbx_grid['data-facet-value'] = $lbx_term->name;
}

$lbx_title    = $lbx_term ? $lbx_term->name : ( $attributes['title'] ?? '' );
$lbx_lead     = $lbx_term ? wp_strip_all_tags( term_description( $lbx_term ) ) : '';
$lbx_heading  = $lbx_term ? $lbx_term->name : __( 'Todo el catálogo', 'lubrax' );
$lbx_products = wc_get_products(
	array(
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => -1,
		'orderby'    => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	)
);
$lbx_segments = lbx_segments();

// Banner de la columna de filtros: Opciones del tema > Banner del catálogo (vacío: contenido de la maqueta).
$lbx_promo = null;
if ( '1' === lbx_option( 'promo_show' ) ) {
	$lbx_promo_url = get_term_link( 'automotriz', 'segmento' );
	$lbx_promo     = array(
		'link'  => lbx_link_value( lbx_option_field( 'promo_link' ) ) ?? array( 'url' => is_wp_error( $lbx_promo_url ) ? $lbx_shop : $lbx_promo_url, 'title' => '', 'target' => '' ),
		'bg'    => sanitize_hex_color( lbx_option( 'promo_bg' ) ) ?: '#032b1a',
		'dark'  => 'oscuro' === lbx_option( 'promo_tone' ),
		'image' => lbx_image_or_asset(
			(int) lbx_option( 'promo_image' ),
			array( 'src' => 'hero/envase-4l-420.webp', 'width' => 420, 'height' => 522 ),
			array( 'alt' => '', 'sizes' => '(min-width: 992px) 200px, 60vw' ),
			'medium_large'
		),
	);
}
?>
<section class="page-hero" aria-labelledby="pageTitle">
	<div class="page-hero__bg">
		<img src="<?php echo esc_url( lbx_asset( 'img/pages/productos-hero.webp' ) ); ?>" srcset="<?php echo esc_url( lbx_asset( 'img/pages/productos-hero-768.webp' ) ); ?> 768w, <?php echo esc_url( lbx_asset( 'img/pages/productos-hero.webp' ) ); ?> 1600w" sizes="100vw" alt="" width="1600" height="964" fetchpriority="high" decoding="async">
	</div>
	<div class="container-xxl">
		<h1 class="page-hero__title t-display" id="pageTitle" data-reveal="title"><span class="line"><span><?php echo esc_html( $lbx_title ); ?></span></span></h1>
		<nav aria-label="<?php esc_attr_e( 'Ruta de navegación', 'lubrax' ); ?>" data-reveal="up" data-reveal-delay="0.15">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'lubrax' ); ?></a></li>
				<?php if ( $lbx_term ) : ?>
					<li class="breadcrumb-item"><a href="<?php echo esc_url( $lbx_shop ); ?>"><?php esc_html_e( 'Productos', 'lubrax' ); ?></a></li>
					<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $lbx_term->name ); ?></li>
				<?php else : ?>
					<li class="breadcrumb-item active" aria-current="page"><?php esc_html_e( 'Productos', 'lubrax' ); ?></li>
				<?php endif; ?>
			</ol>
		</nav>
		<?php if ( $lbx_lead ) : ?>
			<p class="page-hero__lead t-lead mb-0" data-reveal="up" data-reveal-delay="0.25"><?php echo esc_html( $lbx_lead ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="catalog on-light" aria-labelledby="catalogHeading">
	<div class="container-xxl">
		<div class="row g-4 g-xl-5">
			<div class="col-lg-3">
				<aside class="offcanvas-lg offcanvas-start filters" tabindex="-1" id="filters" aria-labelledby="filtersLabel">
					<div class="offcanvas-header">
						<h2 class="offcanvas-title h5" id="filtersLabel"><?php esc_html_e( 'Filtrar catálogo', 'lubrax' ); ?></h2>
						<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#filters" aria-label="<?php esc_attr_e( 'Cerrar filtros', 'lubrax' ); ?>"></button>
					</div>
					<div class="offcanvas-body flex-column">
						<h2 class="filters__title"><?php echo lbx_icon( 'layers' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Categorías', 'lubrax' ); ?></h2>
						<ul class="cat-list">
							<li><a href="<?php echo esc_url( $lbx_shop ); ?>" data-cat="" data-name="<?php esc_attr_e( 'Todo el catálogo', 'lubrax' ); ?>"><?php esc_html_e( 'Todo el catálogo', 'lubrax' ); ?> <span class="count"><?php echo esc_html( (string) count( $lbx_products ) ); ?></span></a></li>
							<?php foreach ( $lbx_segments as $lbx_segment ) : ?>
								<li><a href="<?php echo esc_url( get_term_link( $lbx_segment ) ); ?>" data-cat="<?php echo esc_attr( $lbx_segment->slug ); ?>" data-name="<?php echo esc_attr( $lbx_segment->name ); ?>"<?php echo $lbx_term && $lbx_term->term_id === $lbx_segment->term_id ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $lbx_segment->name ); ?> <span class="count"><?php echo esc_html( (string) $lbx_segment->count ); ?></span></a></li>
							<?php endforeach; ?>
						</ul>

						<h2 class="filters__title"><?php echo lbx_icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Filtros', 'lubrax' ); ?></h2>
						<fieldset class="filter-group" data-filter-group="fuel" hidden>
							<legend><?php esc_html_e( 'Combustible', 'lubrax' ); ?></legend>
							<div class="chip-group" data-facet="fuel"></div>
						</fieldset>
						<fieldset class="filter-group" data-filter-group="type">
							<legend><?php esc_html_e( 'Tipo de producto', 'lubrax' ); ?></legend>
							<div class="chip-group" data-facet="type"></div>
						</fieldset>
						<fieldset class="filter-group">
							<legend><?php esc_html_e( 'Tipo de base', 'lubrax' ); ?></legend>
							<div class="chip-group" data-facet="base"></div>
						</fieldset>
						<fieldset class="filter-group">
							<legend><?php esc_html_e( 'Viscosidad', 'lubrax' ); ?></legend>
							<div class="chip-group" data-facet="sae"></div>
						</fieldset>
						<fieldset class="filter-group">
							<legend><?php esc_html_e( 'Especificación', 'lubrax' ); ?></legend>
							<div class="chip-group" data-facet="spec"></div>
							<button type="button" class="filter-more" data-facet-more="spec" aria-expanded="false" hidden><?php esc_html_e( 'Ver más', 'lubrax' ); ?></button>
						</fieldset>

						<?php if ( $lbx_promo ) : ?>
							<?php $lbx_blank = '_blank' === $lbx_promo['link']['target']; ?>
							<a class="promo-card<?php echo $lbx_promo['dark'] ? ' promo-card--dark' : ''; ?>" href="<?php echo esc_url( $lbx_promo['link']['url'] ); ?>" style="--promo-bg: <?php echo esc_attr( $lbx_promo['bg'] ); ?>"<?php echo $lbx_blank ? ' target="_blank" rel="noopener"' : ''; ?>>
								<strong><?php echo esc_html( lbx_option( 'promo_title' ) ); ?></strong>
								<span><?php echo esc_html( lbx_option( 'promo_text' ) ); ?><?php echo $lbx_blank ? lbx_new_tab_label() : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<?php echo $lbx_promo['image']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						<?php endif; ?>
					</div>
				</aside>
			</div>

			<div class="col-lg-9">
				<div class="catalog__bar">
					<div>
						<h2 class="catalog__heading" id="catalogHeading" data-catalog-heading><?php echo esc_html( $lbx_heading ); ?></h2>
						<p class="catalog__count mb-0" data-catalog-count aria-live="polite"></p>
					</div>
					<button type="button" class="btn btn-lbx-outline-dark btn-sm d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#filters" aria-controls="filters"><?php echo lbx_icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Filtros', 'lubrax' ); ?></button>
				</div>

				<div class="dist-banner" data-dist-banner hidden>
					<span><?php echo lbx_icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Disponibilidad en', 'lubrax' ); ?> <strong data-dist-name><?php esc_html_e( 'Todos', 'lubrax' ); ?></strong></span>
					<div class="form-check form-switch mb-0">
						<input class="form-check-input" type="checkbox" role="switch" id="onlyMine" checked>
						<label class="form-check-label" for="onlyMine"><?php esc_html_e( 'Solo productos de este distribuidor', 'lubrax' ); ?></label>
					</div>
					<button type="button" class="link-arrow btn-reset ms-sm-auto" data-bs-toggle="modal" data-bs-target="#distributorModal"><?php esc_html_e( 'Cambiar', 'lubrax' ); ?></button>
				</div>

				<div class="catalog__tools">
					<div class="view-toggle btn-group" role="group" aria-label="<?php esc_attr_e( 'Vista del catálogo', 'lubrax' ); ?>">
						<button type="button" class="btn" data-view="grid" aria-pressed="true" aria-label="<?php esc_attr_e( 'Ver en cuadrícula', 'lubrax' ); ?>"><?php echo lbx_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
						<button type="button" class="btn" data-view="list" aria-pressed="false" aria-label="<?php esc_attr_e( 'Ver en lista', 'lubrax' ); ?>"><?php echo lbx_icon( 'list' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</div>
					<form class="catalog__search" role="search" action="<?php echo esc_url( $lbx_shop ); ?>" method="get">
						<label for="catalogSearch" class="visually-hidden"><?php esc_html_e( 'Buscar en el catálogo', 'lubrax' ); ?></label>
						<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<input class="form-control" type="search" id="catalogSearch" name="q" placeholder="<?php esc_attr_e( 'Nombre, viscosidad o norma', 'lubrax' ); ?>" autocomplete="off" enterkeyhint="search">
					</form>
					<div class="d-flex align-items-center gap-2 ms-xl-auto">
						<label for="catalogSort" class="form-label mb-0 text-nowrap"><?php esc_html_e( 'Ordenar por', 'lubrax' ); ?></label>
						<select class="form-select" id="catalogSort">
							<option value="destacados"><?php esc_html_e( 'Destacados', 'lubrax' ); ?></option>
							<option value="nombre-asc"><?php esc_html_e( 'Nombre (A-Z)', 'lubrax' ); ?></option>
							<option value="nombre-desc"><?php esc_html_e( 'Nombre (Z-A)', 'lubrax' ); ?></option>
							<option value="viscosidad"><?php esc_html_e( 'Viscosidad', 'lubrax' ); ?></option>
						</select>
					</div>
				</div>

				<div class="active-filters" data-active-filters></div>

				<ul <?php echo implode( ' ', array_map( fn( $k, $v ) => sprintf( '%s="%s"', esc_attr( $k ), esc_attr( (string) $v ) ), array_keys( $lbx_grid ), $lbx_grid ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php
					foreach ( $lbx_products as $lbx_product ) {
						$lbx_hidden = $lbx_term && ! has_term( $lbx_term->term_id, $lbx_term->taxonomy, $lbx_product->get_id() );
						echo lbx_product_card( $lbx_product, $lbx_hidden ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>

				<div class="catalog-empty" data-catalog-empty hidden>
					<h3 class="h5"><?php esc_html_e( 'No encontramos productos con esos filtros', 'lubrax' ); ?></h3>
					<p><?php esc_html_e( 'Prueba quitando algún filtro, busca otra palabra o cambia de distribuidor.', 'lubrax' ); ?></p>
					<button type="button" class="btn btn-lbx-dark btn-sm" data-catalog-reset><?php esc_html_e( 'Limpiar filtros', 'lubrax' ); ?></button>
				</div>

				<nav class="mt-5" aria-label="<?php esc_attr_e( 'Paginación del catálogo', 'lubrax' ); ?>" data-catalog-pagination></nav>
			</div>
		</div>
	</div>
</section>
