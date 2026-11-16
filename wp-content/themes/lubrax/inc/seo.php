<?php
/**
 * SEO técnico.
 *
 * Siempre:
 * - 404 real: sin la redirección de WordPress a una URL "parecida" (evita soft 404) y con noindex, follow.
 * - Sitemap de WordPress (/wp-sitemap.xml) con productos, entradas, páginas y taxonomías del catálogo;
 *   sin usuarios ni las categorías y etiquetas de WooCommerce (ocultas en el tema).
 * - Datos estructurados del tema (Product, BlogPosting, FAQPage, BreadcrumbList) con lbx_print_schema().
 *
 * Solo sin plugin SEO (Yoast SEO, Rank Math, AIOSEO o SEOPress), para no duplicar etiquetas:
 * - <meta name="description">, Open Graph y Twitter Cards (imagen por defecto: assets/img/og/og-lubrax.jpg).
 * - Canónica en listados (WordPress solo la imprime en contenido singular): sin los parámetros de filtros
 *   (?combustible=, ?q=…), que son estados del catálogo y del blog, no páginas distintas.
 * - JSON-LD Organization y WebSite en el inicio.
 * Con un plugin SEO activo, el plugin se encarga de todo eso, del sitemap y de las migas y el artículo en
 * Schema; el tema mantiene Product y FAQPage, que esos plugins no generan en su versión gratuita.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * ¿Hay un plugin SEO activo?
 */
function lbx_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * ¿El tema debe imprimir este tipo de Schema? Con plugin SEO, las migas y el artículo los genera el plugin.
 */
function lbx_schema_enabled( string $type ): bool {
	$enabled = ! ( lbx_seo_plugin_active() && in_array( $type, array( 'BreadcrumbList', 'BlogPosting', 'Organization', 'WebSite' ), true ) );
	return (bool) apply_filters( 'lbx_schema_enabled', $enabled, $type );
}

/**
 * Imprime un @graph JSON-LD con los nodos habilitados.
 */
function lbx_print_schema( array $nodes ): void {
	$nodes = array_values( array_filter( $nodes, fn( $node ) => ! empty( $node['@type'] ) && lbx_schema_enabled( $node['@type'] ) ) );
	if ( ! $nodes ) {
		return;
	}
	printf(
		"<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $nodes ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
	);
}

/* -------------------------------------------------------------------------
 * 404 y robots
 * ---------------------------------------------------------------------- */

// Una URL inexistente responde 404: WordPress no la redirige a la entrada de nombre parecido.
add_filter( 'do_redirect_guess_404_permalink', '__return_false' );

add_filter(
	'wp_robots',
	function ( array $robots ) {
		if ( is_404() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);

/* -------------------------------------------------------------------------
 * Sitemap (WordPress)
 * ---------------------------------------------------------------------- */

add_filter(
	'wp_sitemaps_add_provider',
	fn( $provider, string $name ) => 'users' === $name ? false : $provider,
	10,
	2
);

add_filter(
	'wp_sitemaps_taxonomies',
	function ( array $taxonomies ) {
		unset( $taxonomies['product_cat'], $taxonomies['product_tag'], $taxonomies['product_shipping_class'], $taxonomies['post_format'] );
		return $taxonomies;
	}
);

// Páginas de WooCommerce sin uso en modo catálogo (carrito, pago, mi cuenta), si existen.
add_filter(
	'wp_sitemaps_posts_query_args',
	function ( array $args, string $post_type ) {
		if ( 'page' === $post_type && function_exists( 'wc_get_page_id' ) ) {
			$exclude = array_filter( array_map( 'wc_get_page_id', array( 'cart', 'checkout', 'myaccount' ) ), fn( $id ) => $id > 0 );
			if ( $exclude ) {
				$args['post__not_in'] = array_merge( $args['post__not_in'] ?? array(), $exclude );
			}
		}
		return $args;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Metadatos (sin plugin SEO)
 * ---------------------------------------------------------------------- */

add_filter( 'document_title_separator', fn() => '|' );

add_filter(
	'document_title_parts',
	function ( array $parts ) {
		if ( is_search() ) {
			/* translators: %s: texto buscado. */
			$parts['title'] = sprintf( __( 'Resultados para «%s»', 'lubrax' ), get_search_query( false ) );
		}
		return $parts;
	}
);

/**
 * Texto plano de una descripción, en una línea y hasta 160 caracteres.
 */
function lbx_seo_clean( string $text ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $text ) ) ) ) );
	return mb_strlen( $text ) > 160 ? rtrim( mb_substr( $text, 0, 157 ), " ,.;:" ) . '…' : $text;
}

/**
 * Título, descripción, URL canónica, imagen y tipo de la página actual.
 *
 * @return array{title: string, description: string, url: string, image: int|string, type: string}
 */
function lbx_seo_context(): array {
	$context = array(
		'title'       => wp_get_document_title(),
		'description' => '',
		'url'         => '',
		'image'       => 0,
		'type'        => 'website',
	);

	if ( is_404() ) {
		$context['description'] = lbx_option( 'e404_description' ); // Opciones del tema > Página 404.
		return $context;
	}
	if ( is_search() ) {
		return $context;
	}

	$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
	$page_id = 0;

	if ( is_front_page() ) {
		$page_id                = (int) get_option( 'page_on_front' );
		$context['url']         = home_url( '/' );
		$context['description'] = get_bloginfo( 'description' );
	} elseif ( is_home() ) {
		$page_id        = (int) get_option( 'page_for_posts' );
		$context['url'] = $page_id ? get_permalink( $page_id ) : home_url( '/' );
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$page_id        = $shop_id;
		$context['url'] = get_permalink( $shop_id );
	} elseif ( is_singular() ) {
		$page_id        = get_queried_object_id();
		$context['url'] = wp_get_canonical_url( $page_id );
		if ( is_singular( 'post' ) ) {
			$context['type'] = 'article';
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term                   = get_queried_object();
		$context['url']         = get_term_link( $term );
		$context['description'] = $term->description ?: sprintf(
			/* translators: %s: nombre del término. */
			is_tax() ? __( 'Lubricantes Lubrax: %s. Fichas técnicas, presentaciones y distribuidores.', 'lubrax' ) : __( 'Artículos del blog de Lubrax: %s.', 'lubrax' ),
			$term->name
		);
	} elseif ( is_post_type_archive() ) {
		$context['url'] = get_post_type_archive_link( get_query_var( 'post_type' ) );
	}

	if ( $page_id ) {
		$post = get_post( $page_id );
		if ( $post && ! $context['description'] || ( $post && has_excerpt( $post ) ) ) {
			$context['description'] = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		}
		$context['image'] = (int) get_post_thumbnail_id( $page_id );
	}
	if ( is_wp_error( $context['url'] ) ) {
		$context['url'] = '';
	}
	if ( $context['url'] && is_paged() && ! is_singular() ) {
		$context['url'] = get_pagenum_link( (int) get_query_var( 'paged' ) );
	}

	$context['description'] = lbx_seo_clean( (string) ( $context['description'] ?: get_bloginfo( 'description' ) ) );

	return apply_filters( 'lbx_seo_context', $context );
}

add_action(
	'wp_head',
	function () {
		if ( lbx_seo_plugin_active() ) {
			return;
		}
		$seo = lbx_seo_context();

		if ( $seo['description'] ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $seo['description'] ) );
		}
		// WordPress imprime la canónica del contenido singular (rel_canonical); aquí la de los listados.
		if ( $seo['url'] && ! is_singular() ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $seo['url'] ) );
		}

		$image = $seo['image'] ? wp_get_attachment_image_src( $seo['image'], 'full' ) : false;
		$image = $image ? array( $image[0], $image[1], $image[2] ) : array( lbx_asset( 'img/og/og-lubrax.jpg' ), 1200, 630 );

		$og = array(
			'og:type'        => $seo['type'],
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:locale'      => str_replace( '-', '_', get_bloginfo( 'language' ) ),
			'og:title'       => $seo['title'],
			'og:description' => $seo['description'],
			'og:url'         => $seo['url'],
			'og:image'       => $image[0],
			'og:image:width' => (string) $image[1],
			'og:image:height' => (string) $image[2],
		);
		if ( 'article' === $seo['type'] ) {
			$og['article:published_time'] = get_the_date( DATE_W3C );
			$og['article:modified_time']  = get_the_modified_date( DATE_W3C );
		}
		foreach ( array_filter( $og ) as $property => $content ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	},
	2
);

/**
 * JSON-LD del sitio en el inicio: Organization (contacto y redes) y WebSite.
 */
add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		$home = home_url( '/' );

		lbx_print_schema(
			array(
				array_filter(
					array(
						'@type'        => 'Organization',
						'@id'          => $home . '#organization',
						'name'         => get_bloginfo( 'name' ),
						'url'          => $home,
						'logo'         => array( '@type' => 'ImageObject', 'url' => lbx_asset( 'img/favicon/icon-512.png' ), 'width' => 512, 'height' => 512 ),
						'contactPoint' => array(
							array_filter(
								array(
									'@type'             => 'ContactPoint',
									'contactType'       => 'customer service',
									'telephone'         => lbx_option( 'phone' ),
									'email'             => lbx_option( 'email' ),
									'areaServed'        => 'CL',
									'availableLanguage' => 'es',
								)
							),
						),
						'sameAs'       => array_values( wp_list_pluck( lbx_social_links(), 'url' ) ) ?: null,
					)
				),
				array(
					'@type'      => 'WebSite',
					'@id'        => $home . '#website',
					'url'        => $home,
					'name'       => get_bloginfo( 'name' ),
					'inLanguage' => str_replace( '_', '-', get_locale() ),
					'publisher'  => array( '@id' => $home . '#organization' ),
				),
			)
		);
	},
	20
);
