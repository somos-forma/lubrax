<?php
/**
 * Bloque SCF lubrax/faq. Marcado: section.faq + tpl-faq de nosotros.html (buscador: data-faq-search, en app.js).
 * Publica el JSON-LD FAQPage si el campo "Datos estructurados" está activo.
 *
 * @package Lubrax
 *
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$lbx_d     = lbx_block_defaults( 'faq' );
$lbx_items = ( function_exists( 'get_field' ) ? get_field( 'faq_items' ) : null ) ?: $lbx_d['faq_items'];
$lbx_items = array_values( array_filter( $lbx_items, fn( $item ) => ! empty( $item['pregunta'] ) && ! empty( $item['respuesta'] ) ) );
$lbx_list  = wp_unique_id( 'faqList-' );
$lbx_title = wp_unique_id( 'faqTitle-' );
$lbx_link  = lbx_link_value( lbx_block_value( 'faq_enlace', 'faq' ) );
$lbx_text  = (string) lbx_block_value( 'faq_texto', 'faq' );
?>
<section class="section faq on-light" id="preguntas-frecuentes" aria-labelledby="<?php echo esc_attr( $lbx_title ); ?>">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<div class="col-lg-4">
				<p class="t-kicker" data-reveal="up"><?php echo esc_html( lbx_block_value( 'faq_antetitulo', 'faq' ) ); ?></p>
				<h2 class="t-display" id="<?php echo esc_attr( $lbx_title ); ?>" data-reveal="up"><?php echo esc_html( lbx_block_value( 'faq_titulo', 'faq' ) ); ?></h2>
				<?php if ( $lbx_text ) : ?>
					<p class="text-body-secondary" data-reveal="up"><?php echo esc_html( $lbx_text ); ?></p>
				<?php endif; ?>
				<?php echo lbx_link_html( $lbx_link, 'link-arrow', lbx_icon( 'arrow-right' ), ' data-reveal="up"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="col-lg-8">
				<div class="faq__search" data-reveal="up">
					<label for="<?php echo esc_attr( $lbx_list ); ?>-search" class="visually-hidden"><?php esc_html_e( 'Buscar en preguntas frecuentes', 'lubrax' ); ?></label>
					<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<input class="form-control" type="search" id="<?php echo esc_attr( $lbx_list ); ?>-search" placeholder="<?php esc_attr_e( 'Buscar una pregunta', 'lubrax' ); ?>" autocomplete="off" data-faq-search="#<?php echo esc_attr( $lbx_list ); ?>">
				</div>
				<div class="accordion" id="<?php echo esc_attr( $lbx_list ); ?>">
					<?php foreach ( $lbx_items as $lbx_i => $lbx_item ) : ?>
						<?php
						$lbx_uid  = $lbx_list . '-' . ( $lbx_i + 1 );
						$lbx_open = 0 === $lbx_i;
						?>
						<div class="accordion-item" data-reveal="up">
							<h3 class="accordion-header">
								<button class="accordion-button<?php echo $lbx_open ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $lbx_uid ); ?>" aria-expanded="<?php echo $lbx_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $lbx_uid ); ?>"><?php echo esc_html( $lbx_item['pregunta'] ); ?></button>
							</h3>
							<div id="<?php echo esc_attr( $lbx_uid ); ?>" class="accordion-collapse collapse<?php echo $lbx_open ? ' show' : ''; ?>" data-bs-parent="#<?php echo esc_attr( $lbx_list ); ?>">
								<div class="accordion-body"><?php echo nl2br( esc_html( $lbx_item['respuesta'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="mt-3" data-faq-empty hidden><?php esc_html_e( 'No encontramos preguntas con esa búsqueda.', 'lubrax' ); ?> <a href="<?php echo esc_url( lbx_contact_url( 'consulta' ) ); ?>"><?php esc_html_e( 'Escríbenos', 'lubrax' ); ?></a> <?php esc_html_e( 'y te respondemos.', 'lubrax' ); ?></p>
			</div>
		</div>
	</div>
</section>
<?php
if ( $lbx_items && lbx_block_value( 'faq_schema', 'faq' ) && empty( $is_preview ) ) {
	$lbx_schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map(
			fn( $item ) => array(
				'@type'          => 'Question',
				'name'           => $item['pregunta'],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $item['respuesta'] ),
			),
			$lbx_items
		),
	);
	printf( '<script type="application/ld+json">%s</script>', wp_json_encode( $lbx_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) );
}
