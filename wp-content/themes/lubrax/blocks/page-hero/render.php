<?php
/**
 * Bloque lubrax/page-hero. Marcado: <section class="page-hero"> de la maqueta (politicas.html).
 *
 * @package Lubrax
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

// En archivos, el contexto postId apunta a la primera entrada del listado: solo se usa en contenido singular
// y en la vista previa del editor (REST con ?post_id=).
$lbx_post_id = ( is_singular() || wp_is_serving_rest_request() )
	? (int) ( $block->context['postId'] ?? get_the_ID() )
	: 0;
$lbx_title   = lbx_page_hero_title( $lbx_post_id );
$lbx_lead    = trim( (string) ( $attributes['lead'] ?? '' ) );

if ( ! $lbx_lead && $lbx_post_id && has_excerpt( $lbx_post_id ) ) {
	$lbx_lead = get_the_excerpt( $lbx_post_id );
}

// Migas: Inicio / ancestros / página actual.
$lbx_crumbs = array( array( __( 'Inicio', 'lubrax' ), home_url( '/' ) ) );
if ( $lbx_post_id ) {
	foreach ( array_reverse( get_post_ancestors( $lbx_post_id ) ) as $lbx_ancestor ) {
		$lbx_crumbs[] = array( get_the_title( $lbx_ancestor ), get_permalink( $lbx_ancestor ) );
	}
}

$lbx_thumb = $lbx_post_id ? (int) get_post_thumbnail_id( $lbx_post_id ) : 0;
?>
<section class="page-hero<?php echo ! empty( $attributes['compact'] ) ? ' page-hero--compact' : ''; ?>" aria-labelledby="pageTitle">
	<div class="page-hero__bg">
		<?php if ( $lbx_thumb ) : ?>
			<?php
			echo wp_get_attachment_image(
				$lbx_thumb,
				'lbx-hero',
				false,
				array(
					'alt'           => '',
					'sizes'         => '100vw',
					'decoding'      => 'async',
					'loading'       => false,
					'fetchpriority' => 'high',
				)
			);
			?>
		<?php else : ?>
			<img src="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?>" srcset="<?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero-768.webp' ) ); ?> 768w, <?php echo esc_url( lbx_asset( 'img/pages/distribuidores-hero.webp' ) ); ?> 1600w" sizes="100vw" alt="" width="1600" height="893" decoding="async" fetchpriority="high">
		<?php endif; ?>
	</div>
	<div class="container-xxl">
		<h1 class="page-hero__title t-display" id="pageTitle" data-reveal="title"><span class="line"><span><?php echo esc_html( $lbx_title ); ?></span></span></h1>
		<nav aria-label="<?php esc_attr_e( 'Ruta de navegación', 'lubrax' ); ?>" data-reveal="up" data-reveal-delay="0.15">
			<ol class="breadcrumb">
				<?php foreach ( $lbx_crumbs as [ $lbx_label, $lbx_url ] ) : ?>
					<li class="breadcrumb-item"><a href="<?php echo esc_url( $lbx_url ); ?>"><?php echo esc_html( $lbx_label ); ?></a></li>
				<?php endforeach; ?>
				<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html( $lbx_title ); ?></li>
			</ol>
		</nav>
		<?php if ( $lbx_lead ) : ?>
			<p class="page-hero__lead t-lead mb-0" data-reveal="up" data-reveal-delay="0.25"><?php echo esc_html( $lbx_lead ); ?></p>
		<?php endif; ?>
		<?php
		// Páginas legales: "Última actualización: octubre de 2026" (fecha AAAA-MM-DD o AAAA-MM).
		$lbx_updated = (string) ( $attributes['updated'] ?? '' );
		$lbx_updated = '' === $lbx_updated ? false : date_create_immutable( preg_match( '/^\d{4}-\d{2}$/', $lbx_updated ) ? $lbx_updated . '-01' : $lbx_updated, wp_timezone() );
		?>
		<?php if ( $lbx_updated ) : ?>
			<p class="legal-updated" data-reveal="up" data-reveal-delay="0.3"><?php echo lbx_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Última actualización:', 'lubrax' ); ?> <time datetime="<?php echo esc_attr( $lbx_updated->format( 'Y-m' ) ); ?>"><?php echo esc_html( mb_strtolower( wp_date( 'F \d\e Y', $lbx_updated->getTimestamp() ) ) ); ?></time></p>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['channels'] ) ) : ?>
			<?php
			// Canales de contacto.html: Opciones del tema > Contacto.
			$lbx_channels = array_filter(
				array(
					array( 'headset', __( 'Atención al cliente', 'lubrax' ), lbx_option( 'phone' ), lbx_tel_href( lbx_option( 'phone' ) ), lbx_option( 'phone_note' ), false ),
					array( 'mail', __( 'Correo de atención', 'lubrax' ), lbx_option( 'email' ), 'mailto:' . lbx_option( 'email' ), lbx_option( 'email_note' ), false ),
					array( 'whatsapp', __( 'WhatsApp', 'lubrax' ), lbx_option( 'whatsapp' ), lbx_whatsapp_url(), lbx_option( 'whatsapp_note' ), true ),
				),
				fn( $channel ) => '' !== $channel[2]
			);
			?>
			<div class="channels mt-4 mt-lg-5" data-reveal="up" data-reveal-delay="0.3">
				<?php foreach ( $lbx_channels as [ $lbx_icon, $lbx_label, $lbx_value, $lbx_href, $lbx_note, $lbx_blank ] ) : ?>
					<a class="channel" href="<?php echo esc_url( $lbx_href ); ?>"<?php echo $lbx_blank ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php echo lbx_icon( $lbx_icon ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><span class="channel__label"><?php echo esc_html( $lbx_label ); ?></span><span class="channel__value"><?php echo esc_html( $lbx_value ); ?></span><span class="channel__note"><?php echo esc_html( $lbx_note ); ?><?php echo $lbx_blank ? lbx_new_tab_label() : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
