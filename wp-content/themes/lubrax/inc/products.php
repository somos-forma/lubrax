<?php
/**
 * Datos de producto para el front: tarjeta del catálogo (tpl-product-card), ficha (tpl-product-page),
 * documentos PDF (tpl-doc-card) y datos estructurados (Schema Product + BreadcrumbList).
 *
 * El "id" de un producto en el marcado es su slug (igual que en la maqueta y en data-products de distribuidores).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

add_action(
	'after_setup_theme',
	function () {
		// Imagen de producto (catálogo y ficha): 600 × 600 como la maqueta, sin recorte.
		add_image_size( 'lbx-product', 600, 600, false );
	}
);

/**
 * Nombres de los términos de una taxonomía (orden alfabético natural).
 */
function lbx_term_names( int $post_id, string $taxonomy ): array {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}
	$names = wp_list_pluck( $terms, 'name' );
	natcasesort( $names );
	return array_values( $names );
}

/**
 * Segmentos ordenados por el campo "orden" (y luego por nombre).
 *
 * @return WP_Term[]
 */
function lbx_segments( bool $hide_empty = false ): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'segmento',
			'hide_empty' => $hide_empty,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		fn( $a, $b ) => ( (int) get_term_meta( $a->term_id, 'orden', true ) <=> (int) get_term_meta( $b->term_id, 'orden', true ) ) ?: strcasecmp( $a->name, $b->name )
	);
	return $terms;
}

/**
 * Datos de un producto con las claves de las plantillas de la maqueta.
 */
function lbx_product_data( $product ): array {
	$product  = wc_get_product( $product );
	$id       = $product->get_id();
	$segments = get_the_terms( $id, 'segmento' );
	$segments = $segments && ! is_wp_error( $segments ) ? array_values( $segments ) : array();
	$primary  = $segments[0] ?? null;
	$fuel     = lbx_term_names( $id, 'combustible' );
	$excerpt  = trim( wp_strip_all_tags( $product->get_short_description() ) );
	$short    = $excerpt ?: wp_trim_words( wp_strip_all_tags( $product->get_description() ), 20 );

	$distributors = array_values(
		array_filter(
			array_map(
				fn( $did ) => 'publish' === get_post_status( $did ) ? get_post_field( 'post_name', $did ) : '',
				array_filter( (array) lbx_get_field( 'distribuidores', $id, false ) )
			)
		)
	);

	return array(
		'post_id'       => $id,
		'id'            => $product->get_slug(),
		'name'          => $product->get_name(),
		'url'           => $product->get_permalink(),
		'segments'      => $segments,
		'category'      => $primary ? $primary->slug : '',
		'category_name' => $primary ? $primary->name : '',
		'category_url'  => $primary ? get_term_link( $primary ) : lbx_page_url( 'productos' ),
		'types'         => lbx_term_names( $id, 'tipo_producto' ),
		'fuel'          => $fuel,
		'base'          => lbx_term_names( $id, 'base_aceite' ),
		'sae'           => lbx_term_names( $id, 'viscosidad' ),
		'specs'         => lbx_term_names( $id, 'especificacion' ),
		'featured'      => $product->is_featured(),
		'short'         => wp_strip_all_tags( $short ),
		'has_excerpt'   => '' !== $excerpt, // Descripción corta propia (no recortada de la descripción).
		'description'   => $product->get_description(),
		'image_id'      => (int) $product->get_image_id(),
		'gallery_ids'   => array_map( 'intval', $product->get_gallery_image_ids() ),
		'distributors'  => $distributors,
		'tag_label'     => trim( ( $primary ? $primary->name : '' ) . ( $fuel ? ' · ' . implode( ' / ', $fuel ) : '' ) ),
	);
}

/**
 * <optgroup> por segmento con un <option value="{slug}" data-url="{ficha}"> por producto, en el orden del catálogo.
 * Lo usan el localizador (#locProduct) y el formulario de contacto.
 */
function lbx_product_options_html( string $selected = '' ): string {
	$products = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => -1,
			'orderby' => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);

	$groups = array();
	foreach ( lbx_segments() as $segment ) {
		$groups[ $segment->term_id ] = array( 'label' => $segment->name, 'items' => array() );
	}
	$groups[0] = array( 'label' => __( 'Otros productos', 'lubrax' ), 'items' => array() );

	foreach ( $products as $product ) {
		$terms   = wp_get_post_terms( $product->get_id(), 'segmento', array( 'fields' => 'ids' ) );
		$primary = ! is_wp_error( $terms ) && $terms ? (int) $terms[0] : 0;
		$groups[ isset( $groups[ $primary ] ) ? $primary : 0 ]['items'][] = $product;
	}

	$html = '';
	foreach ( $groups as $group ) {
		if ( ! $group['items'] ) {
			continue;
		}
		$html .= '<optgroup label="' . esc_attr( $group['label'] ) . '">';
		foreach ( $group['items'] as $product ) {
			$html .= sprintf(
				'<option value="%1$s" data-url="%2$s"%3$s>%4$s</option>',
				esc_attr( $product->get_slug() ),
				esc_url( $product->get_permalink() ),
				selected( $selected, $product->get_slug(), false ),
				esc_html( $product->get_name() )
			);
		}
		$html .= '</optgroup>';
	}

	return $html;
}

/**
 * URL del localizador filtrado por producto: /distribuidores/?producto={slug}.
 */
function lbx_where_to_buy_url( string $slug, string $region = '' ): string {
	return add_query_arg( array_filter( array( 'producto' => $slug, 'region' => $region ) ), lbx_page_url( 'distribuidores' ) );
}

/**
 * <img> de producto. Sin imagen: placeholder de WooCommerce.
 */
function lbx_product_image( int $image_id, string $alt, array $attr = array() ): string {
	$attr = array_merge(
		array(
			'alt'      => $alt,
			'class'    => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$attr
	);

	if ( $image_id && wp_attachment_is_image( $image_id ) ) {
		return wp_get_attachment_image( $image_id, 'lbx-product', false, $attr );
	}

	$html = sprintf( '<img src="%s" width="600" height="600"', esc_url( wc_placeholder_img_src( 'lbx-product' ) ) );
	foreach ( $attr as $name => $value ) {
		if ( false !== $value && '' !== $value || 'alt' === $name ) {
			$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}
	}
	return $html . '>';
}

/**
 * Tarjeta del catálogo (maqueta: tpl-product-card).
 *
 * @param bool $hidden Oculta la tarjeta (filtro inicial de un archivo de taxonomía, sin JavaScript).
 */
function lbx_product_card( $product, bool $hidden = false ): string {
	$p      = lbx_product_data( $product );
	$badges = '';
	foreach ( $p['specs'] as $spec ) {
		$badges .= '<span class="badge-spec">' . esc_html( $spec ) . '</span>';
	}

	ob_start();
	?>
	<li data-reveal="up"<?php echo $hidden ? ' hidden' : ''; ?>>
		<article class="product-card" data-product data-id="<?php echo esc_attr( $p['id'] ); ?>" data-name="<?php echo esc_attr( $p['name'] ); ?>" data-category="<?php echo esc_attr( implode( ' ', wp_list_pluck( $p['segments'], 'slug' ) ) ); ?>" data-category-name="<?php echo esc_attr( $p['category_name'] ); ?>" data-type="<?php echo esc_attr( implode( '|', $p['types'] ) ); ?>" data-fuel="<?php echo esc_attr( implode( '|', $p['fuel'] ) ); ?>" data-base="<?php echo esc_attr( implode( '|', $p['base'] ) ); ?>" data-sae="<?php echo esc_attr( implode( '|', $p['sae'] ) ); ?>" data-specs="<?php echo esc_attr( implode( '|', $p['specs'] ) ); ?>" data-distributors="<?php echo esc_attr( implode( ' ', $p['distributors'] ) ); ?>" data-featured="<?php echo $p['featured'] ? '1' : '0'; ?>">
			<div class="product-card__media">
				<?php echo lbx_product_image( $p['image_id'], $p['name'], array( 'sizes' => '(min-width: 1400px) 300px, (min-width: 576px) 45vw, 90vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( $p['tag_label'] ) : ?>
					<span class="product-card__tag"><?php echo esc_html( $p['tag_label'] ); ?></span>
				<?php endif; ?>
				<span class="product-card__avail" data-avail-label><?php esc_html_e( 'Disponible', 'lubrax' ); ?></span>
			</div>
			<div class="product-card__body">
				<h3 class="product-card__title"><a href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['name'] ); ?></a></h3>
				<div class="product-card__badges"><?php foreach ( $p['sae'] as $sae ) : ?><span class="badge-sae"><?php echo esc_html( $sae ); ?></span><?php endforeach; ?><?php echo $badges; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<p class="product-card__text"><?php echo esc_html( $p['short'] ); ?></p>
				<div class="product-card__actions">
					<a class="btn btn-lbx-dark btn-sm" href="<?php echo esc_url( $p['url'] ); ?>" tabindex="-1" aria-hidden="true"><?php esc_html_e( 'Ver más', 'lubrax' ); ?></a>
					<a class="btn btn-lbx-outline-dark btn-sm" href="<?php echo esc_url( lbx_where_to_buy_url( $p['id'] ) ); ?>"><?php esc_html_e( 'Dónde comprar', 'lubrax' ); ?><span class="visually-hidden"> <?php echo esc_html( $p['name'] ); ?></span></a>
				</div>
			</div>
		</article>
	</li>
	<?php
	return (string) ob_get_clean();
}

/**
 * Documentos PDF de un producto (maqueta: tpl-doc-card).
 *
 * @return array<int, array{type: string, url: string, size: string, updated: string, thumb: string}>
 */
function lbx_product_documents( int $post_id ): array {
	$labels = array(
		'ficha-tecnica'  => __( 'Ficha técnica', 'lubrax' ),
		'hoja-seguridad' => __( 'Hoja de seguridad', 'lubrax' ),
		'otro'           => __( 'Documento', 'lubrax' ),
	);
	$docs   = array();

	foreach ( (array) lbx_get_field( 'documentos', $post_id ) as $row ) {
		$file_id = (int) ( $row['archivo'] ?? 0 );
		$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
		if ( ! $url ) {
			continue;
		}

		$path = get_attached_file( $file_id );
		// La fecha del campo se interpreta en la zona horaria del sitio (con strtotime sería UTC: un día antes en Chile).
		$date    = ! empty( $row['actualizado'] ) ? date_create_immutable( $row['actualizado'], wp_timezone() ) : false;
		$updated = $date ? $date->getTimestamp() : get_post_timestamp( $file_id, 'modified' );

		// Miniatura: vista previa que genera WordPress para PDF (requiere Imagick) o ícono genérico.
		$preview = wp_get_attachment_image_url( $file_id, 'medium' );

		$docs[] = array(
			'kind'    => $row['tipo'] ?? 'otro',
			'type'    => $row['titulo'] ?: ( $labels[ $row['tipo'] ?? 'otro' ] ?? $labels['otro'] ),
			'url'     => $url,
			'size'    => $path && file_exists( $path ) ? size_format( filesize( $path ), 0 ) : '',
			'updated' => $updated ? ucfirst( wp_date( 'F Y', $updated ) ) : '',
			'thumb'   => $preview ?: lbx_asset( 'img/docs/pdf.svg' ),
		);
	}

	return $docs;
}

/**
 * Tarjeta de documento con visor PDF (maqueta: tpl-doc-card). Activa el modal #pdfModal en el footer.
 *
 * @param array  $li_attr     Atributos extra del <li> (el repositorio agrega clases de grilla y data-* de filtro).
 * @param string $product_url Si se indica, muestra el producto con enlace a su ficha (repositorio de documentos).
 */
function lbx_doc_card( array $doc, string $product_name, array $li_attr = array(), string $product_url = '' ): string {
	lbx_needs_pdf_modal();
	$meta  = trim( implode( ' · ', array_filter( array( 'PDF', $doc['size'], $doc['updated'] ? sprintf( /* translators: %s: mes y año. */ __( 'Actualizado %s', 'lubrax' ), $doc['updated'] ) : '' ) ) ) );
	$attrs = '';
	foreach ( array_merge( array( 'data-reveal' => 'up' ), $li_attr ) as $name => $value ) {
		$attrs .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
	}

	ob_start();
	?>
	<li<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- escapado arriba. ?>>
		<article class="doc-card">
			<div class="doc-card__thumb"><img src="<?php echo esc_url( $doc['thumb'] ); ?>" alt="" width="320" height="453" loading="lazy" decoding="async"></div>
			<div>
				<?php if ( $product_url ) : ?>
					<p class="doc-card__product"><a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_name ); ?></a></p>
				<?php endif; ?>
				<h3 class="doc-card__title"><?php echo esc_html( $doc['type'] ); ?></h3>
				<p class="doc-card__meta"><?php echo esc_html( $meta ); ?></p>
				<div class="doc-card__actions">
					<a class="btn btn-lbx-dark btn-sm" href="<?php echo esc_url( $doc['url'] ); ?>" target="_blank" rel="noopener" data-pdf-view data-pdf-product="<?php echo esc_attr( $product_name ); ?>" data-pdf-title="<?php echo esc_attr( $doc['type'] ); ?>" data-pdf-meta="<?php echo esc_attr( $meta ); ?>" data-pdf-thumb="<?php echo esc_url( $doc['thumb'] ); ?>"><?php echo lbx_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Ver', 'lubrax' ); ?><span class="visually-hidden"> <?php echo esc_html( $doc['type'] ); ?> (PDF)</span></a>
					<a class="btn btn-lbx-outline-dark btn-sm" href="<?php echo esc_url( $doc['url'] ); ?>" download><?php echo lbx_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Descargar', 'lubrax' ); ?><span class="visually-hidden"> <?php echo esc_html( $doc['type'] . ' (' . trim( 'PDF, ' . $doc['size'], ', ' ) . ')' ); ?></span></a>
				</div>
			</div>
		</article>
	</li>
	<?php
	return (string) ob_get_clean();
}

/**
 * Visor de PDF (#pdfModal, lo maneja app.js). Se imprime una vez, solo en páginas con documentos.
 */
function lbx_needs_pdf_modal(): void {
	static $added = false;
	if ( $added ) {
		return;
	}
	$added = true;

	add_action(
		'wp_footer',
		function () {
			?>
			<div class="modal fade modal-dark pdf-modal" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalTitle" aria-hidden="true">
				<div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
					<div class="modal-content">
						<div class="modal-header border-0">
							<div class="pdf-modal__head">
								<p class="t-kicker mb-1" data-pdf-product></p>
								<h2 class="modal-title h4" id="pdfModalTitle" data-pdf-title><?php esc_html_e( 'Documento', 'lubrax' ); ?></h2>
								<p class="small mb-0" data-pdf-meta></p>
							</div>
							<div class="pdf-modal__actions">
								<a class="btn btn-lbx btn-sm" href="#" data-pdf-download download><?php echo lbx_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Descargar', 'lubrax' ); ?></a>
								<a class="btn btn-icon btn-lbx-outline" href="#" data-pdf-open target="_blank" rel="noopener" data-bs-toggle="tooltip" data-bs-title="<?php esc_attr_e( 'Abrir en una pestaña nueva', 'lubrax' ); ?>" aria-label="<?php esc_attr_e( 'Abrir el PDF en una pestaña nueva', 'lubrax' ); ?>"><?php echo lbx_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Cerrar', 'lubrax' ); ?>"></button>
							</div>
						</div>
						<div class="modal-body">
							<div class="pdf-viewer" data-pdf-viewer></div>
						</div>
					</div>
				</div>
			</div>
			<?php
		},
		6
	);
}

/**
 * Productos relacionados: los elegidos a mano (campo "relacionados") o, si no hay, del mismo segmento.
 *
 * @return WC_Product[]
 */
function lbx_related_products( int $post_id, int $limit = 4 ): array {
	$manual = array_filter( array_map( 'intval', (array) lbx_get_field( 'relacionados', $post_id, false ) ) );
	if ( $manual ) {
		return array_values( array_filter( array_map( 'wc_get_product', $manual ), fn( $p ) => $p && 'publish' === $p->get_status() ) );
	}

	$segments = wp_get_post_terms( $post_id, 'segmento', array( 'fields' => 'ids' ) );
	if ( ! $segments || is_wp_error( $segments ) ) {
		return array();
	}

	$products = wc_get_products(
		array(
			'status'    => 'publish',
			'limit'     => 12,
			'exclude'   => array( $post_id ),
			'orderby'   => 'title',
			'order'     => 'ASC',
			'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'segmento',
					'field'    => 'term_id',
					'terms'    => $segments,
				),
			),
		)
	);

	// Destacados primero (usort es estable: dentro de cada grupo se mantiene el orden por nombre).
	usort( $products, fn( $a, $b ) => (int) $b->is_featured() <=> (int) $a->is_featured() );

	return array_slice( $products, 0, $limit );
}

/* ------------------------------------------------------------------
 * Datos estructurados
 * ------------------------------------------------------------------ */

// El Schema de WooCommerce incluye ofertas y precios: en modo catálogo se reemplaza por el del tema.
add_filter( 'woocommerce_structured_data_product', '__return_empty_array' );

/**
 * JSON-LD de la ficha: Product (con propiedades técnicas) y BreadcrumbList.
 */
add_action(
	'wp_head',
	function () {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		$p = lbx_product_data( get_queried_object_id() );

		$properties = array();
		foreach ( array( 'Viscosidad' => $p['sae'], 'Tipo de base' => $p['base'], 'Especificaciones' => $p['specs'] ) as $name => $values ) {
			if ( $values ) {
				$properties[] = array( '@type' => 'PropertyValue', 'name' => $name, 'value' => implode( ', ', $values ) );
			}
		}
		foreach ( (array) lbx_get_field( 'propiedades', $p['post_id'] ) as $row ) {
			if ( ! empty( $row['propiedad'] ) ) {
				$properties[] = array( '@type' => 'PropertyValue', 'name' => $row['propiedad'], 'value' => (string) $row['valor'] );
			}
		}

		$product = array_filter(
			array(
				'@type'              => 'Product',
				'@id'                => $p['url'] . '#product',
				'name'               => $p['name'],
				'url'                => $p['url'],
				'sku'                => wc_get_product( $p['post_id'] )->get_sku() ?: $p['id'],
				'description'        => wp_strip_all_tags( $p['description'] ?: $p['short'] ),
				'category'           => $p['category_name'],
				'image'              => $p['image_id'] ? wp_get_attachment_image_url( $p['image_id'], 'full' ) : null,
				'brand'              => array( '@type' => 'Brand', 'name' => 'Lubrax' ),
				'additionalProperty' => $properties,
			)
		);

		$crumbs = array(
			array( __( 'Inicio', 'lubrax' ), home_url( '/' ) ),
			array( __( 'Productos', 'lubrax' ), lbx_page_url( 'productos' ) ),
		);
		if ( $p['category_name'] ) {
			$crumbs[] = array( $p['category_name'], $p['category_url'] );
		}
		$crumbs[] = array( $p['name'], $p['url'] );

		$breadcrumb = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array_map(
				fn( $crumb, $i ) => array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[0], 'item' => $crumb[1] ),
				$crumbs,
				array_keys( $crumbs )
			),
		);

		lbx_print_schema( array( $product, $breadcrumb ) );
	},
	20
);

/**
 * Productos que coinciden con una búsqueda: por nombre o descripción (búsqueda de WordPress) y por los términos
 * de sus taxonomías (viscosidad "15W-40", norma "API CK-4", segmento…), como el buscador del catálogo.
 *
 * @return int[] IDs ordenados por menu_order y título.
 */
function lbx_search_product_ids( string $search ): array {
	$search = trim( $search );
	if ( '' === $search ) {
		return array();
	}

	$by_text = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 's' => $search, 'fields' => 'ids', 'posts_per_page' => -1 ) );

	$terms   = get_terms( array( 'taxonomy' => array_keys( lbx_product_taxonomies() ), 'search' => $search, 'fields' => 'ids', 'hide_empty' => true ) );
	$by_term = array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		$tax_query = array( 'relation' => 'OR' );
		foreach ( array_keys( lbx_product_taxonomies() ) as $taxonomy ) {
			$tax_query[] = array( 'taxonomy' => $taxonomy, 'terms' => $terms );
		}
		$by_term = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'tax_query' => $tax_query, 'fields' => 'ids', 'posts_per_page' => -1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}

	$ids = array_unique( array_merge( $by_text, $by_term ) );
	if ( ! $ids ) {
		return array();
	}

	return get_posts( array( 'post_type' => 'product', 'post__in' => $ids, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'fields' => 'ids', 'posts_per_page' => -1 ) );
}
