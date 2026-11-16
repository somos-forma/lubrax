<?php
/**
 * Opciones del tema (Secure Custom Fields): página "Opciones del tema" con pestañas.
 *
 * Los campos están en acf-json/group_lbx_theme_options.json (editables desde SCF > Grupos de campos;
 * los cambios se guardan de vuelta en esa carpeta). Lectura: lbx_option( 'phone' ) → campo "lbx_phone".
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valores por defecto (de la maqueta). Se usan si el campo está vacío o si SCF no está activo.
 */
function lbx_option_defaults(): array {
	return array(
		'phone'       => '800 363 776',
		'email'       => 'atencion@lubrax.com',
		'whatsapp'    => '+569 3917 1917',
		// Notas bajo cada canal (página de contacto).
		'phone_note'    => 'Lun a Vie 8:00–18:00 · pedidos, entregas y reclamos',
		'email_note'    => 'Respuesta con número de caso en menos de 24 h hábiles',
		'whatsapp_note' => 'Consultas rápidas de producto y disponibilidad',
		'address'     => "Cerro Colorado 5240, Torre 1, piso 12.\nLas Condes. Chile.",
		'footer_text' => 'Nuestra línea de lubricantes Lubrax, es desarrollado bajo las normas y especificaciones más exigentes del mundo. Combina tecnología y calidad para ofrecer un portafolio de productos sintéticos, semisintéticos y minerales, que responden a los requerimientos automotrices e industriales.',
		'instagram'   => 'https://www.instagram.com/',
		'facebook'    => 'https://www.facebook.com/',
		'youtube'     => 'https://www.youtube.com/',
		'linkedin'    => 'https://www.linkedin.com/',
		// Mapa de distribuidores (Leaflet).
		'tile_url'         => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
		'tile_attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
		'tile_max_zoom'    => '18',
		// Banner del catálogo (columna de filtros de productos.html).
		'promo_show'       => '1',
		'promo_title'      => 'Ya disponibles en 1 L y 4 L',
		'promo_text'       => 'Línea automotriz en formatos de mostrador.',
		'promo_bg'         => '#032b1a',
		'promo_tone'       => 'claro',
		// Página 404 (404.html de la maqueta).
		'e404_kicker'      => 'Error 404 · Página no encontrada',
		'e404_title'       => "Esta página\nse nos escurrió",
		'e404_lead'        => 'Puede que el enlace esté roto o que la página haya cambiado de dirección. Vuelve a la página anterior o sigue explorando el sitio.',
		'e404_back'        => 'Volver atrás',
		'e404_search'      => 'Busca un producto, viscosidad o norma',
		'e404_links_title' => 'También puedes ir a',
		'e404_description' => 'La página que buscas no existe o cambió de dirección. Vuelve atrás o sigue explorando el catálogo de lubricantes Lubrax.',
	);
}

/**
 * Valor con formato de SCF (enlaces, repetidores) de una opción. null si está vacía o SCF no está activo.
 */
function lbx_option_field( string $key ) {
	$value = function_exists( 'get_field' ) ? get_field( 'lbx_' . $key, 'option' ) : null;
	return ( null === $value || '' === $value || false === $value || array() === $value ) ? null : $value;
}

/**
 * Valor guardado de una opción (null si nunca se guardó o SCF no está activo).
 */
function lbx_option_raw( string $key ): ?string {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	$value = get_field( 'lbx_' . $key, 'option', false );

	return null === $value ? null : (string) $value;
}

/**
 * Valor de una opción. Si está vacía, devuelve el valor por defecto.
 */
function lbx_option( string $key ): string {
	$value = lbx_option_raw( $key );

	if ( null !== $value && '' !== $value ) {
		return $value;
	}

	return lbx_option_defaults()[ $key ] ?? '';
}

/**
 * Redes sociales con URL, en el orden del footer.
 *
 * @return array<string, array{label: string, url: string}>
 */
function lbx_social_links(): array {
	$labels = array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
		'linkedin'  => 'LinkedIn',
	);
	$links  = array();

	foreach ( $labels as $network => $label ) {
		// Una vez guardadas las opciones, una URL vacía oculta la red (no vuelve al valor por defecto).
		$url = lbx_option_raw( $network ) ?? lbx_option_defaults()[ $network ];
		if ( $url ) {
			$links[ $network ] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}

	return $links;
}

/**
 * Página "Opciones del tema" en el menú principal del escritorio.
 */
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		acf_add_options_page(
			array(
				'page_title'      => __( 'Opciones del tema', 'lubrax' ),
				'menu_title'      => __( 'Opciones del tema', 'lubrax' ),
				'menu_slug'       => 'lbx-opciones',
				'capability'      => 'manage_options',
				'icon_url'        => 'dashicons-admin-customizer',
				'position'        => 59,
				'redirect'        => false,
				'update_button'   => __( 'Guardar opciones', 'lubrax' ),
				'updated_message' => __( 'Opciones guardadas.', 'lubrax' ),
			)
		);
	}
);

/**
 * Local JSON: los grupos de campos del tema se cargan y se guardan en /acf-json.
 *
 * Sincronización en los dos sentidos:
 * - Admin → código: al guardar un grupo en SCF > Grupos de campos, SCF escribe acf-json/{clave}.json (save_json).
 * - Código → admin: lbx_sync_field_groups() importa a la base de datos los JSON nuevos o más recientes
 *   (por ejemplo, después de un git pull), así todos los grupos se ven y se editan en el escritorio.
 * Si la base de datos se pierde, los JSON siguen cargando los campos y se vuelven a importar solos.
 */
add_filter( 'acf/settings/save_json', fn() => LBX_DIR . '/acf-json' );
add_filter(
	'acf/settings/load_json',
	function ( array $paths ) {
		$paths[] = LBX_DIR . '/acf-json';
		return array_unique( $paths );
	}
);

/**
 * Importa a la base de datos los grupos de acf-json que no están o cuyo JSON es más reciente.
 * Es lo mismo que la acción "Sincronizar" de SCF, pero automático.
 *
 * @return string[] Títulos de los grupos importados.
 */
function lbx_sync_field_groups(): array {
	if ( ! function_exists( 'acf_get_local_json_files' ) || ! function_exists( 'acf_import_field_group' ) ) {
		return array();
	}

	$synced = array();
	$json   = acf_get_setting( 'json' );
	acf_update_setting( 'json', false ); // Sin reescribir los .json durante la importación.

	foreach ( acf_get_local_json_files( 'acf-field-group' ) as $key => $file ) {
		$group = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $group ) || empty( $group['key'] ) ) {
			continue;
		}
		$post = acf_get_field_group_post( $key );
		// Margen de 10 s: al guardar en el admin, SCF escribe el JSON un instante después de la base de datos.
		if ( $post && (int) ( $group['modified'] ?? 0 ) <= (int) get_post_modified_time( 'U', true, $post->ID ) + 10 ) {
			continue;
		}
		$group['ID'] = $post ? $post->ID : 0;
		acf_import_field_group( $group );
		$synced[] = $group['title'];
	}

	acf_update_setting( 'json', $json );
	return $synced;
}

add_action(
	'admin_init',
	function () {
		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$synced = lbx_sync_field_groups();
		if ( $synced ) {
			add_action(
				'admin_notices',
				function () use ( $synced ) {
					printf(
						'<div class="notice notice-info is-dismissible"><p>%s %s</p></div>',
						esc_html__( 'Grupos de campos actualizados desde el código del tema (acf-json):', 'lubrax' ),
						esc_html( implode( ', ', $synced ) )
					);
				}
			);
		}
	},
	20
);
