<?php
/**
 * Bloque lubrax/doc-library: repositorio de documentos PDF de todos los productos.
 *
 * Componentes de la maqueta: filtros del localizador (.locator__filters), tarjetas de documento (tpl-doc-card),
 * visor PDF (#pdfModal) y paginación compartida (LBX.pagination). docs.js filtra en el navegador.
 * Parámetros de URL: ?q= · ?segmento={slug} · ?tipo=ficha-tecnica|hoja-seguridad|otro · ?producto={slug} · ?pagina=
 *
 * @package Lubrax
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lbx_product_documents' ) ) {
	return;
}

wp_enqueue_script( 'lbx-docs' );

$lbx_types = array(
	'ficha-tecnica'  => __( 'Ficha técnica', 'lubrax' ),
	'hoja-seguridad' => __( 'Hoja de seguridad', 'lubrax' ),
	'otro'           => __( 'Otros documentos', 'lubrax' ),
);

// Un elemento por documento, en el orden del catálogo.
$lbx_items = array();
foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $lbx_product ) {
	$lbx_docs = lbx_product_documents( $lbx_product->get_id() );
	if ( ! $lbx_docs ) {
		continue;
	}
	$lbx_data   = lbx_product_data( $lbx_product );
	$lbx_search = implode( ' ', array_merge( array( $lbx_data['name'] ), $lbx_data['sae'], $lbx_data['specs'], $lbx_data['base'], wp_list_pluck( $lbx_data['segments'], 'name' ) ) );
	foreach ( $lbx_docs as $lbx_doc ) {
		$lbx_items[] = lbx_doc_card(
			$lbx_doc,
			$lbx_data['name'],
			array(
				'class'         => 'col-md-6 col-xl-4',
				'data-doc'      => '',
				'data-product'  => $lbx_data['id'],
				'data-segments' => implode( ' ', wp_list_pluck( $lbx_data['segments'], 'slug' ) ),
				'data-type'     => $lbx_doc['kind'],
				'data-search'   => $lbx_search . ' ' . $lbx_doc['type'],
			),
			$lbx_data['url']
		);
	}
}
?>
<section class="section on-light doc-library" id="documentos" aria-labelledby="docLibraryTitle">
	<div class="container-xxl">
		<h2 class="visually-hidden" id="docLibraryTitle"><?php esc_html_e( 'Buscador de documentos técnicos', 'lubrax' ); ?></h2>
		<form class="locator__filters" role="search" aria-label="<?php esc_attr_e( 'Filtrar documentos', 'lubrax' ); ?>" data-reveal="up" data-docs-filters>
			<div>
				<label class="form-label" for="docSearch"><?php esc_html_e( 'Buscar', 'lubrax' ); ?></label>
				<div class="catalog__search">
					<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<input class="form-control" type="search" id="docSearch" name="q" placeholder="<?php esc_attr_e( 'Producto, viscosidad o norma', 'lubrax' ); ?>" autocomplete="off" enterkeyhint="search">
				</div>
			</div>
			<div>
				<label class="form-label" for="docSegment"><?php esc_html_e( 'Segmento', 'lubrax' ); ?></label>
				<select class="form-select" id="docSegment" name="segmento">
					<option value=""><?php esc_html_e( 'Todos los segmentos', 'lubrax' ); ?></option>
					<?php foreach ( lbx_segments( true ) as $lbx_segment ) : ?>
						<option value="<?php echo esc_attr( $lbx_segment->slug ); ?>"><?php echo esc_html( $lbx_segment->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label class="form-label" for="docType"><?php esc_html_e( 'Tipo de documento', 'lubrax' ); ?></label>
				<select class="form-select" id="docType" name="tipo">
					<option value=""><?php esc_html_e( 'Todos los documentos', 'lubrax' ); ?></option>
					<?php foreach ( $lbx_types as $lbx_value => $lbx_label ) : ?>
						<option value="<?php echo esc_attr( $lbx_value ); ?>"><?php echo esc_html( $lbx_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<button type="reset" class="btn btn-lbx-outline-dark w-100" data-docs-reset><?php esc_html_e( 'Limpiar', 'lubrax' ); ?></button>
			</div>
		</form>

		<div class="locator__bar">
			<p class="catalog__count mb-0" data-docs-count aria-live="polite">
				<?php
				/* translators: %d: cantidad de documentos. */
				echo esc_html( sprintf( _n( '%d documento', '%d documentos', count( $lbx_items ), 'lubrax' ), count( $lbx_items ) ) );
				?>
			</p>
		</div>

		<ul class="row g-3 list-unstyled mb-0" id="docGrid" data-per-page="<?php echo esc_attr( (string) max( 1, (int) ( $attributes['perPage'] ?? 12 ) ) ); ?>">
			<?php echo implode( '', $lbx_items ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado en lbx_doc_card(). ?>
		</ul>

		<div class="catalog-empty mt-3" data-docs-empty<?php echo $lbx_items ? ' hidden' : ''; ?>>
			<h3 class="h5"><?php esc_html_e( 'No encontramos documentos con esos filtros', 'lubrax' ); ?></h3>
			<p><?php esc_html_e( 'Prueba con otro producto o quita algún filtro. Si necesitas un documento que no está aquí, escríbenos.', 'lubrax' ); ?></p>
			<a class="btn btn-lbx-dark btn-sm" href="<?php echo esc_url( lbx_contact_url( 'consulta' ) ); ?>"><?php esc_html_e( 'Solicitar documento', 'lubrax' ); ?></a>
		</div>

		<nav class="mt-5" aria-label="<?php esc_attr_e( 'Paginación de documentos', 'lubrax' ); ?>" data-docs-pagination></nav>
	</div>
</section>
