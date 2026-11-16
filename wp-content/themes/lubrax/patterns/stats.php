<?php
/**
 * Title: Cifras
 * Slug: lubrax/stats
 * Categories: lubrax-secciones
 * Keywords: cifras, números, estadísticas
 * Viewport Width: 1440
 * Description: Cuatro cifras destacadas. Un número solo ("+180", "8") se anima al aparecer.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

$lbx_stats = array(
	array( '+180', 'Productos en catálogo' ),
	array( '8', 'Líneas de producto' ),
	array( '<small class="stat__unit">ISO</small>4406', 'Código de limpieza en planta' ),
	array( '24 h', 'Respuesta con número de caso' ),
);
?>
<!-- wp:group {"tagName":"section","className":"section pt-0","layout":{"type":"default"}} -->
<section class="wp-block-group section pt-0"><!-- wp:group {"className":"container-xxl","layout":{"type":"default"}} -->
<div class="wp-block-group container-xxl"><!-- wp:group {"className":"stats reveal-up","layout":{"type":"default"}} -->
<div class="wp-block-group stats reveal-up">
<?php foreach ( $lbx_stats as [ $lbx_value, $lbx_label ] ) : ?>
<!-- wp:group {"className":"stat","layout":{"type":"default"}} -->
<div class="wp-block-group stat"><!-- wp:paragraph {"className":"stat__value"} -->
<p class="stat__value"><?php echo wp_kses( $lbx_value, array( 'small' => array( 'class' => array() ) ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"stat__label"} -->
<p class="stat__label"><?php echo esc_html( $lbx_label ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
