<?php
/**
 * Importador de la plantilla "PLANTILLAS CONTENIDO WEB.xlsx" (hojas Productos y Distribuidores).
 *
 * - Las columnas se reconocen por su encabezado (el orden no importa; las columnas desconocidas se ignoran).
 * - Es idempotente: un producto se busca por SKU (si hay columna SKU) o por nombre; un distribuidor por nombre.
 *   Volver a importar actualiza, no duplica. Una celda vacía no borra lo que ya existe.
 * - Productos ↔ distribuidores se enlazan por nombre, desde ambas hojas ("Distribuido por" y "Portafolio de productos").
 * - Imágenes y PDF (URL pública, incluido Google Drive) se descargan una sola vez por URL. Desde el escritorio se
 *   encolan en Action Scheduler (segundo plano); desde WP-CLI se descargan en el momento.
 * - Modo simulación: no guarda nada y devuelve el mismo informe.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

class LBX_Importer {

	/** Columnas de la hoja Productos: clave → encabezados aceptados (normalizados con self::key()). */
	private const PRODUCT_COLUMNS = array(
		'nombre'            => array( 'nombre-producto', 'nombre-del-producto', 'producto', 'nombre' ),
		'sku'               => array( 'sku', 'codigo' ),
		'segmento'          => array( 'categoria', 'segmento' ),
		'tipo'              => array( 'tipo-de-producto', 'tipo-producto' ),
		'imagen'            => array( 'ruta-url-de-imagen', 'url-de-imagen', 'imagen' ),
		'combustible'       => array( 'tipo-combustible', 'tipo-de-combustible', 'combustible' ),
		'descripcion'       => array( 'descripcion' ),
		'descripcion_corta' => array( 'descripcion-corta', 'bajada' ),
		'ficha'             => array( 'ficha-tecnica' ),
		'viscosidad'        => array( 'grado-de-viscosidad', 'viscosidad' ),
		'especificaciones'  => array( 'especificaciones-normativas-o-certificaciones', 'especificaciones', 'especificacion-tecnica' ),
		'base'              => array( 'base' ),
		'sds'               => array( 'hoja-de-seguridad-sds', 'hoja-de-seguridad' ),
		'distribuidores'    => array( 'distribuido-por', 'distribuidores' ),
		'presentaciones'    => array( 'presentacion-envase', 'presentaciones', 'presentacion' ),
		'aplicaciones'      => array( 'aplicaciones-recomendadas', 'aplicaciones' ),
		'normas'            => array( 'normas-y-homologaciones', 'normas' ),
		'destacado'         => array( 'destacado' ),
	);

	/** Columnas de propiedades típicas: prefijo del encabezado → [propiedad, unidad]. */
	private const PROPERTY_COLUMNS = array(
		'indice-de-viscosidad'         => array( 'Índice de viscosidad', '' ),
		'viscosidad-a-100'             => array( 'Viscosidad a 100 °C', 'mm²/s' ),
		'viscosidad-a-40'              => array( 'Viscosidad a 40 °C', 'mm²/s' ),
		'punto-de-inflamacion'         => array( 'Punto de inflamación', '°C' ),
		'punto-de-fluidez'             => array( 'Punto de fluidez', '°C' ),
		'intervalo-de-cambio-sugerido' => array( 'Intervalo de cambio sugerido', '' ),
	);

	/**
	 * Columnas de la hoja Distribuidores. Región, comuna, zona, correo y coordenadas son opcionales.
	 * La comuna se valida contra la lista oficial (lbx_comuna_match()); si falta la región, se deduce de la comuna.
	 */
	private const DISTRIBUTOR_COLUMNS = array(
		'nombre'         => array( 'nombre-distribuidor', 'nombre-del-distribuidor', 'distribuidor', 'nombre' ),
		'tipo_compra'    => array( 'tipo-de-compra' ),
		'direccion'      => array( 'direccion' ),
		'link_maps'      => array( 'link-google-maps', 'enlace-google-maps', 'google-maps' ),
		'horario'        => array( 'horario' ),
		'telefono'       => array( 'telefono' ),
		'productos'      => array( 'portafolio-de-productos', 'productos' ),
		'capacidad'      => array( 'capacidad-logistica' ),
		'web'            => array( 'pagina-web', 'sitio-web', 'web' ),
		'presentaciones' => array( 'presentaciones-que-ofrece', 'presentaciones' ),
		'region'         => array( 'region' ),
		'ciudad'         => array( 'ciudad', 'comuna', 'ciudad-o-comuna' ),
		'zona'           => array( 'zona', 'nombre-corto' ),
		'email'          => array( 'correo', 'email', 'e-mail' ),
		'lat'            => array( 'latitud', 'lat' ),
		'lng'            => array( 'longitud', 'lng', 'lon' ),
	);

	private const REGIONS = array(
		'Arica y Parinacota',
		'Tarapacá',
		'Antofagasta',
		'Atacama',
		'Coquimbo',
		'Valparaíso',
		'Metropolitana de Santiago',
		"Libertador General Bernardo O'Higgins",
		'Maule',
		'Ñuble',
		'Biobío',
		'La Araucanía',
		'Los Ríos',
		'Los Lagos',
		'Aysén del General Carlos Ibáñez del Campo',
		'Magallanes y de la Antártica Chilena',
	);

	private bool $dry_run;
	private string $status;
	private bool $inline_media;

	/** @var callable|null */
	private $logger;

	private array $rows    = array();
	private array $totals  = array();
	private array $notices = array();

	/** Nombres (clave normalizada) presentes en el archivo, para validar enlaces en simulación. */
	private array $file_products     = array();
	private array $file_distributors = array();

	/** Enlaces pendientes: [post_id => [nombres]]. */
	private array $product_links     = array();
	private array $distributor_links = array();

	private array $term_maps = array();

	/**
	 * @param array $args dry_run (bool), status ('publish'|'draft'), inline_media (bool), logger (callable).
	 */
	public function __construct( array $args = array() ) {
		$this->dry_run      = (bool) ( $args['dry_run'] ?? true );
		$this->status       = in_array( $args['status'] ?? '', array( 'publish', 'draft' ), true ) ? $args['status'] : 'publish';
		$this->inline_media = (bool) ( $args['inline_media'] ?? false );
		$this->logger       = $args['logger'] ?? null;

		foreach ( array( 'productos', 'distribuidores' ) as $type ) {
			$this->totals[ $type ] = array(
				'crear'      => 0,
				'actualizar' => 0,
				'error'      => 0,
			);
		}
		$this->totals['medios'] = 0;
	}

	/**
	 * Importa las hojas leídas con LBX_Xlsx_Reader.
	 *
	 * @return array{dry_run: bool, totals: array, rows: array, notices: array}
	 */
	public function run( array $sheets ): array {
		wp_raise_memory_limit( 'admin' );
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 600 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
		}

		$products     = $this->table( $sheets, 'productos', self::PRODUCT_COLUMNS, true );
		$distributors = $this->table( $sheets, 'distribuidores', self::DISTRIBUTOR_COLUMNS, false );

		if ( null === $products && null === $distributors ) {
			$this->notice( __( 'No se encontraron las hojas "Productos" ni "Distribuidores" con sus encabezados (Nombre Producto / Nombre Distribuidor).', 'lubrax' ) );
		}

		foreach ( (array) $products as $line => $row ) {
			$this->file_products[ self::key( $row['nombre'] ) ] = true;
		}
		foreach ( (array) $distributors as $line => $row ) {
			$this->file_distributors[ self::key( $row['nombre'] ) ] = true;
		}

		// Primero distribuidores, luego productos, al final los enlaces entre ambos.
		foreach ( (array) $distributors as $line => $row ) {
			$this->import_distributor( $row, $line );
		}
		foreach ( (array) $products as $line => $row ) {
			$this->import_product( $row, $line );
		}
		$this->link_records();

		if ( ! $this->dry_run ) {
			lbx_flush_distributors_cache();
		}

		return array(
			'dry_run' => $this->dry_run,
			'totals'  => $this->totals,
			'rows'    => $this->rows,
			'notices' => $this->notices,
		);
	}

	/* ------------------------------------------------------------------
	 * Lectura de las hojas
	 * ------------------------------------------------------------------ */

	/**
	 * Filas de datos de una hoja como [número de fila => [clave => valor]]. Null si la hoja no está.
	 */
	private function table( array $sheets, string $sheet_key, array $columns, bool $with_properties ): ?array {
		$rows = null;
		foreach ( $sheets as $name => $sheet_rows ) {
			if ( self::key( $name ) === $sheet_key ) {
				$rows = $sheet_rows;
				break;
			}
		}
		if ( null === $rows ) {
			return null;
		}

		// Fila de encabezados: la primera (de las 15 primeras) cuya columna "nombre" se reconoce.
		$map         = array();
		$header_line = 0;
		foreach ( array_slice( $rows, 0, 15, true ) as $line => $cells ) {
			$map = $this->map_headers( $cells, $columns, $with_properties );
			if ( isset( $map['nombre'] ) ) {
				$header_line = $line;
				break;
			}
		}
		if ( ! $header_line ) {
			/* translators: %s: nombre de la hoja. */
			$this->notice( sprintf( __( 'Hoja "%s": no se encontró la fila de encabezados.', 'lubrax' ), $sheet_key ) );
			return null;
		}

		$data = array();
		foreach ( $rows as $line => $cells ) {
			if ( $line <= $header_line ) {
				continue;
			}
			$row = array();
			foreach ( $map as $key => $col ) {
				$row[ $key ] = trim( (string) ( $cells[ $col ] ?? '' ) );
			}
			if ( '' !== ( $row['nombre'] ?? '' ) ) {
				$data[ $line ] = $row;
			}
		}

		return $data;
	}

	private function map_headers( array $cells, array $columns, bool $with_properties ): array {
		$map = array();
		foreach ( $cells as $col => $header ) {
			$key = self::key( (string) $header );
			if ( '' === $key ) {
				continue;
			}
			foreach ( $columns as $field => $aliases ) {
				if ( ! isset( $map[ $field ] ) && in_array( $key, $aliases, true ) ) {
					$map[ $field ] = $col;
					continue 2;
				}
			}
			if ( $with_properties ) {
				foreach ( array_keys( self::PROPERTY_COLUMNS ) as $prefix ) {
					if ( str_starts_with( $key, $prefix ) ) {
						$map[ 'prop:' . $prefix ] = $col;
						continue 2;
					}
				}
			}
		}
		return $map;
	}

	/* ------------------------------------------------------------------
	 * Distribuidores
	 * ------------------------------------------------------------------ */

	private function import_distributor( array $row, int $line ): void {
		$name     = $row['nombre'];
		$existing = $this->find_post( 'distribuidor', $name );
		$warnings = array();

		$region = $this->match_region( $row['region'] ?? '' );

		// Comuna: debe estar en la lista oficial (inc/data/comunas-chile.php); se guarda con su nombre oficial.
		$from_address = false;
		if ( '' === ( $row['ciudad'] ?? '' ) && ! ( $existing && get_post_meta( $existing, 'ciudad', true ) ) ) {
			$row['ciudad'] = $this->city_from_address( $row['direccion'] ?? '' );
			$from_address  = '' !== $row['ciudad'];
		}
		if ( '' !== ( $row['ciudad'] ?? '' ) ) {
			$comuna = lbx_comuna_match( $row['ciudad'] );
			if ( ! $comuna ) {
				/* translators: %s: comuna. */
				$warnings[]    = sprintf( __( 'La comuna «%s» no está en la lista de comunas de Chile: no se guardó.', 'lubrax' ), $row['ciudad'] );
				$row['ciudad'] = '';
			} elseif ( $region && $comuna['region'] !== $region ) {
				/* translators: 1: comuna, 2: región de la comuna, 3: región del archivo. */
				$warnings[]    = sprintf( __( '%1$s pertenece a %2$s, no a %3$s: no se guardó la comuna.', 'lubrax' ), $comuna['comuna'], $comuna['region'], $region );
				$row['ciudad'] = '';
			} else {
				$row['ciudad'] = $comuna['comuna'];
				$region        = $region ?: $comuna['region'];
				if ( $from_address ) {
					/* translators: %s: comuna. */
					$warnings[] = sprintf( __( 'Comuna deducida de la dirección: «%s» (revísala).', 'lubrax' ), $comuna['comuna'] );
				}
			}
		}
		if ( '' === ( $row['ciudad'] ?? '' ) && ! ( $existing && get_post_meta( $existing, 'ciudad', true ) ) ) {
			$warnings[] = __( 'Sin comuna.', 'lubrax' );
		}

		$region = $region ?: $this->match_region( $row['direccion'] ?? '' );
		if ( ! $region && ! ( $existing && get_post_meta( $existing, 'region', true ) ) ) {
			$warnings[] = __( 'Sin región: agrégala en la ficha (es necesaria para el filtro del mapa).', 'lubrax' );
		}

		$coords = null;
		if ( is_numeric( $row['lat'] ?? '' ) && is_numeric( $row['lng'] ?? '' ) ) {
			$coords = array(
				'lat' => (float) str_replace( ',', '.', $row['lat'] ),
				'lng' => (float) str_replace( ',', '.', $row['lng'] ),
			);
		} elseif ( '' !== ( $row['link_maps'] ?? '' ) && ! $this->dry_run ) {
			$coords = lbx_maps_coords( $row['link_maps'] );
		}
		if ( ! $coords && ! ( $existing && get_post_meta( $existing, 'lat', true ) ) ) {
			$warnings[] = '' !== ( $row['link_maps'] ?? '' ) && $this->dry_run
				? __( 'Las coordenadas se obtendrán del enlace de Google Maps al importar.', 'lubrax' )
				: __( 'Sin coordenadas: no aparecerá en el mapa hasta completarlas.', 'lubrax' );
		}

		if ( $this->dry_run ) {
			$this->row( 'distribuidores', $line, $name, $existing ? 'actualizar' : 'crear', $warnings );
			$this->queue_links( $this->distributor_links, $existing ?: -$line, $row['productos'] ?? '' );
			return;
		}

		$post_id = $existing ?: wp_insert_post(
			array(
				'post_type'   => 'distribuidor',
				'post_title'  => $name,
				'post_status' => $this->status,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			$this->row( 'distribuidores', $line, $name, 'error', array( $post_id->get_error_message() ) );
			return;
		}

		$fields = array(
			'direccion' => sanitize_text_field( $row['direccion'] ?? '' ),
			'horario'   => sanitize_text_field( $row['horario'] ?? '' ),
			'telefono'  => sanitize_text_field( $row['telefono'] ?? '' ),
			'ciudad'    => sanitize_text_field( $row['ciudad'] ?? '' ),
			'zona'      => sanitize_text_field( $row['zona'] ?? '' ),
			'email'     => sanitize_email( $row['email'] ?? '' ),
			'region'    => $region,
			// URL: sin sanitize_text_field, que elimina los %XX de los enlaces de Google Maps.
			'link_maps' => esc_url_raw( $row['link_maps'] ?? '' ),
			'web'       => self::normalize_url( $row['web'] ?? '' ),
		);
		foreach ( $fields as $field => $value ) {
			if ( '' !== $value ) {
				lbx_update_field( $field, $value, $post_id );
			}
		}

		$lists = array(
			'tipo_compra'    => array_map( 'ucfirst', array_map( 'strtolower', self::split( $row['tipo_compra'] ?? '' ) ) ),
			'presentaciones' => array_map( array( $this, 'normalize_pack' ), self::split( $row['presentaciones'] ?? '' ) ),
			'capacidad'      => array_map( fn( $v ) => preg_replace( '/\s+/', ' ', $v ), self::split( $row['capacidad'] ?? '' ) ),
		);
		foreach ( $lists as $field => $values ) {
			if ( $values ) {
				lbx_update_field( $field, array_values( array_unique( $values ) ), $post_id );
			}
		}

		if ( $coords ) {
			lbx_update_field( 'lat', $coords['lat'], $post_id );
			lbx_update_field( 'lng', $coords['lng'], $post_id );
		}

		$this->queue_links( $this->distributor_links, $post_id, $row['productos'] ?? '' );
		$this->row( 'distribuidores', $line, $name, $existing ? 'actualizar' : 'crear', $warnings, $post_id );
	}

	/* ------------------------------------------------------------------
	 * Productos
	 * ------------------------------------------------------------------ */

	private function import_product( array $row, int $line ): void {
		$name     = $row['nombre'];
		$sku      = $row['sku'] ?? '';
		$existing = $sku ? (int) wc_get_product_id_by_sku( $sku ) : 0;
		$existing = $existing ?: $this->find_post( 'product', $name );
		$warnings = array();

		$taxonomies = array(
			'segmento'       => self::split( $row['segmento'] ?? '' ),
			'tipo_producto'  => self::split( $row['tipo'] ?? '' ),
			'combustible'    => self::split( $row['combustible'] ?? '' ),
			'viscosidad'     => self::split( $row['viscosidad'] ?? '' ),
			'especificacion' => self::split( $row['especificaciones'] ?? '', true ),
			'base_aceite'    => self::split( $row['base'] ?? '' ),
		);
		if ( ! $taxonomies['segmento'] && ! ( $existing && has_term( '', 'segmento', $existing ) ) ) {
			$warnings[] = __( 'Sin categoría (segmento).', 'lubrax' );
		}

		if ( $this->dry_run ) {
			foreach ( $taxonomies as $taxonomy => $names ) {
				foreach ( $names as $term_name ) {
					if ( ! $this->find_term( $taxonomy, $term_name ) ) {
						/* translators: 1: término, 2: taxonomía. */
						$warnings[] = sprintf( __( 'Se creará "%1$s" en %2$s.', 'lubrax' ), $term_name, get_taxonomy( $taxonomy )->labels->singular_name );
					}
				}
			}
			$this->queue_links( $this->product_links, $existing ?: -$line, $row['distribuidores'] ?? '' );
			$this->row( 'productos', $line, $name, $existing ? 'actualizar' : 'crear', $warnings );
			return;
		}

		try {
			$product = $existing ? wc_get_product( $existing ) : new WC_Product_Simple();
			$product->set_name( $name );
			if ( ! $existing ) {
				$product->set_status( $this->status );
				$product->set_catalog_visibility( 'visible' );
			}
			if ( '' !== ( $row['descripcion'] ?? '' ) ) {
				$product->set_description( wp_kses_post( $row['descripcion'] ) );
			}
			if ( '' !== ( $row['descripcion_corta'] ?? '' ) ) {
				$product->set_short_description( wp_kses_post( $row['descripcion_corta'] ) );
			}
			if ( '' !== ( $row['destacado'] ?? '' ) ) {
				$product->set_featured( in_array( self::key( $row['destacado'] ), array( 'si', '1', 'x', 'true' ), true ) );
			}
			if ( $sku ) {
				$product->set_sku( $sku );
			}
			$post_id = $product->save();
		} catch ( Exception $e ) {
			$this->row( 'productos', $line, $name, 'error', array( $e->getMessage() ) );
			return;
		}

		// Taxonomías: una celda con valores reemplaza los términos de esa taxonomía; vacía, los conserva.
		foreach ( $taxonomies as $taxonomy => $names ) {
			if ( $names ) {
				wp_set_object_terms( $post_id, $this->term_ids( $taxonomy, $names, $warnings ), $taxonomy );
			}
		}

		$this->update_product_fields( $post_id, $row );

		// Medios: imagen principal y documentos.
		$media = array(
			array( 'imagen', $row['imagen'] ?? '', '' ),
			array( 'documento', $row['ficha'] ?? '', 'ficha-tecnica' ),
			array( 'documento', $row['sds'] ?? '', 'hoja-seguridad' ),
		);
		foreach ( $media as [ $kind, $url, $doc_type ] ) {
			if ( '' === $url ) {
				continue;
			}
			if ( ! wp_http_validate_url( $url ) ) {
				/* translators: %s: URL. */
				$warnings[] = sprintf( __( 'URL no válida: %s', 'lubrax' ), $url );
				continue;
			}
			$result = LBX_Import_Media::handle( $post_id, $kind, $url, $doc_type, $this->inline_media );
			if ( is_wp_error( $result ) ) {
				$warnings[] = $result->get_error_message();
			} else {
				++$this->totals['medios'];
			}
		}

		$this->queue_links( $this->product_links, $post_id, $row['distribuidores'] ?? '' );
		$this->row( 'productos', $line, $name, $existing ? 'actualizar' : 'crear', $warnings, $post_id );
	}

	/**
	 * Campos SCF del producto. Presentaciones y aplicaciones se reemplazan si la celda trae valores;
	 * las propiedades se actualizan una a una (las que no vienen en el Excel se conservan).
	 */
	private function update_product_fields( int $post_id, array $row ): void {
		$packs = array_map( array( $this, 'normalize_pack' ), self::split( $row['presentaciones'] ?? '' ) );
		if ( $packs ) {
			// Conserva la imagen ya cargada de cada envase.
			$images = array();
			foreach ( (array) get_field( lbx_field_key( 'presentaciones', $post_id ), $post_id ) as $current ) {
				if ( ! empty( $current['envase'] ) ) {
					$images[ $current['envase'] ] = $current['imagen'] ?? '';
				}
			}
			$rows = array();
			foreach ( array_unique( $packs ) as $pack ) {
				$rows[] = array(
					'envase' => $pack,
					'imagen' => $images[ $pack ] ?? '',
				);
			}
			lbx_update_field( 'presentaciones', $rows, $post_id );
		}

		$applications = self::split( $row['aplicaciones'] ?? '', false, false );
		if ( $applications ) {
			lbx_update_field( 'aplicaciones', array_map( fn( $text ) => array( 'texto' => $text ), $applications ), $post_id );
		}

		if ( '' !== ( $row['normas'] ?? '' ) ) {
			lbx_update_field( 'normas', sanitize_textarea_field( $row['normas'] ), $post_id );
		}

		$properties = array();
		foreach ( (array) get_field( lbx_field_key( 'propiedades', $post_id ), $post_id ) as $current ) {
			if ( ! empty( $current['propiedad'] ) ) {
				$properties[ $current['propiedad'] ] = (string) $current['valor'];
			}
		}
		$changed = false;
		foreach ( self::PROPERTY_COLUMNS as $prefix => [ $label, $unit ] ) {
			$value = $row[ 'prop:' . $prefix ] ?? '';
			if ( '' === $value ) {
				continue;
			}
			$properties[ $label ] = self::with_unit( $value, $unit );
			$changed              = true;
		}
		if ( $changed ) {
			$rows = array();
			foreach ( $properties as $label => $value ) {
				$rows[] = array(
					'propiedad' => $label,
					'valor'     => $value,
				);
			}
			lbx_update_field( 'propiedades', $rows, $post_id );
		}
	}

	/* ------------------------------------------------------------------
	 * Enlaces producto ↔ distribuidor
	 * ------------------------------------------------------------------ */

	private function queue_links( array &$links, int $post_id, string $names ): void {
		foreach ( self::split( $names ) as $name ) {
			$links[ $post_id ][] = $name;
		}
	}

	private function link_records(): void {
		$pairs = array(
			// Desde la hoja Productos ("Distribuido por").
			array( $this->product_links, 'distribuidor', 'distribuidores', $this->file_distributors, __( 'Distribuidor no encontrado: "%s".', 'lubrax' ) ),
			// Desde la hoja Distribuidores ("Portafolio de productos").
			array( $this->distributor_links, 'product', 'productos', $this->file_products, __( 'Producto no encontrado en el portafolio: "%s".', 'lubrax' ) ),
		);

		foreach ( $pairs as [ $links, $target_type, $field, $in_file, $message ] ) {
			foreach ( $links as $post_id => $names ) {
				$ids = array();
				foreach ( array_unique( $names ) as $name ) {
					$target = $this->find_post( $target_type, $name );
					if ( $target ) {
						$ids[] = $target;
					} elseif ( ! ( $this->dry_run && isset( $in_file[ self::key( $name ) ] ) ) ) {
						$this->notice( sprintf( $message, $name ) );
					}
				}
				if ( $this->dry_run || $post_id <= 0 || ! $ids ) {
					continue;
				}
				// Suma a los enlaces existentes; SCF actualiza el otro lado (relación bidireccional).
				$current = array_map( 'intval', (array) get_field( lbx_field_key( $field, $post_id ), $post_id, false ) );
				lbx_update_field( $field, array_values( array_unique( array_merge( array_filter( $current ), $ids ) ) ), $post_id );
			}
		}
	}

	/* ------------------------------------------------------------------
	 * Términos
	 * ------------------------------------------------------------------ */

	private function find_term( string $taxonomy, string $name ): int {
		if ( ! isset( $this->term_maps[ $taxonomy ] ) ) {
			$this->term_maps[ $taxonomy ] = array();
			foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $term ) {
				$this->term_maps[ $taxonomy ][ self::key( $term->name ) ] = $term->term_id;
				$this->term_maps[ $taxonomy ][ $term->slug ]              = $term->term_id;
			}
		}
		return $this->term_maps[ $taxonomy ][ self::key( $name ) ] ?? 0;
	}

	private function term_ids( string $taxonomy, array $names, array &$warnings ): array {
		$ids = array();
		foreach ( $names as $name ) {
			$id = $this->find_term( $taxonomy, $name );
			if ( ! $id ) {
				$created = wp_insert_term( $name, $taxonomy );
				if ( is_wp_error( $created ) ) {
					$warnings[] = $created->get_error_message();
					continue;
				}
				$id                                                  = (int) $created['term_id'];
				$this->term_maps[ $taxonomy ][ self::key( $name ) ] = $id;
				/* translators: 1: término, 2: taxonomía. */
				$warnings[] = sprintf( __( 'Nuevo término "%1$s" en %2$s.', 'lubrax' ), $name, get_taxonomy( $taxonomy )->labels->singular_name );
			}
			$ids[] = $id;
		}
		return array_values( array_unique( $ids ) );
	}

	/* ------------------------------------------------------------------
	 * Utilidades
	 * ------------------------------------------------------------------ */

	/**
	 * Busca una entrada por título exacto (sin distinguir mayúsculas ni tildes), en cualquier estado salvo papelera.
	 */
	private function find_post( string $post_type, string $title ): int {
		static $index = array();

		if ( ! isset( $index[ $post_type ] ) ) {
			$index[ $post_type ] = array();
			$ids                 = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
			foreach ( $ids as $id ) {
				$index[ $post_type ][ self::key( get_the_title( $id ) ) ] = $id;
			}
		}

		$key = self::key( $title );
		if ( ! isset( $index[ $post_type ][ $key ] ) && ! $this->dry_run ) {
			// Creado en esta misma importación.
			$found = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => array( 'publish', 'draft' ),
					'title'          => $title,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( $found ) {
				$index[ $post_type ][ $key ] = (int) $found[0];
			}
		}

		return (int) ( $index[ $post_type ][ $key ] ?? 0 );
	}

	private function match_region( string $text ): string {
		$text = self::key( $text );
		if ( '' === $text ) {
			return '';
		}
		$aliases = array(
			'rm'            => 'Metropolitana de Santiago',
			'metropolitana' => 'Metropolitana de Santiago',
			'santiago'      => 'Metropolitana de Santiago',
			'ohiggins'      => "Libertador General Bernardo O'Higgins",
			'o-higgins'     => "Libertador General Bernardo O'Higgins",
			'araucania'     => 'La Araucanía',
			'aysen'         => 'Aysén del General Carlos Ibáñez del Campo',
			'magallanes'    => 'Magallanes y de la Antártica Chilena',
		);
		foreach ( self::REGIONS as $region ) {
			if ( str_contains( "-$text-", '-' . self::key( $region ) . '-' ) ) {
				return $region;
			}
		}
		foreach ( self::REGIONS as $region ) {
			$short = self::key( preg_replace( '/^(la|los|libertador general bernardo)\s+/i', '', $region ) );
			if ( str_contains( "-$text-", "-$short-" ) ) {
				return $region;
			}
		}
		foreach ( $aliases as $alias => $region ) {
			if ( str_contains( "-$text-", "-$alias-" ) ) {
				return $region;
			}
		}
		return '';
	}

	/**
	 * "Av. Siempre Viva 123, Talca, Maule" → "Talca": la última parte de la dirección que no sea región ni número.
	 */
	private function city_from_address( string $address ): string {
		$parts = array_map( 'trim', explode( ',', $address ) );
		array_shift( $parts ); // La calle.
		$parts = array_filter(
			$parts,
			fn( $part ) => '' !== $part && ! preg_match( '/\d/', $part ) && mb_strlen( $part ) <= 40 && 'chile' !== self::key( $part )
		);

		$region_keys = array_map( array( __CLASS__, 'key' ), self::REGIONS );
		foreach ( array_reverse( $parts ) as $part ) {
			$key = preg_replace( '/^region-(de-|del-)?/', '', self::key( $part ) );
			if ( ! in_array( $key, $region_keys, true ) ) {
				return $part;
			}
		}

		// Todas las partes son nombres de región (p. ej. "…, Valparaíso, Valparaíso"): la primera es la ciudad.
		return (string) ( reset( $parts ) ?: '' );
	}

	private function normalize_pack( string $value ): string {
		$key = self::key( $value );
		$map = array(
			'/^(botella-)?1-?l(itro)?s?$/'                    => '1 L',
			'/^(bidon-)?4-?l(itro)?s?$/'                      => '4 L',
			'/^(balde-)?20-?l(itro)?s?$/'                     => '20 L',
			'/^(cubeta-)?19-?l(itro)?s?$/'                    => 'Cubeta 19 L',
			'/^(tambo|tambor)?-?200-?l(itro)?s?$/'            => 'Tambo 200 L',
			'/^(granel|ibc)(-1000-?l(itro)?s?)?$|^1000-?l$/' => 'Granel 1000 L',
		);
		foreach ( $map as $pattern => $label ) {
			if ( preg_match( $pattern, $key ) ) {
				return $label;
			}
		}
		return $value;
	}

	private function row( string $type, int $line, string $name, string $action, array $warnings, int $post_id = 0 ): void {
		++$this->totals[ $type ][ $action ];
		$this->rows[] = compact( 'type', 'line', 'name', 'action', 'warnings', 'post_id' );
		if ( $this->logger ) {
			call_user_func( $this->logger, sprintf( '[%s fila %d] %s: %s%s', $type, $line, $action, $name, $warnings ? ' — ' . implode( ' ', $warnings ) : '' ) );
		}
	}

	private function notice( string $message ): void {
		if ( ! in_array( $message, $this->notices, true ) ) {
			$this->notices[] = $message;
		}
	}

	/**
	 * Clave de comparación: minúsculas, sin tildes ni signos ("Línea  pesada" → "linea-pesada").
	 */
	public static function key( string $value ): string {
		return sanitize_title( remove_accents( wp_strip_all_tags( $value ) ) );
	}

	/**
	 * Separa una celda con varios valores (coma, punto y coma, barra vertical o salto de línea).
	 * Con $slash también separa " / " (p. ej. "API SN / JASO MA2"). Sin $comma no separa por coma
	 * (textos largos, como las aplicaciones, pueden llevar comas).
	 */
	public static function split( string $value, bool $slash = false, bool $comma = true ): array {
		$separators = '[;|\n\r]' . ( $comma ? '|,(?!\d)' : '' ) . ( $slash ? '|\s+\/\s+' : '' );
		$parts      = preg_split( '/\s*(?:' . $separators . ')\s*/u', $value );
		return array_values( array_filter( array_map( 'trim', (array) $parts ), 'strlen' ) );
	}

	/**
	 * "10.9" → "10,9 mm²/s". Si el valor ya trae unidad, no la repite.
	 */
	private static function with_unit( string $value, string $unit ): string {
		$value = trim( $value );
		// Número decimal de Excel ("10.9") → coma decimal. "10.000" (miles escritos como texto) se deja igual.
		if ( is_numeric( $value ) && ! preg_match( '/^\d{1,3}(\.\d{3})+$/', $value ) ) {
			$value = str_replace( '.', ',', (string) ( 0 + $value ) );
		}
		if ( $unit && ! str_contains( $value, trim( $unit, '°' ) ) ) {
			$value .= ' ' . $unit;
		}
		return $value;
	}

	private static function normalize_url( string $url ): string {
		$url = trim( $url );
		if ( '' !== $url && ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}
		return esc_url_raw( $url );
	}
}
