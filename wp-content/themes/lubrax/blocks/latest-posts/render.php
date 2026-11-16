<?php
/**
 * Bloque SCF lubrax/latest-posts. Últimas entradas con la tarjeta del blog (tpl-post-card).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

$lbx_posts = get_posts(
	array(
		'post_type'      => 'post',
		'posts_per_page' => max( 1, (int) lbx_block_value( 'news_cantidad', 'latest-posts' ) ),
	)
);
if ( ! $lbx_posts ) {
	return;
}

$lbx_id   = wp_unique_id( 'newsTitle-' );
$lbx_link = lbx_link_value( lbx_block_value( 'news_enlace', 'latest-posts' ) );
?>
<section class="section bg-paper on-light" aria-labelledby="<?php echo esc_attr( $lbx_id ); ?>">
	<div class="container-xxl">
		<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
			<div data-reveal="up">
				<p class="t-kicker"><?php echo esc_html( lbx_block_value( 'news_antetitulo', 'latest-posts' ) ); ?></p>
				<h2 class="mb-0" id="<?php echo esc_attr( $lbx_id ); ?>"><?php echo esc_html( lbx_block_value( 'news_titulo', 'latest-posts' ) ); ?></h2>
			</div>
			<?php echo lbx_link_html( $lbx_link, 'link-arrow', lbx_icon( 'arrow-right' ), ' data-reveal="up"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<ul class="post-grid" data-reveal-stagger="0.06">
			<?php
			foreach ( $lbx_posts as $lbx_post ) {
				echo lbx_post_card( $lbx_post ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</ul>
	</div>
</section>
