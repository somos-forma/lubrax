<?php
/**
 * Datos de distribuidores para el front: modal del header, localizador (Paso 4) y ficha de producto (Paso 3).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * Datos de un distribuidor con las claves que espera la maqueta (data-* de tpl-dist-card).
 */
function lbx_distributor_data( int $post_id ): array {
	$meta     = fn( string $key ) => (string) get_post_meta( $post_id, $key, true );
	$products = array_filter( array_map( 'intval', (array) get_post_meta( $post_id, 'productos', true ) ) );
	$city     = $meta( 'ciudad' );
	$label    = $meta( 'zona' ) ?: ( $city ? sprintf( /* translators: %s: ciudad. */ __( 'Zona %s', 'lubrax' ), $city ) : get_the_title( $post_id ) );

	return array(
		'post_id'  => $post_id,
		'id'       => get_post_field( 'post_name', $post_id ),
		'name'     => get_the_title( $post_id ),
		'label'    => $label,
		'region'   => $meta( 'region' ),
		'city'     => $city,
		'address'  => $meta( 'direccion' ),
		'phone'    => $meta( 'telefono' ),
		'email'    => $meta( 'email' ),
		'web'      => $meta( 'web' ),
		'hours'    => $meta( 'horario' ),
		'maps'     => $meta( 'link_maps' ),
		'lat'      => '' !== $meta( 'lat' ) ? (float) $meta( 'lat' ) : null,
		'lng'      => '' !== $meta( 'lng' ) ? (float) $meta( 'lng' ) : null,
		// "Mayorista y minorista" (como la maqueta).
		'purchase' => ucfirst( mb_strtolower( implode( ' y ', array_filter( (array) get_post_meta( $post_id, 'tipo_compra', true ) ) ) ) ),
		'packs'    => array_values( array_filter( (array) get_post_meta( $post_id, 'presentaciones', true ) ) ),
		'products' => array_values(
			array_filter(
				array_map(
					fn( $id ) => 'publish' === get_post_status( $id ) ? get_post_field( 'post_name', $id ) : '',
					$products
				)
			)
		),
	);
}

/**
 * Todos los distribuidores publicados, ordenados por región y nombre. Se cachea hasta que cambie
 * un distribuidor o un producto.
 *
 * @return array<int, array>
 */
function lbx_get_distributors(): array {
	$cached = get_transient( 'lbx_distributors' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$ids = get_posts(
		array(
			'post_type'      => 'distribuidor',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	$distributors = array_map( 'lbx_distributor_data', $ids );
	usort( $distributors, fn( $a, $b ) => strcmp( $a['region'] . $a['name'], $b['region'] . $b['name'] ) );

	set_transient( 'lbx_distributors', $distributors, DAY_IN_SECONDS );

	return $distributors;
}

function lbx_flush_distributors_cache(): void {
	delete_transient( 'lbx_distributors' );
}

add_action( 'save_post_distribuidor', 'lbx_flush_distributors_cache' );
add_action( 'save_post_product', 'lbx_flush_distributors_cache' );
add_action(
	'deleted_post',
	function ( $post_id, $post ) {
		if ( $post && in_array( $post->post_type, array( 'distribuidor', 'product' ), true ) ) {
			lbx_flush_distributors_cache();
		}
	},
	10,
	2
);
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( $new !== $old && in_array( $post->post_type, array( 'distribuidor', 'product' ), true ) ) {
			lbx_flush_distributors_cache();
		}
	},
	10,
	3
);

/**
 * Enlace "Ver ruta" (Google Maps): a las coordenadas o, si no hay, a la dirección.
 */
function lbx_distributor_route_url( array $d ): string {
	$destination = null !== $d['lat'] ? $d['lat'] . ',' . $d['lng'] : trim( $d['address'] . ', ' . $d['city'] . ', Chile', ', ' );
	return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $destination );
}

/**
 * Tarjeta del localizador (maqueta distribuidores.html: tpl-dist-card). locator.js lee los data-* y data-field.
 */
function lbx_distributor_card( array $d ): string {
	$tel   = $d['phone'] ? lbx_tel_href( $d['phone'] ) : '';
	$name  = $d['name'];
	// "Dirección · Región" (maqueta), sin repetir la región si la dirección ya la incluye.
	$in    = $d['region'] && str_contains( remove_accents( mb_strtolower( $d['address'] ) ), remove_accents( mb_strtolower( $d['region'] ) ) );
	$where = implode( ' · ', array_filter( array( $d['address'], $in ? '' : $d['region'] ) ) );

	ob_start();
	?>
	<li>
		<article class="dist-card" data-distributor data-id="<?php echo esc_attr( $d['id'] ); ?>" data-name="<?php echo esc_attr( $name ); ?>" data-label="<?php echo esc_attr( $d['label'] ); ?>" data-region="<?php echo esc_attr( $d['region'] ); ?>" data-city="<?php echo esc_attr( $d['city'] ); ?>" data-lat="<?php echo esc_attr( null === $d['lat'] ? '' : (string) $d['lat'] ); ?>" data-lng="<?php echo esc_attr( null === $d['lng'] ? '' : (string) $d['lng'] ); ?>" data-products="<?php echo esc_attr( implode( ' ', $d['products'] ) ); ?>">
			<div class="dist-card__head">
				<h3 class="dist-card__name"><button type="button" class="dist-card__title" data-action="profile" tabindex="-1"><?php echo esc_html( $name ); ?></button></h3>
				<span class="dist-card__distance" data-distance></span>
			</div>
			<div class="dist-card__chips">
				<?php if ( $d['purchase'] ) : ?>
					<span class="dist-chip dist-chip--type" data-field="purchase"><?php echo esc_html( $d['purchase'] ); ?></span>
				<?php endif; ?>
				<span class="dist-chip" data-field="label"><?php echo esc_html( $d['label'] ); ?></span>
				<span class="dist-chip dist-chip--mine" data-mine-chip hidden><?php esc_html_e( 'Tu distribuidor', 'lubrax' ); ?></span>
			</div>
			<ul class="dist-card__info">
				<?php if ( $where ) : ?>
					<li><?php echo lbx_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-field="address"><?php echo esc_html( $where ); ?></span></li>
				<?php endif; ?>
				<?php if ( $d['hours'] ) : ?>
					<li><?php echo lbx_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-field="hours"><?php echo esc_html( $d['hours'] ); ?></span></li>
				<?php endif; ?>
				<?php if ( $tel ) : ?>
					<li><?php echo lbx_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="<?php echo esc_attr( $tel ); ?>" data-field="phone"><?php echo esc_html( $d['phone'] ); ?></a></li>
				<?php endif; ?>
				<?php if ( $d['web'] ) : ?>
					<li><?php echo lbx_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="<?php echo esc_url( $d['web'] ); ?>" data-field="web" target="_blank" rel="noopener"><?php esc_html_e( 'Sitio web', 'lubrax' ); ?></a></li>
				<?php endif; ?>
				<?php if ( $d['packs'] ) : ?>
					<li><?php echo lbx_icon( 'package' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-field="packs"><?php echo esc_html( implode( ' · ', $d['packs'] ) ); ?></span></li>
				<?php endif; ?>
			</ul>
			<span class="dist-card__avail" data-avail-text></span>
			<?php /* translators: %s: distribuidor. */ ?>
			<ul class="dist-card__products" data-products-preview aria-label="<?php echo esc_attr( sprintf( __( 'Productos de %s', 'lubrax' ), $name ) ); ?>"></ul>
			<div class="dist-card__actions">
				<button type="button" class="btn btn-lbx-dark btn-sm" data-action="profile"><?php esc_html_e( 'Ver perfil', 'lubrax' ); ?><span class="visually-hidden"> <?php echo esc_html( sprintf( /* translators: %s: distribuidor. */ __( 'de %s', 'lubrax' ), $name ) ); ?></span></button>
				<button type="button" class="btn btn-lbx-outline-dark btn-sm" data-action="select"><span><?php esc_html_e( 'Elegir', 'lubrax' ); ?><span class="visually-hidden"> <?php echo esc_html( $name ); ?> </span> <?php esc_html_e( 'distribuidor', 'lubrax' ); ?></span></button>
				<div class="dist-card__icons">
					<?php if ( null !== $d['lat'] ) : ?>
						<?php /* translators: %s: distribuidor. */ ?>
						<button type="button" class="btn btn-icon btn-lbx-outline-dark" data-action="focus" data-bs-toggle="tooltip" data-bs-title="<?php esc_attr_e( 'Ver en el mapa', 'lubrax' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Ver %s en el mapa', 'lubrax' ), $name ) ); ?>"><?php echo lbx_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<?php endif; ?>
					<?php /* translators: %s: distribuidor. */ ?>
					<a class="btn btn-icon btn-lbx-outline-dark" href="<?php echo esc_url( lbx_distributor_route_url( $d ) ); ?>" data-field="route" target="_blank" rel="noopener" data-bs-toggle="tooltip" data-bs-title="<?php esc_attr_e( 'Ver ruta', 'lubrax' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Ver ruta a %s (abre en una pestaña nueva)', 'lubrax' ), $name ) ); ?>"><?php echo lbx_icon( 'navigation' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php if ( $tel ) : ?>
						<?php /* translators: 1: distribuidor, 2: teléfono. */ ?>
						<a class="btn btn-icon btn-lbx-outline-dark" href="<?php echo esc_attr( $tel ); ?>" data-bs-toggle="tooltip" data-bs-title="<?php esc_attr_e( 'Llamar', 'lubrax' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Llamar a %1$s: %2$s', 'lubrax' ), $name, $d['phone'] ) ); ?>"><?php echo lbx_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
					<?php if ( $d['web'] ) : ?>
						<?php /* translators: %s: distribuidor. */ ?>
						<a class="btn btn-icon btn-lbx-outline-dark" href="<?php echo esc_url( $d['web'] ); ?>" data-field="web" target="_blank" rel="noopener" data-bs-toggle="tooltip" data-bs-title="<?php esc_attr_e( 'Ir al sitio web', 'lubrax' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Sitio web de %s (abre en una pestaña nueva)', 'lubrax' ), $name ) ); ?>"><?php echo lbx_icon( 'globe' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
				</div>
			</div>
		</article>
	</li>
	<?php
	return (string) ob_get_clean();
}

/**
 * Opciones del modal "Elige tu distribuidor" (header). Ver inc/globals.php.
 */
add_filter(
	'lbx_distributor_options_html',
	function () {
		$html = '';
		foreach ( lbx_get_distributors() as $d ) {
			$html .= sprintf(
				'<option value="%1$s" data-label="%2$s" data-region="%3$s" data-lat="%4$s" data-lng="%5$s">%6$s</option>',
				esc_attr( $d['id'] ),
				esc_attr( $d['label'] ),
				esc_attr( $d['region'] ),
				esc_attr( null === $d['lat'] ? '' : (string) $d['lat'] ),
				esc_attr( null === $d['lng'] ? '' : (string) $d['lng'] ),
				esc_html( $d['name'] )
			);
		}
		return $html;
	}
);

/**
 * Coordenadas desde un enlace de Google Maps. Los enlaces cortos (maps.app.goo.gl) se resuelven
 * siguiendo la redirección.
 *
 * @return array{lat: float, lng: float}|null
 */
function lbx_maps_coords( string $url ): ?array {
	$url = trim( $url );
	if ( '' === $url ) {
		return null;
	}

	if ( preg_match( '#^https?://(maps\.app\.goo\.gl|goo\.gl/maps)/#i', $url ) ) {
		$response = wp_safe_remote_head( $url, array( 'redirection' => 0, 'timeout' => 8 ) );
		$location = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_header( $response, 'location' );
		if ( $location ) {
			$url = $location;
		}
	}

	$url      = rawurldecode( $url );
	$patterns = array(
		'/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/',            // Marcador exacto del lugar.
		'/@(-?\d+\.\d+),(-?\d+\.\d+)/',                // Centro del mapa.
		'/[?&](?:q|query|ll|destination)=(-?\d+\.\d+),\s*(-?\d+\.\d+)/',
	);
	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $url, $m ) ) {
			$lat = (float) $m[1];
			$lng = (float) $m[2];
			if ( abs( $lat ) <= 90 && abs( $lng ) <= 180 ) {
				return array( 'lat' => $lat, 'lng' => $lng );
			}
		}
	}

	return null;
}

/**
 * Al guardar un distribuidor: si faltan coordenadas y hay enlace de Google Maps, se obtienen de él.
 */
add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( ! is_numeric( $post_id ) || 'distribuidor' !== get_post_type( (int) $post_id ) ) {
			return;
		}
		if ( '' !== (string) get_field( lbx_field_key( 'lat', $post_id ), $post_id, false ) && '' !== (string) get_field( lbx_field_key( 'lng', $post_id ), $post_id, false ) ) {
			return;
		}
		$coords = lbx_maps_coords( (string) get_field( lbx_field_key( 'link_maps', $post_id ), $post_id, false ) );
		if ( $coords ) {
			lbx_update_field( 'lat', $coords['lat'], $post_id );
			lbx_update_field( 'lng', $coords['lng'], $post_id );
		}
	},
	20
);

/* ------------------------------------------------------------------
 * Regiones y comunas de Chile (inc/data/comunas-chile.php)
 * ------------------------------------------------------------------ */

/**
 * Comunas por región: [ región => [ comuna, … ] ].
 */
function lbx_comunas(): array {
	static $data = null;
	if ( null === $data ) {
		$data = require LBX_DIR . '/inc/data/comunas-chile.php';
	}
	return $data;
}

/**
 * Busca una comuna escrita de cualquier forma ("concepcion", "Til Til", "Puerto Natales").
 *
 * @return array{comuna: string, region: string}|null Nombre oficial de la comuna y su región.
 */
function lbx_comuna_match( string $text ): ?array {
	static $index = null;
	$key          = fn( string $value ) => sanitize_title( remove_accents( str_replace( array( "'", '’' ), '', $value ) ) );

	if ( null === $index ) {
		$index = array();
		foreach ( lbx_comunas() as $region => $comunas ) {
			foreach ( $comunas as $comuna ) {
				$index[ $key( $comuna ) ] = array( 'comuna' => $comuna, 'region' => $region );
			}
		}
		// Nombres alternativos de uso común → nombre oficial.
		$aliases = array(
			'calera'                     => 'La Calera',
			'llay-llay'                  => 'Llaillay',
			'til-til'                    => 'Tiltil',
			'coihaique'                  => 'Coyhaique',
			'puerto-aysen'               => 'Aysén',
			'marchihue'                  => 'Marchigüe',
			'chol-chol'                  => 'Cholchol',
			'puerto-natales'             => 'Natales',
			'san-francisco-de-mostazal'  => 'Mostazal',
			'rapa-nui'                   => 'Isla de Pascua',
			'puerto-williams'            => 'Cabo de Hornos',
			'santiago-centro'            => 'Santiago',
			'san-jose-de-la-mariquina'   => 'Mariquina',
		);
		foreach ( $aliases as $alias => $comuna ) {
			$index[ $alias ] = $index[ $key( $comuna ) ];
		}
	}

	return $index[ $key( $text ) ] ?? null;
}

/**
 * Campo "Comuna" del distribuidor: opciones agrupadas por región (<optgroup>), desde el archivo de datos.
 * En el editor de grupos de campos no se cargan, así el JSON del grupo no guarda las 346 comunas.
 */
add_filter(
	'acf/load_field/key=field_lbx_dist_ciudad',
	function ( array $field ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'acf-field-group' === $screen->post_type ) {
			return $field;
		}
		$field['choices'] = array();
		foreach ( lbx_comunas() as $region => $comunas ) {
			$field['choices'][ $region ] = array_combine( $comunas, $comunas );
		}
		return $field;
	}
);

/**
 * La comuna debe pertenecer a la región elegida.
 */
add_filter(
	'acf/validate_value/key=field_lbx_dist_ciudad',
	function ( $valid, $value ) {
		if ( true !== $valid || '' === (string) $value ) {
			return $valid;
		}
		$match  = lbx_comuna_match( (string) $value );
		$region = sanitize_text_field( wp_unslash( $_POST['acf']['field_lbx_dist_region'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification -- SCF verifica el nonce.
		if ( ! $match ) {
			return __( 'Elige una comuna de la lista.', 'lubrax' );
		}
		if ( $region && $match['region'] !== $region ) {
			/* translators: 1: comuna, 2: región. */
			return sprintf( __( '%1$s pertenece a la región %2$s.', 'lubrax' ), $match['comuna'], $match['region'] );
		}
		return $valid;
	},
	10,
	2
);

/**
 * Editor del distribuidor: la lista de comunas se filtra según la región (select dependiente).
 */
add_action(
	'acf/input/admin_enqueue_scripts',
	function () {
		$screen = get_current_screen();
		if ( $screen && 'distribuidor' === $screen->post_type ) {
			wp_enqueue_script( 'lbx-admin-comunas', lbx_asset( 'js/editor/comunas.js' ), array( 'acf-input' ), lbx_asset_version( 'js/editor/comunas.js' ), true );
		}
	}
);
