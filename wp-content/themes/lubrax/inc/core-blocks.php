<?php
/**
 * Bloques core con el diseño de la maqueta.
 *
 * - Botón: estilos "Amarillo", "Verde", "Contorno amarillo" y "Contorno verde" → clases btn btn-lbx… de la maqueta.
 * - Clase reveal-up|down|left|right|zoom|clip en cualquier bloque (Avanzado > Clases CSS) → data-reveal (animación).
 * - Título con clase reveal-title: cada línea (Mayús + Intro) entra por separado, como los títulos de la maqueta.
 * - Párrafo con clase stat__value y un número ("+180", "8") → contador animado (data-count-to).
 * - Párrafo con estilo "Nota destacada" → div.legal-note con ícono; lista con estilo "Lista con check" →
 *   ul.legal-checks (textos legales, bloque Documento legal).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

const LBX_BUTTON_STYLES = array(
	'lbx'              => 'Amarillo',
	'lbx-dark'         => 'Verde',
	'lbx-outline'      => 'Contorno amarillo',
	'lbx-outline-dark' => 'Contorno verde',
);

add_action(
	'init',
	function () {
		foreach ( LBX_BUTTON_STYLES as $style => $label ) {
			register_block_style(
				'core/button',
				array(
					'name'  => $style,
					'label' => $label,
				)
			);
		}
		// Los estilos "Relleno" y "Contorno" de core se registran en el editor: se quitan en assets/js/editor/blocks.js.

		register_block_style( 'core/paragraph', array( 'name' => 'lbx-note', 'label' => __( 'Nota destacada', 'lubrax' ) ) );
		register_block_style( 'core/list', array( 'name' => 'lbx-checks', 'label' => __( 'Lista con check', 'lubrax' ) ) );
	}
);

/**
 * Botón con estilo Lubrax: <a class="btn btn-lbx-dark"> (sin las clases de botón de WordPress).
 */
add_filter(
	'render_block_core/button',
	function ( string $html, array $block ) {
		if ( ! preg_match( '/\bis-style-(lbx(?:-outline)?(?:-dark)?)\b/', $block['attrs']['className'] ?? '', $m ) ) {
			return $html;
		}
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) ) {
			$tags->remove_class( 'wp-block-button__link' );
			$tags->remove_class( 'wp-element-button' );
			$tags->add_class( 'btn' );
			$tags->add_class( 'btn-' . $m[1] );
		}
		return $tags->get_updated_html();
	},
	10,
	2
);

/**
 * Páginas armadas con bloques (plantilla "Página con patrones", clase lbx-sections): sin la clase entry-content
 * que agrega core/post-content, porque main.css la usa para el cuerpo de los artículos del blog
 * (h2 verdes de 1,5rem, texto justificado) y alteraría los títulos de las secciones.
 */
add_filter(
	'render_block_core/post-content',
	function ( string $html ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() && $tags->has_class( 'lbx-sections' ) ) {
			$tags->remove_class( 'entry-content' );
			return $tags->get_updated_html();
		}
		return $html;
	}
);

/**
 * Animaciones de scroll y contadores a partir de clases CSS.
 */
add_filter(
	'render_block',
	function ( string $html, array $block ) {
		$class = $block['attrs']['className'] ?? '';
		if ( '' === $class || ! str_starts_with( (string) $block['blockName'], 'core/' ) ) {
			return $html;
		}

		// Título animado por líneas: los <br> separan las líneas.
		if ( 'core/heading' === $block['blockName'] && preg_match( '/\breveal-title\b/', $class ) ) {
			$html = preg_replace_callback(
				'#^(\s*<h[1-6][^>]*>)(.*)(</h[1-6]>\s*)$#s',
				function ( $m ) {
					$lines = preg_split( '#<br\s*/?>#i', $m[2] );
					$lines = array_map( fn( $line ) => '<span class="line"><span>' . trim( $line ) . '</span></span>', array_filter( $lines, fn( $l ) => '' !== trim( wp_strip_all_tags( $l ) ) ) );
					return $m[1] . implode( ' ', $lines ) . $m[3];
				},
				$html
			);
		}

		$tags = new WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() ) {
			return $html;
		}

		if ( preg_match( '/\breveal-(up|down|left|right|zoom|clip|title)\b/', $class, $m ) ) {
			$tags->set_attribute( 'data-reveal', $m[1] );
			$tags->remove_class( 'reveal-' . $m[1] );
		}

		// Contador: "+180" → data-count-to="180" data-count-prefix="+". "ISO 4406" o "24 h" no se animan.
		if ( preg_match( '/\bstat__value\b/', $class ) && preg_match( '/^\s*([+~]?)(\d+)\s*$/u', wp_strip_all_tags( $html ), $n ) ) {
			$tags->set_attribute( 'data-count-to', $n[2] );
			if ( '' !== $n[1] ) {
				$tags->set_attribute( 'data-count-prefix', $n[1] );
			}
		}

		return $tags->get_updated_html();
	},
	10,
	2
);

/**
 * Párrafo "Nota destacada": <div class="legal-note">{ícono}<p>…</p></div> (maqueta politicas.html).
 */
add_filter(
	'render_block_core/paragraph',
	function ( string $html, array $block ) {
		if ( ! preg_match( '/\bis-style-lbx-note\b/', $block['attrs']['className'] ?? '' ) ) {
			return $html;
		}
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag( 'p' ) ) {
			$tags->remove_class( 'is-style-lbx-note' );
		}
		return '<div class="legal-note">' . lbx_icon( 'info' ) . trim( $tags->get_updated_html() ) . '</div>';
	},
	10,
	2
);

/**
 * Lista "Lista con check": <ul class="legal-checks">.
 */
add_filter(
	'render_block_core/list',
	function ( string $html, array $block ) {
		if ( ! preg_match( '/\bis-style-lbx-checks\b/', $block['attrs']['className'] ?? '' ) ) {
			return $html;
		}
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			$tags->add_class( 'legal-checks' );
		}
		return $tags->get_updated_html();
	},
	10,
	2
);
