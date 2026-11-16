<?php
/**
 * Bloque lubrax/distributor-locator. Marcado: section.locator y modal #distProfile de distribuidores.html.
 * locator.js (con Leaflet) filtra, dibuja el mapa y abre los perfiles a partir de este HTML.
 *
 * Parámetros de URL (los lee locator.js): ?producto={slug} · ?region=… · ?vista=mapa|listado · ?perfil={slug}
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'lbx-leaflet' );
wp_enqueue_script( 'lbx-locator' );

$lbx_distributors = lbx_get_distributors();
$lbx_selected     = isset( $_GET['producto'] ) ? sanitize_title( wp_unslash( $_GET['producto'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<section class="locator on-light" id="locator" aria-labelledby="locatorTitle">
	<div class="container-xxl">
		<h2 class="visually-hidden" id="locatorTitle"><?php esc_html_e( 'Buscador de distribuidores', 'lubrax' ); ?></h2>
		<form class="locator__filters" role="search" aria-label="<?php esc_attr_e( 'Filtrar distribuidores', 'lubrax' ); ?>" data-reveal="up">
			<div>
				<label class="form-label" for="locSearch"><?php esc_html_e( 'Buscar', 'lubrax' ); ?></label>
				<div class="catalog__search">
					<?php echo lbx_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<input class="form-control" type="search" id="locSearch" placeholder="<?php esc_attr_e( 'Ciudad, comuna o nombre', 'lubrax' ); ?>" autocomplete="off" enterkeyhint="search">
				</div>
			</div>
			<div>
				<label class="form-label" for="locRegion"><?php esc_html_e( 'Región', 'lubrax' ); ?></label>
				<select class="form-select" id="locRegion"><option value=""><?php esc_html_e( 'Todas las regiones', 'lubrax' ); ?></option></select>
			</div>
			<div>
				<label class="form-label" for="locProduct"><?php esc_html_e( 'Producto', 'lubrax' ); ?></label>
				<select class="form-select" id="locProduct">
					<option value=""><?php esc_html_e( 'Todos los productos', 'lubrax' ); ?></option>
					<?php echo function_exists( 'lbx_product_options_html' ) ? lbx_product_options_html( $lbx_selected ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escapado al generarse. ?>
				</select>
			</div>
			<div>
				<button type="button" class="btn btn-lbx-dark w-100" data-loc-geolocate><?php echo lbx_icon( 'locate' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Cerca de mí', 'lubrax' ); ?></button>
			</div>
		</form>

		<div class="locator__bar">
			<p class="catalog__count mb-0" data-locator-count aria-live="polite"></p>
			<div class="locator__views">
				<span class="form-label mb-0" id="locViewLabel"><?php esc_html_e( 'Vista', 'lubrax' ); ?></span>
				<div class="locator-tabs view-toggle btn-group" role="group" aria-labelledby="locViewLabel">
					<button type="button" class="btn" data-loc-view="map" aria-pressed="true"><?php echo lbx_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Mapa', 'lubrax' ); ?></button>
					<button type="button" class="btn" data-loc-view="list" aria-pressed="false"><?php echo lbx_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Listado', 'lubrax' ); ?></button>
				</div>
			</div>
		</div>

		<div class="locator__layout" data-view="map">
			<ul class="locator__list" id="distList">
				<?php
				foreach ( $lbx_distributors as $lbx_distributor ) {
					echo lbx_distributor_card( $lbx_distributor ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</ul>
			<div class="locator__map-wrap">
				<div class="locator__map" id="distMap" role="region" aria-label="<?php esc_attr_e( 'Mapa de distribuidores', 'lubrax' ); ?>" data-tile-url="<?php echo esc_attr( lbx_option( 'tile_url' ) ); ?>" data-tile-attribution="<?php echo esc_attr( wp_kses( lbx_option( 'tile_attribution' ), array( 'a' => array( 'href' => array() ) ) ) ); ?>" data-tile-max-zoom="<?php echo esc_attr( lbx_option( 'tile_max_zoom' ) ); ?>"></div>
				<p class="locator__notice" hidden><?php esc_html_e( 'Vista local: el mapa base solo carga desde un servidor (http/https). Los marcadores y filtros funcionan igual.', 'lubrax' ); ?></p>
				<p class="locator__fallback" hidden><?php esc_html_e( 'No se pudo cargar el mapa. Usa el listado para encontrar tu distribuidor.', 'lubrax' ); ?></p>
			</div>
		</div>
	</div>
</section>

<div class="modal fade modal-dark dist-profile" id="distProfile" tabindex="-1" aria-labelledby="distProfileTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
		<div class="modal-content">
			<div class="modal-header border-0">
				<div class="dist-profile__head">
					<p class="t-kicker mb-1" data-profile="label"></p>
					<h2 class="modal-title h3" id="distProfileTitle" data-profile="name"><?php esc_html_e( 'Distribuidor', 'lubrax' ); ?></h2>
					<div class="dist-profile__chips">
						<span class="dist-chip dist-chip--type" data-profile="purchase"></span>
						<span class="dist-chip dist-chip--mine" data-profile="mine" hidden><?php echo lbx_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Tu distribuidor', 'lubrax' ); ?></span>
						<span class="dist-profile__distance" data-profile="distance"></span>
					</div>
				</div>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Cerrar', 'lubrax' ); ?>"></button>
			</div>
			<div class="modal-body">
				<div class="row g-4 m-0">
					<div class="col-md-5">
						<dl class="dist-profile__info">
							<div><dt><?php echo lbx_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Dirección', 'lubrax' ); ?></dt><dd data-profile="address"></dd></div>
							<div><dt><?php echo lbx_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Horario', 'lubrax' ); ?></dt><dd data-profile="hours"></dd></div>
							<div><dt><?php echo lbx_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Teléfono', 'lubrax' ); ?></dt><dd><a data-profile="phone"></a></dd></div>
							<div><dt><?php echo lbx_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Sitio web', 'lubrax' ); ?></dt><dd><a data-profile="web" target="_blank" rel="noopener"></a></dd></div>
							<div><dt><?php echo lbx_icon( 'package' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Envases', 'lubrax' ); ?></dt><dd data-profile="packs"></dd></div>
						</dl>
					</div>
					<div class="col-md-7">
						<h3 class="dist-profile__subtitle"><?php esc_html_e( 'Productos disponibles', 'lubrax' ); ?> <span data-profile="count"></span></h3>
						<p class="dist-profile__match" data-profile="match" hidden></p>
						<div class="dist-profile__groups" data-profile="products"></div>
					</div>
				</div>
			</div>
			<div class="modal-footer border-0">
				<button type="button" class="btn btn-lbx" data-profile-action="select"><?php echo lbx_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span data-profile="select-label"><?php esc_html_e( 'Elegir distribuidor', 'lubrax' ); ?></span></button>
				<a class="btn btn-lbx-outline" href="#" data-profile="route" target="_blank" rel="noopener"><?php echo lbx_icon( 'navigation' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Ver ruta', 'lubrax' ); ?><span class="visually-hidden"> <?php esc_html_e( '(abre en una pestaña nueva)', 'lubrax' ); ?></span></a>
				<div class="dist-profile__links">
					<button type="button" class="link-arrow link-arrow--yellow btn-reset" data-profile-action="map"><?php esc_html_e( 'Ver en el mapa', 'lubrax' ); ?> <?php echo lbx_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
