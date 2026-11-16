<?php
/**
 * Bloque lubrax/post-article. Marcado: blogarticle.html (page-hero.blog + article.entry.entry-single + barra lateral).
 * El cuerpo es el contenido de la entrada (bloques de Gutenberg) dentro de .entry-content.
 *
 * @package Lubrax
 *
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$lbx_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( 'post' !== get_post_type( $lbx_id ) ) {
	// Editor del sitio (plantilla sin entrada): vista previa con la entrada más reciente.
	$lbx_latest = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	$lbx_id     = (int) ( $lbx_latest[0] ?? 0 );
	if ( ! $lbx_id ) {
		return;
	}
}

$lbx_post     = get_post( $lbx_id );
$lbx_category = lbx_post_category( $lbx_id );
$lbx_tags     = get_the_tags( $lbx_id ) ?: array();
$lbx_blog_url = lbx_page_url( 'blog' );
$lbx_image    = (int) get_post_thumbnail_id( $lbx_id );
$lbx_title    = get_the_title( $lbx_id );

// Contenido: mismos filtros que el bloque core/post-content.
$lbx_content = apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', get_the_content( null, false, $lbx_post ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
?>
<section class="page-hero blog" aria-labelledby="pageTitle">
	<div class="page-hero__bg">
		<img src="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?>" srcset="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero-768.webp' ) ); ?> 768w, <?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?> 1600w" sizes="100vw" alt="" width="1600" height="893" decoding="async">
	</div>
	<div class="container-xxl">
		<nav aria-label="<?php esc_attr_e( 'Ruta de navegación', 'lubrax' ); ?>" data-reveal="up">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'lubrax' ); ?></a></li>
				<li class="breadcrumb-item"><a href="<?php echo esc_url( $lbx_blog_url ); ?>"><?php echo esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ); ?></a></li>
				<?php if ( $lbx_category ) : ?>
					<li class="breadcrumb-item"><a href="<?php echo esc_url( get_category_link( $lbx_category ) ); ?>"><?php echo esc_html( $lbx_category->name ); ?></a></li>
				<?php endif; ?>
				<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $lbx_title ); ?></li>
			</ol>
		</nav>
		<h1 class="text-white t-display pb-3" id="pageTitle" data-reveal="title"><span class="line"><span><?php echo esc_html( $lbx_title ); ?></span></span></h1>
		<?php if ( has_excerpt( $lbx_id ) ) : ?>
			<p class="page-hero__lead t-lead pb-5" data-reveal="up" data-reveal-delay="0.15"><?php echo esc_html( get_the_excerpt( $lbx_id ) ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="section on-light rltv" aria-label="<?php esc_attr_e( 'Artículo', 'lubrax' ); ?>">
	<div class="container-xxl">
		<div class="row g-4 g-lg-5">
			<div class="col-lg-9 entries">
				<article class="entry entry-single">
					<?php if ( $lbx_image ) : ?>
						<div class="entry-img" data-reveal="zoom">
							<?php
							echo wp_get_attachment_image(
								$lbx_image,
								'large',
								false,
								array(
									'class'         => 'img-fluid',
									'sizes'         => '(min-width: 1200px) 860px, (min-width: 992px) 66vw, 100vw',
									'fetchpriority' => 'high',
									'loading'       => false,
									'decoding'      => 'async',
								)
							);
							?>
						</div>
					<?php endif; ?>

					<div class="entry-meta">
						<ul>
							<?php $lbx_author = get_the_author_meta( 'display_name', (int) $lbx_post->post_author ); ?>
							<?php if ( $lbx_author ) : ?>
								<li><?php echo lbx_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span><?php echo esc_html( $lbx_author ); ?></span></li>
							<?php endif; ?>
							<li><?php echo lbx_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $lbx_id ) ); ?>"><?php echo esc_html( lbx_post_date( $lbx_id, true ) ); ?></time></li>
							<?php /* translators: %d: minutos. */ ?>
							<li><?php echo lbx_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span><?php echo esc_html( sprintf( __( '%d min de lectura', 'lubrax' ), lbx_reading_time( $lbx_id ) ) ); ?></span></li>
							<?php if ( $lbx_category ) : ?>
								<li><?php echo lbx_icon( 'layers' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <a href="<?php echo esc_url( get_category_link( $lbx_category ) ); ?>"><?php echo esc_html( $lbx_category->name ); ?></a></li>
							<?php endif; ?>
						</ul>
					</div>

					<div class="entry-content" data-reveal="up">
						<?php echo $lbx_content; // phpcs:ignore WordPress.Security.EscapeOutput -- contenido filtrado por the_content. ?>
					</div>

					<footer class="entry-footer">
						<?php if ( $lbx_tags ) : ?>
							<h2 class="visually-hidden"><?php esc_html_e( 'Etiquetas del artículo', 'lubrax' ); ?></h2>
							<ul class="tag-cloud">
								<?php foreach ( $lbx_tags as $lbx_tag ) : ?>
									<li><a href="<?php echo esc_url( get_tag_link( $lbx_tag ) ); ?>">#<?php echo esc_html( $lbx_tag->name ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<a class="link-arrow" href="<?php echo esc_url( $lbx_blog_url ); ?>"><?php echo lbx_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Volver al blog', 'lubrax' ); ?></a>
					</footer>
				</article>
			</div>

			<?php echo lbx_blog_sidebar(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
</section>
