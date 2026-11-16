<?php
/**
 * Bloque lubrax/post-list. Marcado: blog.html (page-hero + section.blog-list). blog.js filtra, ordena y pagina.
 *
 * Imprime las entradas publicadas (hasta 300; con más conviene paginar en el servidor). En una categoría o
 * etiqueta, el filtro inicial va en data-cat / data-tag y las tarjetas que no corresponden llevan "hidden".
 *
 * @package Lubrax
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'lbx-blog' );

$lbx_blog_url = lbx_page_url( 'blog' );
$lbx_term     = ( is_category() || is_tag() ) ? get_queried_object() : null;
$lbx_title    = $lbx_term ? $lbx_term->name : get_the_title( (int) get_option( 'page_for_posts' ) );
$lbx_lead     = $lbx_term ? wp_strip_all_tags( term_description( $lbx_term ) ) : (string) ( $attributes['lead'] ?? '' );
$lbx_posts    = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 300,
	)
);

$lbx_grid = array(
	'class'               => 'post-grid',
	'id'                  => 'blogGrid',
	'data-base-url'       => wp_parse_url( $lbx_blog_url, PHP_URL_PATH ),
	'data-reveal-stagger' => '0.06',
);
if ( is_category() ) {
	$lbx_grid['data-cat'] = $lbx_term->slug;
} elseif ( is_tag() ) {
	$lbx_grid['data-tag'] = $lbx_term->slug;
}
?>
<section class="page-hero" aria-labelledby="pageTitle">
	<div class="page-hero__bg">
		<img src="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?>" srcset="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero-768.webp' ) ); ?> 768w, <?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?> 1600w" sizes="100vw" alt="" width="1600" height="893" decoding="async" fetchpriority="high">
	</div>
	<div class="container-xxl">
		<h1 class="page-hero__title t-display" id="pageTitle" data-reveal="title"><span class="line"><span><?php echo esc_html( $lbx_title ); ?></span></span></h1>
		<nav aria-label="<?php esc_attr_e( 'Ruta de navegación', 'lubrax' ); ?>" data-reveal="up" data-reveal-delay="0.15">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'lubrax' ); ?></a></li>
				<?php if ( $lbx_term ) : ?>
					<li class="breadcrumb-item"><a href="<?php echo esc_url( $lbx_blog_url ); ?>"><?php echo esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ); ?></a></li>
				<?php endif; ?>
				<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $lbx_title ); ?></li>
			</ol>
		</nav>
		<?php if ( $lbx_lead ) : ?>
			<p class="page-hero__lead t-lead mb-0" data-reveal="up" data-reveal-delay="0.25"><?php echo esc_html( $lbx_lead ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="section on-light blog-list" aria-labelledby="blogHeading">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<div class="col-lg-9">
				<div class="catalog__bar">
					<div>
						<h2 class="catalog__heading" id="blogHeading" data-blog-heading><?php echo esc_html( $lbx_term ? ( is_tag() ? '#' : '' ) . $lbx_term->name : __( 'Todos los artículos', 'lubrax' ) ); ?></h2>
						<p class="catalog__count mb-0" data-blog-count aria-live="polite"></p>
					</div>
				</div>

				<div class="catalog__tools">
					<form class="catalog__search" role="search" aria-label="<?php esc_attr_e( 'Buscar en el blog', 'lubrax' ); ?>" action="<?php echo esc_url( $lbx_blog_url ); ?>" method="get">
						<label for="blogSearch" class="visually-hidden"><?php esc_html_e( 'Buscar en el blog', 'lubrax' ); ?></label>
						<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<input class="form-control" type="search" id="blogSearch" name="q" placeholder="<?php esc_attr_e( 'Buscar artículos', 'lubrax' ); ?>" autocomplete="off" enterkeyhint="search">
					</form>
					<div class="d-flex align-items-center gap-2 ms-xl-auto">
						<label for="blogSort" class="form-label mb-0 text-nowrap"><?php esc_html_e( 'Ordenar por', 'lubrax' ); ?></label>
						<select class="form-select" id="blogSort">
							<option value="recientes"><?php esc_html_e( 'Más recientes', 'lubrax' ); ?></option>
							<option value="antiguos"><?php esc_html_e( 'Más antiguos', 'lubrax' ); ?></option>
							<option value="titulo"><?php esc_html_e( 'Título (A-Z)', 'lubrax' ); ?></option>
						</select>
					</div>
				</div>

				<fieldset class="blog-cats">
					<legend class="filters__title"><?php echo lbx_icon( 'layers' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Categorías', 'lubrax' ); ?></legend>
					<div class="chip-group" data-blog-categories></div>
				</fieldset>

				<div class="active-filters" data-active-filters></div>

				<ul <?php echo implode( ' ', array_map( fn( $k, $v ) => sprintf( '%s="%s"', esc_attr( $k ), esc_attr( (string) $v ) ), array_keys( $lbx_grid ), $lbx_grid ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php
					foreach ( $lbx_posts as $lbx_post ) {
						$lbx_hidden = $lbx_term && ! has_term( $lbx_term->term_id, $lbx_term->taxonomy, $lbx_post );
						echo lbx_post_card( $lbx_post, $lbx_hidden ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>

				<div class="catalog-empty" data-blog-empty<?php echo $lbx_posts ? ' hidden' : ''; ?>>
					<h3 class="h5"><?php esc_html_e( 'No encontramos artículos con esos filtros', 'lubrax' ); ?></h3>
					<p><?php esc_html_e( 'Prueba con otra palabra, categoría o etiqueta.', 'lubrax' ); ?></p>
					<button type="button" class="btn btn-lbx-dark btn-sm" data-blog-reset><?php esc_html_e( 'Limpiar filtros', 'lubrax' ); ?></button>
				</div>

				<nav class="mt-5" aria-label="<?php esc_attr_e( 'Paginación del blog', 'lubrax' ); ?>" data-blog-pagination></nav>
			</div>

			<?php echo lbx_blog_sidebar(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
</section>
