<?php
/**
 * Menús con el marcado de la maqueta: <li><a href="…" aria-current="page">…</a></li>, sin clases de WordPress.
 *
 * La página actual y sus ancestros (p. ej. "Productos" en la ficha de un producto) llevan aria-current="page",
 * como en la maqueta.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

class LBX_Nav_Walker extends Walker_Nav_Menu {

	private const CURRENT_CLASSES = array(
		'current-menu-item',
		'current-menu-ancestor',
		'current-menu-parent',
		'current_page_parent',
		'current_page_ancestor',
	);

	public function start_lvl( &$output, $depth = 0, $args = null ) {}

	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item    = $data_object;
		$classes = (array) apply_filters( 'nav_menu_css_class', array_filter( (array) $item->classes ), $item, $args, $depth );
		// Un enlace a un ancla (/nosotros/#preguntas-frecuentes) no es la página actual.
		$current = ! str_contains( (string) $item->url, '#' ) && array_intersect( self::CURRENT_CLASSES, $classes );
		$title   = apply_filters( 'nav_menu_item_title', $item->title, $item, $args, $depth );
		$extra   = '';

		if ( '_blank' === $item->target ) {
			$extra = ' target="_blank" rel="noopener"';
			$title = esc_html( $title ) . lbx_new_tab_label();
		} else {
			$title = esc_html( $title );
		}

		$output .= sprintf(
			'<li><a href="%1$s"%2$s%3$s>%4$s</a>',
			esc_url( $item->url ),
			$current ? ' aria-current="page"' : '',
			$extra,
			$title
		);
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		$output .= "</li>\n";
	}
}

/**
 * WordPress marca la página del blog como ancestro (current_page_parent) también en productos y
 * distribuidores. Solo debe marcarse en entradas, categorías, etiquetas y archivos del blog.
 */
add_filter(
	'nav_menu_css_class',
	function ( array $classes, $item ) {
		$posts_page = (int) get_option( 'page_for_posts' );

		if ( $posts_page && (int) $item->object_id === $posts_page && 'page' === $item->object ) {
			$is_blog = is_home() || is_singular( 'post' ) || is_category() || is_tag() || ( is_date() && ! is_post_type_archive() );
			if ( ! $is_blog ) {
				$classes = array_diff( $classes, array( 'current_page_parent' ) );
			}
		}

		return $classes;
	},
	10,
	2
);

/**
 * Imprime una ubicación de menú dentro de <ul class="…">.
 *
 * Sin menú asignado no imprime nada (salvo que se pase $fallback: lista de [texto, url]).
 */
function lbx_nav_menu( string $location, string $ul_class = '', array $fallback = array() ): string {
	if ( has_nav_menu( $location ) ) {
		return (string) wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => $ul_class,
				'items_wrap'     => $ul_class ? '<ul class="%2$s">%3$s</ul>' : '<ul>%3$s</ul>',
				'depth'          => 1,
				'fallback_cb'    => false,
				'walker'         => new LBX_Nav_Walker(),
				'echo'           => false,
			)
		);
	}

	if ( ! $fallback ) {
		return '';
	}

	$items = '';
	foreach ( $fallback as [ $label, $url ] ) {
		$items .= sprintf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $url ), esc_html( $label ) );
	}

	return $ul_class ? sprintf( '<ul class="%1$s">%2$s</ul>', esc_attr( $ul_class ), $items ) : '<ul>' . $items . '</ul>';
}
