<?php
/**
 * Blog: tarjeta de artículo (tpl-post-card), barra lateral (populares y etiquetas), tiempo de lectura y
 * datos estructurados (BlogPosting + BreadcrumbList).
 *
 * "Artículos populares": entradas marcadas con el campo "Artículo popular" (barra lateral de la entrada).
 * Si no hay ninguna, las más recientes.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * Categoría principal de una entrada (la primera que no sea "Sin categoría").
 */
function lbx_post_category( int $post_id ): ?WP_Term {
	$terms = get_the_category( $post_id );
	foreach ( $terms as $term ) {
		if ( (int) get_option( 'default_category' ) !== $term->term_id || 1 === count( $terms ) ) {
			return $term;
		}
	}
	return null;
}

/**
 * "14 oct 2026" (tarjetas) o "14 de octubre de 2026" (artículo).
 */
function lbx_post_date( int $post_id, bool $long = false ): string {
	// En minúscula, como la maqueta (la traducción es_CL escribe los meses con mayúscula inicial).
	return mb_strtolower( $long ? get_the_date( 'j \d\e F \d\e Y', $post_id ) : rtrim( get_the_date( 'j M Y', $post_id ), '.' ) );
}

/**
 * Minutos de lectura (200 palabras por minuto).
 */
function lbx_reading_time( int $post_id ): int {
	$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 0, 'ÁÉÍÓÚÜÑáéíóúüñ' );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Tarjeta del listado del blog (maqueta blog.html: tpl-post-card). blog.js lee los data-*.
 *
 * @param bool $hidden Oculta la tarjeta (filtro inicial de un archivo de categoría o etiqueta, sin JavaScript).
 */
function lbx_post_card( $post, bool $hidden = false ): string {
	$post     = get_post( $post );
	$id       = $post->ID;
	$category = lbx_post_category( $id );
	$tags     = wp_list_pluck( get_the_tags( $id ) ?: array(), 'slug' );
	$url      = get_permalink( $id );
	$excerpt  = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 22 );
	$image_id = (int) get_post_thumbnail_id( $id );

	ob_start();
	?>
	<li data-reveal="up"<?php echo $hidden ? ' hidden' : ''; ?>>
		<article class="post-card" data-post data-id="<?php echo esc_attr( $post->post_name ); ?>" data-title="<?php echo esc_attr( get_the_title( $id ) ); ?>" data-category="<?php echo esc_attr( $category ? $category->slug : '' ); ?>" data-category-name="<?php echo esc_attr( $category ? $category->name : '' ); ?>" data-tags="<?php echo esc_attr( implode( ' ', $tags ) ); ?>" data-date="<?php echo esc_attr( get_the_date( 'Y-m-d', $id ) ); ?>">
			<a href="<?php echo esc_url( $url ); ?>" class="post-media" tabindex="-1" aria-hidden="true">
				<?php if ( $image_id ) : ?>
					<?php
					echo wp_get_attachment_image(
						$image_id,
						'lbx-card',
						false,
						array(
							'alt'      => '',
							'class'    => '',
							'sizes'    => '(min-width: 1200px) 300px, (min-width: 576px) 45vw, 100vw',
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
					?>
				<?php else : ?>
					<img src="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero-768.webp' ) ); ?>" width="640" height="360" alt="" loading="lazy" decoding="async">
				<?php endif; ?>
			</a>
			<?php if ( $category ) : ?>
				<span class="post-cat"><?php echo esc_html( $category->name ); ?></span>
			<?php endif; ?>
			<div class="post-body">
				<h3 class="post-title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></h3>
				<p class="post-excerpt"><?php echo esc_html( wp_strip_all_tags( $excerpt ) ); ?></p>
				<div class="post-meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $id ) ); ?>"><?php echo lbx_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( lbx_post_date( $id ) ); ?></time>
					<span class="link-arrow" aria-hidden="true"><?php esc_html_e( 'Leer más', 'lubrax' ); ?> <?php echo lbx_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</div>
			</div>
		</article>
	</li>
	<?php
	return (string) ob_get_clean();
}

/**
 * Barra lateral del blog (maqueta: "Artículos populares" y "Etiquetas").
 */
function lbx_blog_sidebar(): string {
	$popular = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 4,
			'meta_key'       => 'popular', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	) ?: get_posts( array( 'post_type' => 'post', 'posts_per_page' => 4 ) );
	$tags    = get_tags( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 20 ) );

	ob_start();
	?>
	<aside class="col-lg-3" aria-label="<?php esc_attr_e( 'Contenido destacado', 'lubrax' ); ?>">
		<?php if ( $popular ) : ?>
			<div class="side-card" data-reveal="right">
				<h2 class="side-title"><?php esc_html_e( 'Artículos populares', 'lubrax' ); ?></h2>
				<ol class="popular">
					<?php foreach ( $popular as $i => $item ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $item ) ); ?>"><span class="pop-num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><span><b><?php echo esc_html( get_the_title( $item ) ); ?></b><time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $item ) ); ?>"><?php echo esc_html( lbx_post_date( $item->ID ) ); ?></time></span></a></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>
		<?php if ( $tags ) : ?>
			<div class="side-card" data-reveal="right" data-reveal-delay="0.1">
				<h2 class="side-title"><?php esc_html_e( 'Etiquetas', 'lubrax' ); ?></h2>
				<ul class="tag-cloud">
					<?php foreach ( $tags as $tag ) : ?>
						<li><a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" data-tag="<?php echo esc_attr( $tag->slug ); ?>">#<?php echo esc_html( $tag->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</aside>
	<?php
	return (string) ob_get_clean();
}

/**
 * JSON-LD de la entrada: BlogPosting y BreadcrumbList.
 */
add_action(
	'wp_head',
	function () {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$id       = get_queried_object_id();
		$category = lbx_post_category( $id );
		$image    = get_the_post_thumbnail_url( $id, 'full' );
		$url      = get_permalink( $id );

		$article = array_filter(
			array(
				'@type'            => 'BlogPosting',
				'@id'              => $url . '#article',
				'mainEntityOfPage' => $url,
				'headline'         => get_the_title( $id ),
				'description'      => wp_strip_all_tags( get_the_excerpt( $id ) ),
				'image'            => $image ?: null,
				'datePublished'    => get_the_date( DATE_W3C, $id ),
				'dateModified'     => get_the_modified_date( DATE_W3C, $id ),
				'author'           => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ) ),
				'publisher'        => array(
					'@type' => 'Organization',
					'name'  => 'Lubrax',
					'logo'  => array( '@type' => 'ImageObject', 'url' => lbx_asset( 'img/favicon/icon-512.png' ) ),
				),
				'articleSection'   => $category ? $category->name : null,
				'keywords'         => implode( ', ', wp_list_pluck( get_the_tags( $id ) ?: array(), 'name' ) ) ?: null,
				'wordCount'        => str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ) ),
				'inLanguage'       => 'es-CL',
			)
		);

		$crumbs = array(
			array( __( 'Inicio', 'lubrax' ), home_url( '/' ) ),
			array( get_the_title( (int) get_option( 'page_for_posts' ) ), lbx_page_url( 'blog' ) ),
		);
		if ( $category ) {
			$crumbs[] = array( $category->name, get_category_link( $category ) );
		}
		$crumbs[] = array( get_the_title( $id ), $url );

		$breadcrumb = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array_map(
				fn( $crumb, $i ) => array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[0], 'item' => $crumb[1] ),
				$crumbs,
				array_keys( $crumbs )
			),
		);

		lbx_print_schema( array( $article, $breadcrumb ) );
	},
	20
);
