<?php
/**
 * Title: Texto e imagen (Lubrax en Chile)
 * Slug: lubrax/about-intro
 * Categories: lubrax-secciones
 * Keywords: nosotros, empresa, texto, imagen
 * Viewport Width: 1440
 * Description: Antetítulo, título en varias líneas, texto, botón e imagen (Quiénes somos).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","className":"section on-light","layout":{"type":"default"}} -->
<section class="wp-block-group section on-light"><!-- wp:group {"className":"container-xxl","layout":{"type":"default"}} -->
<div class="wp-block-group container-xxl"><!-- wp:group {"className":"row g-4 g-lg-5 align-items-center","layout":{"type":"default"}} -->
<div class="wp-block-group row g-4 g-lg-5 align-items-center"><!-- wp:group {"className":"col-lg-6","layout":{"type":"default"}} -->
<div class="wp-block-group col-lg-6"><!-- wp:paragraph {"className":"t-kicker reveal-up"} -->
<p class="t-kicker reveal-up">Lubrax en Chile</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"plant__title t-tight reveal-title"} -->
<h2 class="wp-block-heading plant__title t-tight reveal-title">Tecnología y<br>calidad que<br>mueven la industria</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"t-lead reveal-up"} -->
<p class="t-lead reveal-up">Nuestra línea de lubricantes Lubrax es desarrollada bajo las normas y especificaciones más exigentes del mundo, con un portafolio de productos sintéticos, semisintéticos y minerales para requerimientos automotrices e industriales.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"reveal-up"} -->
<p class="reveal-up">Contamos con una planta propia, exclusiva para el desarrollo de lubricantes y especialidades, ubicada en la comuna de Maipú, en la Región Metropolitana. Para cumplir con los estándares de limpieza de los fabricantes de equipos, microfiltramos nuestros productos según la norma ISO 4406.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"mt-2 reveal-up"} -->
<div class="wp-block-buttons mt-2 reveal-up"><!-- wp:button {"className":"is-style-lbx-dark"} -->
<div class="wp-block-button is-style-lbx-dark"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( wp_make_link_relative( lbx_page_url( 'productos' ) ) ); ?>">Ver catálogo</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"col-lg-6","layout":{"type":"default"}} -->
<div class="wp-block-group col-lg-6"><!-- wp:image {"sizeSlug":"large","className":"media-rounded reveal-clip"} -->
<figure class="wp-block-image size-large media-rounded reveal-clip"><img src="<?php echo esc_url( lbx_asset( 'img/home/planta-maipu-1024.webp' ) ); ?>" alt="Planta de lubricantes Lubrax en Maipú"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
