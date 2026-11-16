<?php
/**
 * Bloque SCF lubrax/hero-slider. Marcado: section.hero de index.html (lo maneja slider.js).
 *
 * La primera diapositiva lleva fetchpriority="high" y se precarga en el <head>; las demás, loading="lazy".
 *
 * @package Lubrax
 *
 * @var array $block
 * @var bool  $is_preview
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'lbx-slider' );

$is_preview = ! empty( $is_preview );

$lbx_defaults = lbx_block_defaults( 'hero-slider' );
$lbx_slides   = function_exists( 'get_field' ) ? get_field( 'hero_slides' ) : null;
$lbx_slides   = $lbx_slides ?: $lbx_defaults['hero_slides'];
$lbx_total    = count( $lbx_slides );
$lbx_interval = (int) lbx_block_value( 'hero_intervalo', 'hero-slider' );
$lbx_h1       = (string) lbx_block_value( 'hero_h1', 'hero-slider' );

// Precarga de la imagen de la primera diapositiva (el <head> aún no se imprime al renderizar la plantilla).
$lbx_first = $lbx_slides[0] ?? array();
if ( ! $is_preview && ! did_action( 'wp_head' ) ) {
	$lbx_first_id = (int) ( $lbx_first['imagen'] ?? 0 );
	$lbx_preload  = $lbx_first_id && wp_attachment_is_image( $lbx_first_id )
		? array(
			'href'   => wp_get_attachment_image_url( $lbx_first_id, 'full' ),
			'srcset' => (string) wp_get_attachment_image_srcset( $lbx_first_id, 'full' ),
		)
		: array(
			'href'   => lbx_asset( 'img/' . $lbx_defaults['hero_slides'][0]['asset']['src'] ),
			'srcset' => implode( ', ', array_map( fn( $f, $w ) => lbx_asset( 'img/' . $f ) . " {$w}w", array_keys( $lbx_defaults['hero_slides'][0]['asset']['srcset'] ), $lbx_defaults['hero_slides'][0]['asset']['srcset'] ) ),
		);
	$lbx_sizes = ! empty( $lbx_first['producto'] ) ? '(min-width: 768px) 560px, 80vw' : '100vw';
	add_action(
		'wp_head',
		function () use ( $lbx_preload, $lbx_sizes ) {
			printf(
				'<link rel="preload" as="image" href="%1$s"%2$s imagesizes="%3$s" fetchpriority="high">' . "\n",
				esc_url( $lbx_preload['href'] ),
				$lbx_preload['srcset'] ? ' imagesrcset="' . esc_attr( $lbx_preload['srcset'] ) . '"' : '',
				esc_attr( $lbx_sizes )
			);
		},
		2
	);
}
?>
<section class="hero" data-hero data-interval="<?php echo esc_attr( (string) max( 3000, $lbx_interval ) ); ?>" aria-roledescription="carrusel" aria-labelledby="heroTitle">
	<h1 class="visually-hidden" id="heroTitle"><?php echo esc_html( $lbx_h1 ); ?></h1>
	<div class="hero__track">
		<?php foreach ( array_values( $lbx_slides ) as $lbx_i => $lbx_slide ) : ?>
			<?php
			$lbx_product = ! empty( $lbx_slide['producto'] );
			$lbx_asset   = $lbx_slide['asset'] ?? $lbx_defaults['hero_slides'][ min( $lbx_i, 2 ) ]['asset'];
			$lbx_classes = 'hero-slide' . ( 0 === $lbx_i ? ' is-active' : '' ) . ( $lbx_product ? ' hero-slide--product' : '' );
			?>
			<?php /* translators: 1: número de diapositiva, 2: total. */ ?>
			<article class="<?php echo esc_attr( $lbx_classes ); ?>" role="group" aria-roledescription="diapositiva" aria-label="<?php echo esc_attr( sprintf( __( '%1$d de %2$d', 'lubrax' ), $lbx_i + 1, $lbx_total ) ); ?>">
				<div class="hero-slide__media">
					<?php
					echo lbx_image_or_asset( // phpcs:ignore WordPress.Security.EscapeOutput
						(int) ( $lbx_slide['imagen'] ?? 0 ),
						$lbx_asset,
						array(
							'alt'           => $lbx_asset['alt'] ?? '',
							'sizes'         => $lbx_product ? '(min-width: 768px) 560px, 80vw' : '100vw',
							'loading'       => 0 === $lbx_i ? 'eager' : 'lazy',
							'fetchpriority' => 0 === $lbx_i ? 'high' : false,
						),
						'full'
					);
					?>
				</div>
				<div class="hero-slide__content">
					<div class="container-xxl">
						<div class="hero-slide__inner">
							<?php if ( ! empty( $lbx_slide['antetitulo'] ) ) : ?>
								<p class="t-kicker" data-hero-item><?php echo esc_html( $lbx_slide['antetitulo'] ); ?></p>
							<?php endif; ?>
							<h2 class="hero-slide__title t-display" data-hero-item><?php echo esc_html( $lbx_slide['titulo'] ?? '' ); ?></h2>
							<?php if ( ! empty( $lbx_slide['texto'] ) ) : ?>
								<p class="hero-slide__text" data-hero-item><?php echo esc_html( $lbx_slide['texto'] ); ?></p>
							<?php endif; ?>
							<?php $lbx_button = lbx_link_value( $lbx_slide['boton'] ?? null ); ?>
							<?php if ( $lbx_button ) : ?>
								<div data-hero-item>
									<?php echo lbx_link_html( $lbx_button, 'btn btn-lbx', lbx_icon( 'arrow-right', 'icon icon-arrow' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<?php if ( $lbx_total > 1 ) : ?>
		<div class="hero__controls">
			<div class="container-xxl">
				<div class="hero-counter">
					<span class="hero-counter__current" data-hero-current aria-hidden="true">01</span>
					<span class="hero-counter__bar" aria-hidden="true"><span></span></span>
					<button type="button" class="hero-counter__next" data-hero-next-num aria-label="<?php esc_attr_e( 'Ir a la diapositiva 2', 'lubrax' ); ?>">02</button>
				</div>
				<div class="hero__nav">
					<button type="button" class="btn btn-icon" data-hero-toggle aria-label="<?php esc_attr_e( 'Pausar el carrusel', 'lubrax' ); ?>"><?php echo lbx_icon( 'pause' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<button type="button" class="btn btn-icon" data-hero-prev aria-label="<?php esc_attr_e( 'Diapositiva anterior', 'lubrax' ); ?>"><?php echo lbx_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<button type="button" class="btn btn-icon" data-hero-next aria-label="<?php esc_attr_e( 'Diapositiva siguiente', 'lubrax' ); ?>"><?php echo lbx_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				</div>
			</div>
		</div>
	<?php endif; ?>
</section>
