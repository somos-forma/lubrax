<?php
/**
 * Bloques SCF (ACF): helpers de render y contenido por defecto.
 *
 * Los bloques están en /blocks/{nombre}/ con "acf" en block.json y sus campos en acf-json/group_lbx_block_*.json.
 * Cada campo lleva un prefijo propio (hero_, intro_…): SCF busca los campos por nombre y dos bloques con un
 * campo "titulo" se confundirían.
 *
 * Si un campo está vacío, el bloque usa el contenido de la maqueta (lbx_block_defaults()). El mismo contenido
 * se usa al crear las páginas y los patrones (lbx_acf_block_markup()), así el equipo editorial ve textos reales.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valor de un campo del bloque actual o, si está vacío, el valor por defecto del bloque.
 */
function lbx_block_value( string $name, string $block ) {
	$value = function_exists( 'get_field' ) ? get_field( $name ) : null;
	if ( null !== $value && '' !== $value && false !== $value && array() !== $value ) {
		return $value;
	}
	return lbx_block_defaults( $block )[ $name ] ?? '';
}

/**
 * "Lubricantes para\nquien mueve" → <span class="line"><span>…</span></span> por línea (títulos data-reveal="title").
 */
function lbx_title_lines( string $text ): string {
	$lines = array_filter( array_map( 'trim', preg_split( '/\R/', $text ) ), 'strlen' );
	return implode( ' ', array_map( fn( $line ) => '<span class="line"><span>' . esc_html( $line ) . '</span></span>', $lines ) );
}

/**
 * Texto con párrafos separados por una línea en blanco → <p>.
 */
function lbx_paragraphs( string $text, string $attrs = '' ): string {
	$html = '';
	foreach ( preg_split( '/\R\s*\R/', trim( $text ) ) as $paragraph ) {
		if ( '' !== trim( $paragraph ) ) {
			$html .= '<p' . $attrs . '>' . nl2br( esc_html( trim( $paragraph ) ) ) . '</p>';
		}
	}
	return $html;
}

/**
 * Enlace de un campo "link" (array url/title/target). Devuelve null si no hay URL.
 *
 * @return array{url: string, title: string, target: string}|null
 */
function lbx_link_value( $link ): ?array {
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return null;
	}
	return array(
		'url'    => (string) $link['url'],
		'title'  => (string) ( $link['title'] ?? '' ),
		'target' => (string) ( $link['target'] ?? '' ),
	);
}

/**
 * <a> con el texto del enlace, ícono opcional y aviso de pestaña nueva.
 */
function lbx_link_html( ?array $link, string $class, string $icon = '', string $extra = '' ): string {
	if ( ! $link ) {
		return '';
	}
	$blank = '_blank' === $link['target'];
	return sprintf(
		'<a class="%1$s" href="%2$s"%3$s%4$s>%5$s%6$s%7$s</a>',
		esc_attr( $class ),
		esc_url( $link['url'] ),
		$blank ? ' target="_blank" rel="noopener"' : '',
		$extra,
		esc_html( $link['title'] ),
		$blank ? lbx_new_tab_label() : '',
		$icon ? ' ' . $icon : ''
	);
}

/**
 * <img> de la biblioteca de medios o, si no hay, una imagen de la maqueta (assets/img).
 *
 * @param array $asset ['src' => 'home/x.webp', 'srcset' => ['home/x-640.webp' => 640, …], 'width' => 1024, 'height' => 683]
 * @param array $attr  Atributos del <img> (alt, sizes, loading, fetchpriority, class…).
 */
function lbx_image_or_asset( int $image_id, array $asset, array $attr = array(), string $size = 'large' ): string {
	$attr = array_merge(
		array(
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$attr
	);

	if ( $image_id && wp_attachment_is_image( $image_id ) ) {
		if ( ! isset( $attr['class'] ) ) {
			$attr['class'] = '';
		}
		if ( '' === $attr['alt'] ) {
			$attr['alt'] = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		}
		return wp_get_attachment_image( $image_id, $size, false, $attr );
	}

	$html = sprintf( '<img src="%s"', esc_url( lbx_asset( 'img/' . $asset['src'] ) ) );
	if ( ! empty( $asset['srcset'] ) ) {
		$html .= ' srcset="' . esc_attr( implode( ', ', array_map( fn( $file, $w ) => lbx_asset( 'img/' . $file ) . " {$w}w", array_keys( $asset['srcset'] ), $asset['srcset'] ) ) ) . '"';
	}
	$html .= sprintf( ' width="%d" height="%d"', $asset['width'], $asset['height'] );
	foreach ( $attr as $name => $value ) {
		if ( false !== $value && ( '' !== $value || 'alt' === $name ) ) {
			$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}
	}
	return $html . '>';
}

/**
 * Marcado de un bloque SCF con sus datos: <!-- wp:lubrax/x {"name":…,"data":{…},"mode":"preview"} /-->.
 *
 * @param array $values Valores por nombre de campo. Repetidores: lista de filas [subcampo => valor].
 */
function lbx_acf_block_markup( string $block, array $values ): string {
	$data = array();
	foreach ( $values as $name => $value ) {
		$field = function_exists( 'acf_get_field' ) ? acf_get_field( $name ) : null;
		if ( $field ) {
			lbx_acf_flatten( $field, $name, $value, $data );
		}
	}

	$attrs = array(
		'name' => $block,
		'data' => $data,
		'mode' => 'preview',
	);

	return '<!-- wp:' . $block . ' ' . serialize_block_attributes( $attrs ) . ' /-->';
}

/**
 * Datos de un campo en el formato de bloque de SCF (repetidores: nombre_0_subcampo y la clave en _nombre).
 */
function lbx_acf_flatten( array $field, string $path, $value, array &$data ): void {
	if ( 'repeater' === $field['type'] && is_array( $value ) ) {
		$rows                 = array_values( $value );
		$data[ $path ]        = count( $rows );
		$data[ '_' . $path ] = $field['key'];
		foreach ( $rows as $i => $row ) {
			foreach ( $field['sub_fields'] as $sub ) {
				if ( array_key_exists( $sub['name'], (array) $row ) ) {
					lbx_acf_flatten( $sub, "{$path}_{$i}_{$sub['name']}", $row[ $sub['name'] ], $data );
				}
			}
		}
		return;
	}
	$data[ $path ]        = $value;
	$data[ '_' . $path ] = $field['key'];
}

/**
 * Contenido de la maqueta para cada bloque (respaldo si un campo está vacío y contenido inicial de las páginas).
 */
function lbx_block_defaults( string $block ): array {
	$contact  = fn( string $tipo ) => lbx_contact_url( $tipo );
	$link     = fn( string $title, string $url, string $target = '' ) => array( 'title' => $title, 'url' => $url, 'target' => $target );
	$defaults = array(
		'hero-slider'       => array(
			'hero_h1'        => 'Lubrax: lubricantes automotrices e industriales en Chile',
			'hero_intervalo' => 7000,
			'hero_slides'    => array(
				array(
					'imagen'     => 0,
					'asset'      => array( 'src' => 'hero/rebranding-1200.webp', 'srcset' => array( 'hero/rebranding-768.webp' => 768, 'hero/rebranding-1200.webp' => 1200 ), 'width' => 1200, 'height' => 722, 'alt' => 'Envase Lubrax Top Auto 5W-40 sobre fondo oscuro' ),
					'producto'   => 0,
					'antetitulo' => 'Próximamente',
					'titulo'     => 'Viene el rebranding de Lubrax',
					'texto'      => 'Nueva identidad visual en envases, material de punto de venta y canales digitales. Los detalles de la transición se comparten con la red de distribuidores.',
					'boton'      => $link( 'Quiero enterarme primero', $contact( 'distribuidor' ) ),
				),
				array(
					'imagen'     => 0,
					'asset'      => array( 'src' => 'hero/envase-4l-840.webp', 'srcset' => array( 'hero/envase-4l-420.webp' => 420, 'hero/envase-4l-840.webp' => 840 ), 'width' => 839, 'height' => 1042, 'alt' => 'Envase de 4 litros de Lubrax Tecno' ),
					'producto'   => 1,
					'antetitulo' => 'Presentaciones 1 L y 4 L',
					'titulo'     => 'Ya disponibles en envase de 1 L y 4 L',
					'texto'      => 'La línea automotriz llega en formatos de mostrador, listos para exhibición y venta unitaria en punto de venta.',
					'boton'      => $link( 'Ver productos en 1 L y 4 L', lbx_page_url( 'productos' ) ),
				),
				array(
					'imagen'     => 0,
					'asset'      => array( 'src' => 'hero/burbujas-1200.webp', 'srcset' => array( 'hero/burbujas-768.webp' => 768, 'hero/burbujas-1200.webp' => 1200 ), 'width' => 1200, 'height' => 800, 'alt' => '' ),
					'producto'   => 0,
					'antetitulo' => 'Síguenos',
					'titulo'     => 'Novedades primero en Instagram',
					'texto'      => 'Lanzamientos, tips de mantenimiento y material para punto de venta, primero en nuestro Instagram.',
					'boton'      => $link( 'Seguir en Instagram', lbx_option( 'instagram' ), '_blank' ),
				),
			),
		),
		'intro'             => array(
			'intro_imagen'     => 0,
			'intro_asset'      => array( 'src' => 'home/gotas-180-880.webp', 'srcset' => array( 'home/gotas-180-480.webp' => 480, 'home/gotas-180-880.webp' => 880 ), 'width' => 880, 'height' => 880 ),
			'intro_alt'        => 'Gotas de lubricante dorado: más de 180 productos en catálogo',
			'intro_antetitulo' => 'Catálogo regional 2026',
			'intro_titulo'     => "Lubricantes para\nquien mueve\nla industria",
			'intro_texto'      => "Nuestra línea de lubricantes Lubrax es desarrollada bajo las normas y especificaciones más exigentes del mundo.\n\nCombina tecnología y calidad para ofrecer un portafolio de productos sintéticos, semisintéticos y minerales, que responden a los requerimientos automotrices e industriales.",
			'intro_boton'      => $link( 'Hazte cliente', $contact( 'cliente' ) ),
			'intro_enlace'     => $link( 'Ver catálogo', lbx_page_url( 'productos' ) ),
		),
		'segments'          => array(
			'seg_antetitulo' => 'Productos',
			'seg_titulo'     => 'Categorías',
			'seg_enlace'     => $link( 'Ver catálogo', lbx_page_url( 'productos' ) ),
			'seg_fondo'      => 0,
			'seg_asset'      => array( 'src' => 'home/categorias-bg.webp', 'width' => 1600, 'height' => 893 ),
		),
		'product-finder'    => array(
			'finder_titulo' => '¿Qué estás buscando?',
		),
		'plant'             => array(
			'plant_antetitulo'   => 'Lubrax',
			'plant_titulo'       => "Planta de\nLubricantes y\nEspecialidades",
			'plant_texto'        => 'Contamos con una planta propia, exclusiva para el desarrollo de lubricantes y especialidades, ubicada en la comuna de Maipú, en la Región Metropolitana. Nuestra planta cumple con todas las normativas para el desarrollo de productos de alta calidad que el mercado requiere.',
			'plant_enlace'       => $link( 'Buscar distribuidores', lbx_page_url( 'distribuidores' ) ),
			'plant_imagen'       => 0,
			'plant_asset'        => array( 'src' => 'home/planta-maipu-1024.webp', 'srcset' => array( 'home/planta-maipu-640.webp' => 640, 'home/planta-maipu-1024.webp' => 1024 ), 'width' => 1024, 'height' => 683 ),
			'plant_alt'          => 'Camión cisterna en la planta de lubricantes Lubrax en Maipú',
			'plant_insignia'     => 'ISO 4406',
			'plant_card_imagen'  => 0,
			'plant_card_asset'   => array( 'src' => 'home/microfiltrado.webp', 'width' => 240, 'height' => 155 ),
			'plant_card_alt'     => 'Instalaciones industriales de la planta',
			'plant_card_titulo'  => 'Microfiltrado en planta',
			'plant_card_texto'   => 'Para cumplir con los altos estándares de limpieza de los fabricantes de equipos, Lubrax microfiltra sus productos para alcanzar el código de limpieza medido con la norma ISO 4406.',
		),
		'featured-products' => array(
			'feat_antetitulo' => 'Destacados',
			'feat_titulo'     => 'Productos destacados',
			'feat_cantidad'   => 4,
			'feat_enlace'     => $link( 'Ver catálogo completo', lbx_page_url( 'productos' ) ),
		),
		'latest-posts'      => array(
			'news_antetitulo' => 'Blog',
			'news_titulo'     => 'Noticias y consejos',
			'news_cantidad'   => 3,
			'news_enlace'     => $link( 'Ver todos los artículos', lbx_page_url( 'blog' ) ),
		),
		'image-band'        => array(
			'band_imagen'        => 0,
			'band_asset'         => array( 'src' => 'pages/nosotros-motos.webp', 'width' => 1200, 'height' => 471 ),
			'band_antetitulo'    => 'Nuestro respaldo',
			'band_titulo'        => 'Más que un lubricante',
			'band_servicios'     => array(
				array( 'icono' => 'factory', 'titulo' => 'Planta propia', 'texto' => 'Producción y control de calidad en Maipú, con microfiltrado según ISO 4406.' ),
				array( 'icono' => 'flask', 'titulo' => 'Lubrax Express', 'texto' => 'Análisis de aceite usado para planes de mantención preventiva y correctiva.' ),
				array( 'icono' => 'users', 'titulo' => 'Capacitación técnica', 'texto' => 'Un equipo especializado que asesora y capacita a talleres, flotas e industria.' ),
				array( 'icono' => 'truck', 'titulo' => 'Red de distribuidores', 'texto' => 'Distribuidores autorizados en las principales regiones del país.' ),
			),
			'band_titulo_grande' => "Lubricantes para\nquien mueve\nla industria",
			'band_boton'         => $link( 'Encuentra tu distribuidor', lbx_page_url( 'distribuidores' ) ),
		),
		'faq'               => array(
			'faq_antetitulo' => 'Ayuda',
			'faq_titulo'     => 'Preguntas frecuentes',
			'faq_texto'      => 'Aquí encontrarás respuestas a las principales dudas sobre nuestros aceites lubricantes.',
			'faq_enlace'     => $link( '¿Otra pregunta? Escríbenos', $contact( 'consulta' ) ),
			'faq_schema'     => 1,
			'faq_items'      => array(
				array( 'pregunta' => '¿Qué es un lubricante?', 'respuesta' => 'Es un fluido que permite disminuir la fricción y evitar el desgaste de las piezas de los distintos componentes de un vehículo, como el motor, caja de cambios y transmisión.' ),
				array( 'pregunta' => '¿Cuáles son las funciones de un lubricante de motor?', 'respuesta' => 'Reduce la fricción y el desgaste, refrigera las piezas internas, mantiene el motor limpio al arrastrar residuos hacia el filtro, protege contra la corrosión y ayuda a sellar entre anillos y cilindros.' ),
				array( 'pregunta' => '¿Cómo se clasifican los lubricantes?', 'respuesta' => 'Por su base (mineral, semisintética o sintética), por su viscosidad según la norma SAE y por su nivel de desempeño según especificaciones como API o ACEA.' ),
				array( 'pregunta' => '¿Se pueden mezclar dos marcas de lubricantes?', 'respuesta' => 'Es posible en una emergencia si tienen la misma viscosidad y especificación, pero se recomienda usar un solo producto para mantener el desempeño de sus aditivos.' ),
				array( 'pregunta' => '¿Cuál es la diferencia entre un aceite mineral y uno sintético?', 'respuesta' => 'El mineral se obtiene de la refinación del petróleo. El sintético se produce mediante procesos químicos que dan moléculas más uniformes, con mejor estabilidad a altas temperaturas y mejor fluidez en frío.' ),
				array( 'pregunta' => '¿Es normal que un motor consuma aceite?', 'respuesta' => 'Sí, un consumo moderado es normal por el funcionamiento propio del motor. Un consumo elevado puede indicar desgaste o fugas y debe revisarse en un taller.' ),
				array( 'pregunta' => '¿La calidad del aceite sintético es superior a la del aceite mineral?', 'respuesta' => 'El sintético ofrece mayor protección en condiciones exigentes y permite intervalos de cambio más largos. Lo más importante es usar el aceite que recomienda el fabricante del vehículo.' ),
				array( 'pregunta' => '¿Cuál es la viscosidad más adecuada para un vehículo?', 'respuesta' => 'La que indica el manual del fabricante, que considera el diseño del motor y las condiciones de uso.' ),
				array( 'pregunta' => '¿Existen lubricantes diseñados para una cantidad de kilometrajes determinados?', 'respuesta' => 'Sí. Hay aceites formulados para motores de alto kilometraje, que ayudan a controlar el consumo de aceite y proteger sellos y piezas con desgaste.' ),
				array( 'pregunta' => '¿Cómo se mide correctamente el nivel de aceite del motor?', 'respuesta' => 'Con el vehículo en superficie plana y el motor frío o apagado hace algunos minutos: se retira la varilla, se limpia, se vuelve a introducir y se verifica que el nivel quede entre las marcas de mínimo y máximo.' ),
				array( 'pregunta' => '¿Por qué es importante revisar el nivel de aceite en un vehículo?', 'respuesta' => 'Un nivel bajo reduce la lubricación y la refrigeración del motor, lo que acelera el desgaste y puede provocar fallas graves.' ),
				array( 'pregunta' => '¿Por qué es necesario realizar cambio de aceite?', 'respuesta' => 'Con el uso, el aceite se degrada y acumula contaminantes, perdiendo su capacidad de proteger. Cambiarlo a tiempo mantiene el motor limpio y prolonga su vida útil.' ),
			),
		),
		'contact-services'  => array(
			'srv_antetitulo' => 'Servicios',
			'srv_titulo'     => '¿Cómo te ayudamos?',
			'srv_texto'      => 'Elige un servicio y completamos el formulario por ti con el tipo de solicitud correcto.',
			'srv_items'      => array(
				array( 'icono' => 'users', 'titulo' => 'Capacitación técnica', 'texto' => 'Un equipo técnico con experiencia en el rubro, disponible para resolver tus inquietudes sobre lubricantes y capacitar a tu equipo.', 'boton' => 'Solicitar capacitación', 'tipo' => 'capacitacion', 'destacado' => 0 ),
				array( 'icono' => 'flask', 'titulo' => 'Lubrax Express', 'texto' => 'Análisis de aceite usado en nuestro laboratorio, para apoyar los planes de mantención preventiva y correctiva de maquinaria y equipos.', 'boton' => 'Solicitar análisis', 'tipo' => 'analisis', 'destacado' => 0 ),
				array( 'icono' => 'factory', 'titulo' => 'Lubricantes industriales', 'texto' => 'Ventas para industria, minería y flotas: formatos tambor, IBC y granel con asesoría técnica.', 'boton' => 'Contactar ventas', 'tipo' => 'industrial', 'destacado' => 0 ),
				array( 'icono' => 'store', 'titulo' => 'Quiero ser distribuidor', 'texto' => 'Súmate a la red de distribuidores autorizados Lubrax en tu región.', 'boton' => 'Postular', 'tipo' => 'distribuidor', 'destacado' => 1 ),
			),
		),
		'contact-form'      => array(
			'cf_titulo'       => 'Escríbenos',
			'cf_exito_titulo' => '¡Recibimos tu mensaje!',
			'cf_exito_texto'  => 'Te enviaremos el número de caso a tu correo y un ejecutivo te contactará dentro de 24 horas hábiles.',
			'cf_info_titulo'  => 'Planta Lubrax',
			'cf_info_texto'   => 'Comuna de Maipú, Región Metropolitana, Chile.',
			'cf_info_nota'    => 'Las ventas se realizan a través de distribuidores autorizados.',
			'cf_info_enlace'  => $link( 'Buscar distribuidor', lbx_page_url( 'distribuidores' ) ),
			'cf_faq_titulo'   => 'Sobre tu atención',
			'cf_faq_enlace'   => $link( 'Más preguntas frecuentes', lbx_page_url( 'nosotros' ) . '#preguntas-frecuentes' ),
			'cf_faq_items'    => array(
				array( 'pregunta' => '¿Cuánto tarda la respuesta a un reclamo?', 'respuesta' => 'Confirmamos el caso en 24 horas hábiles y entregamos la resolución en un máximo de 15 días, con reposición o nota de crédito cuando procede.' ),
				array( 'pregunta' => '¿Qué datos necesito para abrir un caso?', 'respuesta' => 'Producto, número de lote del envase, factura o pedido, y una descripción de lo ocurrido. Con eso asignamos ejecutivo el mismo día.' ),
				array( 'pregunta' => '¿Puedo comprar directo o debo ir con un distribuidor?', 'respuesta' => 'La venta se hace a través de la red de distribuidores autorizados. Si necesitas volumen o entrega en planta, te asignamos el distribuidor de tu zona.' ),
				array( 'pregunta' => '¿Cómo doy seguimiento a un caso abierto?', 'respuesta' => 'Con el número de caso puedes escribir al correo de atención o llamar a la línea; el mismo ejecutivo continúa el seguimiento hasta el cierre.' ),
			),
		),
	);

	return $defaults[ $block ] ?? array();
}
