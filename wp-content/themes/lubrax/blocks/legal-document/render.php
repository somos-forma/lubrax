<?php
/**
 * Bloque lubrax/legal-document. Marcado: índice lateral + article.legal de politicas.html y terminos.html.
 *
 * El texto se escribe dentro del bloque con bloques de Gutenberg (títulos, párrafos, listas). Cada título H2
 * abre una sección: <section id="seccion-N" class="legal-block"> con su número, y una entrada en el índice
 * (data-legal-toc, app.js). Un H2 con "Ancla HTML" usa esa ancla como id. Lo que va antes del primer H2 se
 * muestra como introducción. Al final se agrega la sección de contacto (#contacto-legal).
 *
 * Estilos disponibles dentro del bloque: párrafo "Nota destacada" (.legal-note) y lista "Lista con check"
 * (.legal-checks), en inc/core-blocks.php.
 *
 * @package Lubrax
 *
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$lbx_sections = array();
$lbx_intro    = '';
foreach ( preg_split( '#(?=<h2\b)#i', (string) $content ) as $lbx_part ) {
	if ( ! preg_match( '#^<h2\b([^>]*)>(.*?)</h2>(.*)$#is', $lbx_part, $lbx_m ) ) {
		$lbx_intro .= $lbx_part;
		continue;
	}
	$lbx_n          = count( $lbx_sections ) + 1;
	$lbx_sections[] = array(
		'id'    => preg_match( '/\bid="([^"]+)"/', $lbx_m[1], $lbx_id ) ? $lbx_id[1] : 'seccion-' . $lbx_n,
		'title' => trim( $lbx_m[2] ),
		'body'  => $lbx_m[3],
	);
}

$lbx_email   = trim( (string) ( $attributes['contactEmail'] ?? '' ) ) ?: lbx_option( 'email' );
$lbx_contact = ! empty( $attributes['showContact'] ) && $lbx_email;
$lbx_label   = get_the_title( (int) ( $block->context['postId'] ?? get_the_ID() ) ) ?: __( 'Documento legal', 'lubrax' );
$lbx_toc_id  = wp_unique_id( 'tocTitle-' );
?>
<section class="section on-light" aria-label="<?php echo esc_attr( $lbx_label ); ?>">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<?php if ( $lbx_sections ) : ?>
				<aside class="col-sm-4 col-xl-3">
					<nav class="legal-toc" aria-labelledby="<?php echo esc_attr( $lbx_toc_id ); ?>" data-legal-toc>
						<details open>
							<summary id="<?php echo esc_attr( $lbx_toc_id ); ?>"><?php echo esc_html( $attributes['tocTitle'] ?? '' ); ?></summary>
							<ol>
								<?php foreach ( $lbx_sections as $lbx_section ) : ?>
									<li><a href="#<?php echo esc_attr( $lbx_section['id'] ); ?>"><?php echo esc_html( wp_strip_all_tags( $lbx_section['title'] ) ); ?></a></li>
								<?php endforeach; ?>
								<?php if ( $lbx_contact ) : ?>
									<li><a href="#contacto-legal"><?php echo esc_html( $attributes['contactTitle'] ?? '' ); ?></a></li>
								<?php endif; ?>
							</ol>
						</details>
					</nav>
				</aside>
			<?php endif; ?>

			<article class="<?php echo $lbx_sections ? 'col-sm-8 col-xl-9' : 'col-12'; ?> legal">
				<?php if ( '' !== trim( $lbx_intro ) ) : ?>
					<div class="legal-block"><?php echo $lbx_intro; // phpcs:ignore WordPress.Security.EscapeOutput -- contenido de bloques. ?></div>
				<?php endif; ?>
				<?php foreach ( $lbx_sections as $lbx_i => $lbx_section ) : ?>
					<section id="<?php echo esc_attr( $lbx_section['id'] ); ?>" class="legal-block">
						<h2><span class="legal-num"><?php echo esc_html( ( $lbx_i + 1 ) . '.' ); ?></span> <?php echo wp_kses_post( $lbx_section['title'] ); ?></h2>
						<?php echo $lbx_section['body']; // phpcs:ignore WordPress.Security.EscapeOutput -- contenido de bloques. ?>
					</section>
				<?php endforeach; ?>
				<?php if ( $lbx_contact ) : ?>
					<section id="contacto-legal" class="legal-block">
						<h2><span class="legal-num"><?php echo esc_html( ( count( $lbx_sections ) + 1 ) . '.' ); ?></span> <?php echo esc_html( $attributes['contactTitle'] ?? '' ); ?></h2>
						<div class="legal-contact">
							<?php if ( ! empty( $attributes['contactText'] ) ) : ?>
								<p><?php echo esc_html( $attributes['contactText'] ); ?></p>
							<?php endif; ?>
							<a href="<?php echo esc_url( 'mailto:' . $lbx_email ); ?>" class="btn btn-lbx-dark btn-sm"><?php echo lbx_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $lbx_email ); ?></a>
						</div>
					</section>
				<?php endif; ?>
			</article>
		</div>
	</div>
</section>
