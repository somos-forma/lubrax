<?php
/**
 * Formularios con Contact Form 7 (+ Contact Form CFDB7 para guardar los envíos).
 *
 * - CF7 imprime el formulario (plantilla con el marcado de contacto.html) y procesa el envío por su API REST.
 *   El JavaScript y los estilos de CF7 no se cargan: valida app.js (Bootstrap) y envía assets/js/forms.js.
 * - Etiquetas propias: [lbx_tipo] (motivos con grupos y ayudas), [lbx_producto] (catálogo), [lbx_acepto]
 *   (casilla de privacidad), [lbx_enviar] (botón con ícono) y [lbx_antispam] (honeypot + token firmado).
 * - Campos condicionales: <fieldset data-show-for="tipo1 tipo2" hidden disabled> (contact.js). En el servidor,
 *   un campo con la clase lbx-required solo es obligatorio si el motivo elegido está en su data-show-for.
 * - Enrutamiento: cada motivo pertenece a un área (Comercial, Técnico o Distribuidores) y el correo va a los
 *   destinatarios de esa área (Opciones del tema > Formularios). En la pestaña Correo de CF7: [_lbx_area_email]
 *   (termina en _email para que el validador de CF7 la acepte como destinatario).
 * - Etiquetas de correo: [_lbx_caso] (número de caso), [_lbx_motivo], [_lbx_area], [_lbx_area_email] y
 *   [_lbx_resumen] (solo los campos que aplican al motivo).
 * - Antispam: honeypot, tiempo mínimo de llenado y token firmado; Turnstile o reCAPTCHA se activan en
 *   Contacto > Integración (CF7).
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

const LBX_FORMS_VERSION = '1';

/**
 * Motivos del formulario de contacto, agrupados como en la maqueta. "area" decide a quién llega el correo.
 *
 * Enrutamiento (definido aquí): Comercial = cliente, distribuidor, industrial, pedido y sugerencias;
 * Técnico = consulta, capacitación, análisis, reclamo de producto y garantía (requieren evaluar lote y producto);
 * Distribuidores = reclamo de servicio o entrega (la entrega la hace la red de distribuidores).
 *
 * @return array<string, array<string, array{label: string, hint: string, area: string}>>
 */
function lbx_contact_motives(): array {
	$motives = array(
		__( 'Comercial', 'lubrax' )          => array(
			'cliente'      => array( 'label' => __( 'Quiero ser cliente', 'lubrax' ), 'hint' => __( 'Te asignamos el distribuidor de tu zona y te enviamos condiciones comerciales.', 'lubrax' ), 'area' => 'comercial' ),
			'distribuidor' => array( 'label' => __( 'Quiero ser distribuidor', 'lubrax' ), 'hint' => __( 'Cuéntanos sobre tu negocio: un ejecutivo comercial te contactará.', 'lubrax' ), 'area' => 'comercial' ),
			'industrial'   => array( 'label' => __( 'Ventas industriales', 'lubrax' ), 'hint' => __( 'Indica el producto y el volumen estimado para preparar tu cotización.', 'lubrax' ), 'area' => 'comercial' ),
			'pedido'       => array( 'label' => __( 'Pedido y disponibilidad', 'lubrax' ), 'hint' => __( 'Indica el producto y, si ya compraste, el número de factura o pedido.', 'lubrax' ), 'area' => 'comercial' ),
		),
		__( 'Servicios técnicos', 'lubrax' ) => array(
			'consulta'     => array( 'label' => __( 'Consulta técnica', 'lubrax' ), 'hint' => __( 'Te responde nuestro equipo técnico. Indica el equipo y las condiciones de uso.', 'lubrax' ), 'area' => 'tecnico' ),
			'capacitacion' => array( 'label' => __( 'Capacitación técnica', 'lubrax' ), 'hint' => __( 'Coordinamos capacitaciones presenciales o en línea para tu equipo.', 'lubrax' ), 'area' => 'tecnico' ),
			'analisis'     => array( 'label' => __( 'Análisis de aceite (Lubrax Express)', 'lubrax' ), 'hint' => __( 'Te enviamos el kit de muestreo y las instrucciones para el análisis.', 'lubrax' ), 'area' => 'tecnico' ),
		),
		__( 'Postventa', 'lubrax' )          => array(
			'reclamo-producto' => array( 'label' => __( 'Reclamo de producto', 'lubrax' ), 'hint' => __( 'Ten a mano el número de lote del envase y tu factura o pedido.', 'lubrax' ), 'area' => 'tecnico' ),
			'reclamo-servicio' => array( 'label' => __( 'Reclamo de servicio o entrega', 'lubrax' ), 'hint' => __( 'Cuéntanos qué pasó con tu entrega o atención; te asignamos un ejecutivo.', 'lubrax' ), 'area' => 'distribuidores' ),
			'garantia'         => array( 'label' => __( 'Garantía', 'lubrax' ), 'hint' => __( 'Necesitamos el número de lote y la factura para evaluar la garantía.', 'lubrax' ), 'area' => 'tecnico' ),
		),
		__( 'Otros', 'lubrax' )              => array(
			'sugerencia' => array( 'label' => __( 'Sugerencia o felicitación', 'lubrax' ), 'hint' => __( 'Gracias por ayudarnos a mejorar.', 'lubrax' ), 'area' => 'comercial' ),
		),
	);

	return apply_filters( 'lbx_contact_motives', $motives );
}

/**
 * Motivos sin agrupar: valor => datos.
 */
function lbx_contact_motive( string $value = '' ): ?array {
	$all = array_merge( ...array_values( lbx_contact_motives() ) );
	return '' === $value ? $all : ( $all[ $value ] ?? null );
}

/**
 * Áreas de destino y su campo en Opciones del tema > Formularios.
 */
function lbx_contact_areas(): array {
	return array(
		'comercial'      => __( 'Comercial', 'lubrax' ),
		'tecnico'        => __( 'Técnico', 'lubrax' ),
		'distribuidores' => __( 'Distribuidores', 'lubrax' ),
	);
}

/**
 * Destinatarios de un área (varios correos separados por coma). Vacío: correo de atención, luego el del sitio.
 *
 * @return string[]
 */
function lbx_contact_recipients( string $area ): array {
	$emails = array_filter( array_map( 'trim', explode( ',', lbx_option( 'form_to_' . $area ) ) ), 'is_email' );

	if ( ! $emails ) {
		$emails = array_filter( array( lbx_option( 'email' ) ), 'is_email' ) ?: array( get_option( 'admin_email' ) );
	}

	return array_values( $emails );
}

/**
 * Opciones del tema > Formularios: el texto de ayuda lista los motivos de cada área (desde el código).
 */
add_filter(
	'acf/load_field/key=field_lbx_forms_help',
	function ( array $field ) {
		$by_area = array();
		foreach ( lbx_contact_motive() as $motive ) {
			$by_area[ $motive['area'] ][] = mb_strtolower( $motive['label'] );
		}
		$lines = array();
		foreach ( lbx_contact_areas() as $area => $label ) {
			$lines[] = '<b>' . esc_html( $label ) . '</b>: ' . esc_html( implode( ', ', $by_area[ $area ] ?? array() ) ) . '.';
		}
		$field['message'] = __( 'Cada motivo del formulario de contacto llega a un área:', 'lubrax' ) . '<br>' . implode( '<br>', $lines ) . '<br>'
			. __( 'Si un área no tiene correos, el mensaje llega al correo de atención (pestaña Contacto). Los envíos quedan guardados en «Contact Forms» (CFDB7). Turnstile o reCAPTCHA se activan en Contacto > Integración.', 'lubrax' );
		return $field;
	}
);

/**
 * ID del formulario CF7 asignado ('contact' o 'newsletter'): Opciones del tema, o el que creó el tema.
 */
function lbx_form_id( string $which ): int {
	$id = (int) lbx_option( 'form_' . $which );
	if ( ! $id ) {
		$id = (int) ( get_option( 'lbx_cf7_forms', array() )[ $which ] ?? 0 );
	}
	return $id && 'wpcf7_contact_form' === get_post_type( $id ) ? $id : 0;
}

/**
 * HTML de un formulario CF7 con los atributos de la maqueta (id, clases). '' si CF7 no está activo.
 */
function lbx_form_html( int $form_id, array $args = array() ): string {
	if ( ! $form_id || ! function_exists( 'wpcf7_contact_form' ) ) {
		return '';
	}
	$form = wpcf7_contact_form( $form_id );
	if ( ! $form ) {
		return '';
	}

	wp_enqueue_script( 'lbx-forms' );

	return $form->form_html( $args );
}

/**
 * ¿El formulario usa las etiquetas del formulario de contacto de Lubrax?
 */
function lbx_is_contact_form( $form ): bool {
	return $form instanceof WPCF7_ContactForm && (bool) $form->scan_form_tags( array( 'type' => array( 'lbx_tipo', 'lbx_tipo*' ) ) );
}

/**
 * Campos condicionales del formulario: nombre => [ motivos en que aplica, ¿obligatorio? ].
 * Se leen de los <fieldset data-show-for> de la plantilla, así el formulario de CF7 es la única fuente.
 *
 * @return array<string, array{types: string[], required: bool}>
 */
function lbx_contact_conditions( WPCF7_ContactForm $form ): array {
	$map = array();
	preg_match_all( '#<fieldset\b[^>]*\bdata-show-for="([^"]*)"[^>]*>(.*?)</fieldset>#is', (string) $form->prop( 'form' ), $sets, PREG_SET_ORDER );

	foreach ( $sets as $set ) {
		preg_match_all( '/\[\s*[a-z_]+\*?\s+([a-zA-Z][\w-]*)([^\]]*)\]/', $set[2], $tags, PREG_SET_ORDER );
		foreach ( $tags as $tag ) {
			$map[ $tag[1] ] = array(
				'types'    => preg_split( '/\s+/', trim( $set[1] ) ),
				'required' => (bool) preg_match( '/\bclass:lbx-required\b/', $tag[2] ),
			);
		}
	}

	return $map;
}

/**
 * ¿El campo aplica al motivo enviado? (los que no están en un fieldset condicional, siempre).
 */
function lbx_contact_field_applies( WPCF7_ContactForm $form, string $name, string $tipo ): bool {
	$conditions = lbx_contact_conditions( $form );
	return ! isset( $conditions[ $name ] ) || in_array( $tipo, $conditions[ $name ]['types'], true );
}

/**
 * Número de caso del envío en curso (se genera una vez por envío): LBX-20261025-00042.
 */
function lbx_contact_case(): string {
	static $case = '';
	if ( '' === $case ) {
		$seq = (int) get_option( 'lbx_contact_case_seq', 0 ) + 1;
		update_option( 'lbx_contact_case_seq', $seq, false );
		$case = sprintf( 'LBX-%s-%05d', wp_date( 'Ymd' ), $seq );
	}
	return $case;
}

/**
 * Valor legible de un campo para el correo y la base de datos (motivo y producto por su nombre).
 */
function lbx_contact_display_value( string $name, $value ): string {
	$value = is_array( $value ) ? implode( ', ', array_filter( $value ) ) : (string) $value;

	if ( 'tipo' === $name ) {
		return lbx_contact_motive( $value )['label'] ?? $value;
	}
	if ( 'producto' === $name && '' !== $value ) {
		$product = get_page_by_path( $value, OBJECT, 'product' );
		return $product ? get_the_title( $product ) : $value;
	}
	if ( 'privacidad' === $name ) {
		return '' !== $value ? __( 'Sí', 'lubrax' ) : '';
	}
	return $value;
}

/**
 * Campos enviados que aplican al motivo, en el orden del formulario: [ etiqueta, valor ].
 *
 * @return array<int, array{0: string, 1: string}>
 */
function lbx_contact_summary( WPCF7_ContactForm $form, array $posted ): array {
	// Etiquetas: el texto de cada <label for="id"> de la plantilla.
	preg_match_all( '#<label\b[^>]*\bfor="([^"]+)"[^>]*>(.*?)</label>#is', (string) $form->prop( 'form' ), $labels, PREG_SET_ORDER );
	$label_by_id = array();
	foreach ( $labels as $label ) {
		$label_by_id[ $label[1] ] = trim( rtrim( trim( wp_strip_all_tags( $label[2] ) ), '*' ) );
	}

	$tipo = (string) ( $posted['tipo'] ?? '' );
	$rows = array();
	foreach ( $form->scan_form_tags( array( 'feature' => 'name-attr' ) ) as $tag ) {
		if ( ! $tag->name || 'privacidad' === $tag->name || isset( $rows[ $tag->name ] ) || ! lbx_contact_field_applies( $form, $tag->name, $tipo ) ) {
			continue;
		}
		$value = lbx_contact_display_value( $tag->name, $posted[ $tag->name ] ?? '' );
		if ( '' === trim( $value ) ) {
			continue;
		}
		// get_option y no get_id_option: CF7 no devuelve dos veces el mismo id (el resumen va en dos correos).
		$id                 = (string) $tag->get_option( 'id', 'id', true );
		$rows[ $tag->name ] = array( $label_by_id[ $id ] ?? ucfirst( $tag->name ), $value );
	}

	return array_values( $rows );
}

/* -------------------------------------------------------------------------
 * Etiquetas de formulario
 * ---------------------------------------------------------------------- */

add_action(
	'wpcf7_init',
	function () {
		wpcf7_add_form_tag( array( 'lbx_tipo', 'lbx_tipo*' ), 'lbx_cf7_tag_tipo', array( 'name-attr' => true ) );
		wpcf7_add_form_tag( array( 'lbx_producto', 'lbx_producto*' ), 'lbx_cf7_tag_producto', array( 'name-attr' => true ) );
		wpcf7_add_form_tag( array( 'lbx_acepto', 'lbx_acepto*' ), 'lbx_cf7_tag_acepto', array( 'name-attr' => true ) );
		wpcf7_add_form_tag( 'lbx_enviar', 'lbx_cf7_tag_enviar' );
		wpcf7_add_form_tag( 'lbx_antispam', 'lbx_cf7_tag_antispam' );
	}
);

/**
 * Atributos comunes de un control: clase, id, obligatorio y estado de error.
 */
function lbx_cf7_control_atts( WPCF7_FormTag $tag, string $class ): array {
	$error = wpcf7_get_validation_error( $tag->name );
	$atts  = array(
		'class'        => $tag->get_class_option( wpcf7_form_controls_class( $tag->type, $class ) . ( $error ? ' wpcf7-not-valid is-invalid' : '' ) ),
		'id'           => $tag->get_id_option(),
		'name'         => $tag->name,
		'required'     => $tag->is_required(),
		'aria-invalid' => $error ? 'true' : 'false',
	);
	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}
	return $atts;
}

/**
 * Envoltorio de CF7 (span data-name) con el error del servidor (envío sin JavaScript).
 */
function lbx_cf7_wrap( WPCF7_FormTag $tag, string $control ): string {
	return sprintf( '<span class="wpcf7-form-control-wrap" data-name="%1$s">%2$s%3$s</span>', esc_attr( $tag->name ), $control, wpcf7_get_validation_error( $tag->name ) );
}

/**
 * [lbx_tipo* tipo id:contactType] — motivos con <optgroup> y ayuda (data-hint). Seleccionado: "consulta" o default:.
 */
function lbx_cf7_tag_tipo( WPCF7_FormTag $tag ): string {
	$selected = (string) ( wpcf7_get_hangover( $tag->name ) ?: $tag->get_option( 'default', '[a-z0-9-]+', true ) ?: 'consulta' );
	$atts     = lbx_cf7_control_atts( $tag, 'form-select' );
	$hint_id  = $tag->get_option( 'hint', 'id', true ) ?: 'contactTypeHint';

	$atts['aria-describedby'] = $hint_id;

	$html = '';
	foreach ( lbx_contact_motives() as $group => $items ) {
		$html .= '<optgroup label="' . esc_attr( $group ) . '">';
		foreach ( $items as $value => $motive ) {
			$html .= sprintf(
				'<option value="%1$s" data-hint="%2$s"%3$s>%4$s</option>',
				esc_attr( $value ),
				esc_attr( $motive['hint'] ),
				selected( $selected, $value, false ),
				esc_html( $motive['label'] )
			);
		}
		$html .= '</optgroup>';
	}

	return lbx_cf7_wrap( $tag, '<select ' . wpcf7_format_atts( $atts ) . '>' . $html . '</select>' );
}

/**
 * [lbx_producto producto id:cProduct] — productos del catálogo agrupados por segmento (valor: slug).
 */
function lbx_cf7_tag_producto( WPCF7_FormTag $tag ): string {
	$atts    = lbx_cf7_control_atts( $tag, 'form-select' );
	$label   = $tag->values[0] ?? __( 'Selecciona un producto', 'lubrax' );
	$options = function_exists( 'lbx_product_options_html' ) && function_exists( 'wc_get_products' ) ? lbx_product_options_html( (string) wpcf7_get_hangover( $tag->name ) ) : '';

	return lbx_cf7_wrap( $tag, '<select ' . wpcf7_format_atts( $atts ) . '><option value="">' . esc_html( $label ) . '</option>' . $options . '</select>' );
}

/**
 * [lbx_acepto* privacidad id:cPrivacy "Acepto la {política de privacidad}…"] — casilla de Bootstrap.
 * El texto entre llaves enlaza a la página de privacidad (Ajustes > Privacidad).
 */
function lbx_cf7_tag_acepto( WPCF7_FormTag $tag ): string {
	$atts          = lbx_cf7_control_atts( $tag, 'form-check-input' );
	$atts['type']  = 'checkbox';
	$atts['value'] = '1';
	$atts['id']    = $atts['id'] ?: 'cPrivacy';

	$text = esc_html( $tag->values[0] ?? __( 'Acepto la {política de privacidad} y el uso de mis datos para responder esta solicitud.', 'lubrax' ) );
	$url  = get_privacy_policy_url() ?: lbx_page_url( 'politicas' );
	$text = preg_replace( '/\{([^}]+)\}/', '<a href="' . esc_url( $url ) . '">$1</a>', $text );

	return sprintf(
		'<div class="form-check"><input %1$s><label class="form-check-label small" for="%2$s">%3$s%4$s</label><div class="invalid-feedback">%5$s</div>%6$s</div>',
		wpcf7_format_atts( $atts ),
		esc_attr( $atts['id'] ),
		$text,
		$tag->is_required() ? ' *' : '',
		esc_html__( 'Debes aceptar para continuar.', 'lubrax' ),
		wpcf7_get_validation_error( $tag->name )
	);
}

/**
 * [lbx_enviar class:btn-lbx-dark icon:send "Enviar mensaje"] — <button> con ícono (CF7 solo imprime <input>).
 */
function lbx_cf7_tag_enviar( WPCF7_FormTag $tag ): string {
	$icon = $tag->get_option( 'icon', '[a-z-]+', true );

	return sprintf(
		'<button %1$s>%2$s%3$s</button>',
		wpcf7_format_atts(
			array(
				'class' => $tag->get_class_option( 'btn' ),
				'id'    => $tag->get_id_option(),
				'type'  => 'submit',
			)
		),
		$icon ? lbx_icon( $icon ) . ' ' : '',
		esc_html( $tag->values[0] ?? __( 'Enviar', 'lubrax' ) )
	);
}

/**
 * [lbx_antispam] — campo trampa (los bots lo completan) y token con la hora de carga, firmado.
 * Los nombres empiezan con "_": CF7 no los guarda ni los envía por correo.
 */
function lbx_cf7_tag_antispam( WPCF7_FormTag $tag ): string {
	static $n = 0;
	$id    = 'lbxHp' . ( ++$n );
	$time  = (string) time();
	$token = $time . '.' . wp_hash( 'lbx-form|' . $time, 'nonce' );

	return sprintf(
		'<div class="lbx-hp" aria-hidden="true"><label for="%1$s">%2$s</label><input type="text" id="%1$s" name="_lbx_web" value="" tabindex="-1" autocomplete="off"></div><input type="hidden" name="_lbx_token" value="%3$s">',
		esc_attr( $id ),
		esc_html__( 'No completes este campo', 'lubrax' ),
		esc_attr( $token )
	);
}

/* -------------------------------------------------------------------------
 * Validación en el servidor
 * ---------------------------------------------------------------------- */

foreach ( array( 'lbx_tipo', 'lbx_tipo*' ) as $lbx_type ) {
	add_filter(
		"wpcf7_validate_{$lbx_type}",
		function ( $result, $tag ) {
			$value = (string) wpcf7_superglobal_post( $tag->name );
			if ( '' === $value && $tag->is_required() ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			} elseif ( '' !== $value && ! lbx_contact_motive( $value ) ) {
				$result->invalidate( $tag, __( 'Elige un tipo de solicitud de la lista.', 'lubrax' ) );
			}
			return $result;
		},
		10,
		2
	);
}

foreach ( array( 'lbx_producto', 'lbx_producto*' ) as $lbx_type ) {
	add_filter(
		"wpcf7_validate_{$lbx_type}",
		function ( $result, $tag ) {
			$value = (string) wpcf7_superglobal_post( $tag->name );
			if ( '' === $value && $tag->is_required() ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			} elseif ( '' !== $value && ! get_page_by_path( $value, OBJECT, 'product' ) ) {
				$result->invalidate( $tag, __( 'Elige un producto de la lista.', 'lubrax' ) );
			}
			return $result;
		},
		10,
		2
	);
}

foreach ( array( 'lbx_acepto', 'lbx_acepto*' ) as $lbx_type ) {
	add_filter(
		"wpcf7_validate_{$lbx_type}",
		function ( $result, $tag ) {
			if ( $tag->is_required() && '' === (string) wpcf7_superglobal_post( $tag->name ) ) {
				$result->invalidate( $tag, __( 'Debes aceptar para continuar.', 'lubrax' ) );
			}
			return $result;
		},
		10,
		2
	);
}
unset( $lbx_type );

/**
 * Campos condicionales con la clase lbx-required: obligatorios solo para los motivos de su fieldset.
 */
add_filter(
	'wpcf7_validate',
	function ( $result, $tags ) {
		$form = WPCF7_ContactForm::get_current();
		if ( ! lbx_is_contact_form( $form ) ) {
			return $result;
		}

		$tipo = sanitize_key( (string) wpcf7_superglobal_post( 'tipo' ) );
		foreach ( lbx_contact_conditions( $form ) as $name => $condition ) {
			if ( ! $condition['required'] || ! in_array( $tipo, $condition['types'], true ) || ! $result->is_valid( $name ) ) {
				continue;
			}
			if ( '' === trim( (string) wpcf7_superglobal_post( $name ) ) ) {
				foreach ( $tags as $tag ) {
					if ( $tag->name === $name ) {
						$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
						break;
					}
				}
			}
		}
		return $result;
	},
	10,
	2
);

/**
 * Antispam propio: campo trampa completado, token alterado o envío en menos de 3 segundos (filtro lbx_form_min_seconds).
 */
add_filter(
	'wpcf7_spam',
	function ( $spam, $submission ) {
		if ( $spam || ! $submission->get_contact_form()->scan_form_tags( array( 'type' => 'lbx_antispam' ) ) ) {
			return $spam;
		}

		$reason = '';
		$token  = explode( '.', (string) wpcf7_superglobal_post( '_lbx_token' ) );
		if ( '' !== trim( (string) wpcf7_superglobal_post( '_lbx_web' ) ) ) {
			$reason = __( 'Se completó el campo trampa.', 'lubrax' );
		} elseif ( 2 !== count( $token ) || ! hash_equals( wp_hash( 'lbx-form|' . $token[0], 'nonce' ), $token[1] ) ) {
			$reason = __( 'El token del formulario no es válido.', 'lubrax' );
		} elseif ( time() - (int) $token[0] < (int) apply_filters( 'lbx_form_min_seconds', 3 ) ) {
			$reason = __( 'El formulario se envió demasiado rápido.', 'lubrax' );
		}

		if ( $reason ) {
			$submission->add_spam_log( array( 'agent' => 'lubrax', 'reason' => $reason ) );
			return true;
		}
		return false;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Correo, base de datos (CFDB7) y respuesta
 * ---------------------------------------------------------------------- */

/**
 * Etiquetas de correo especiales del formulario de contacto.
 */
add_filter(
	'wpcf7_special_mail_tags',
	function ( $output, $name, $html ) {
		if ( ! str_starts_with( (string) $name, '_lbx_' ) ) {
			return $output;
		}
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return $output;
		}
		$posted = $submission->get_posted_data();
		$motive = lbx_contact_motive( (string) ( $posted['tipo'] ?? '' ) );
		$area   = $motive['area'] ?? 'comercial';

		switch ( $name ) {
			case '_lbx_caso':
				return lbx_contact_case();
			case '_lbx_motivo':
				return $motive['label'] ?? '';
			case '_lbx_area':
				return lbx_contact_areas()[ $area ] ?? '';
			case '_lbx_area_email':
				return implode( ', ', lbx_contact_recipients( $area ) );
			case '_lbx_resumen':
				$rows = lbx_contact_summary( $submission->get_contact_form(), $posted );
				if ( ! $html ) {
					return implode( "\n", array_map( fn( $row ) => $row[0] . ': ' . $row[1], $rows ) );
				}
				$cells = array_map(
					fn( $row ) => sprintf(
						'<tr><th style="padding:6px 12px 6px 0;text-align:left;vertical-align:top;white-space:nowrap">%s</th><td style="padding:6px 0">%s</td></tr>',
						esc_html( $row[0] ),
						nl2br( esc_html( $row[1] ) )
					),
					$rows
				);
				return '<table style="border-collapse:collapse">' . implode( '', $cells ) . '</table>';
		}
		return $output;
	},
	10,
	3
);

/**
 * CFDB7: guarda el número de caso, el área y valores legibles; descarta los campos que no aplican al motivo.
 */
add_filter(
	'cfdb7_before_save_data',
	function ( array $data ) {
		$submission = WPCF7_Submission::get_instance();
		$form       = $submission ? $submission->get_contact_form() : null;
		if ( ! lbx_is_contact_form( $form ) ) {
			return $data;
		}

		$tipo   = (string) ( $submission->get_posted_data( 'tipo' ) ?? '' );
		$motive = lbx_contact_motive( $tipo );
		foreach ( $data as $key => $value ) {
			if ( 'cfdb7_status' !== $key && ! lbx_contact_field_applies( $form, $key, $tipo ) ) {
				unset( $data[ $key ] );
			} elseif ( is_array( $value ) ) {
				$data[ $key ] = implode( ', ', $value ); // Listas desplegables: texto, no un arreglo.
			}
		}
		if ( isset( $data['tipo'] ) ) {
			$data['tipo'] = $motive['label'] ?? $tipo;
		}
		if ( ! empty( $data['producto'] ) ) {
			$data['producto'] = lbx_contact_display_value( 'producto', $submission->get_posted_data( 'producto' ) );
		}

		return array( 'caso' => lbx_contact_case(), 'area' => lbx_contact_areas()[ $motive['area'] ?? 'comercial' ] ?? '' ) + $data;
	}
);

/**
 * Respuesta de la API: número de caso para el mensaje de éxito.
 */
add_filter(
	'wpcf7_feedback_response',
	function ( array $response ) {
		$submission = WPCF7_Submission::get_instance();
		if ( 'mail_sent' === ( $response['status'] ?? '' ) && $submission && lbx_is_contact_form( $submission->get_contact_form() ) ) {
			$response['lbx_case'] = lbx_contact_case();
		}
		return $response;
	}
);

/* -------------------------------------------------------------------------
 * Front: marcado de la maqueta y recursos
 * ---------------------------------------------------------------------- */

// Sin <p> ni <br> automáticos: la plantilla ya trae el marcado de Bootstrap.
add_filter( 'wpcf7_autop_or_not', '__return_false' );
// Sin el JS ni los estilos de CF7: valida app.js y envía assets/js/forms.js.
add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );

/**
 * <form data-cf7-endpoint>: URL de la API REST de CF7 que usa forms.js.
 */
add_filter(
	'wpcf7_form_additional_atts',
	function ( array $atts ) {
		$form = WPCF7_ContactForm::get_current();
		if ( $form ) {
			$atts['data-cf7-endpoint'] = rest_url( 'contact-form-7/v1/contact-forms/' . $form->id() . '/feedback' );
		}
		return $atts;
	}
);

/**
 * Validación en el navegador (Bootstrap): CF7 no imprime "required". Se agrega a los campos obligatorios
 * y a los condicionales con la clase lbx-required (un <fieldset disabled> no se valida). También pasa al
 * control las opciones aria-describedby: e inputmode: de la etiqueta.
 */
add_filter(
	'wpcf7_form_elements',
	function ( string $html ) {
		$form  = WPCF7_ContactForm::get_current();
		$extra = array();
		if ( $form ) {
			foreach ( $form->scan_form_tags() as $tag ) {
				foreach ( array( 'aria-describedby' => 'id', 'inputmode' => '[a-z]+' ) as $option => $pattern ) {
					$value = $tag->name ? $tag->get_option( $option, $pattern, true ) : '';
					if ( $value ) {
						$extra[ $tag->name ][ $option ] = $value;
					}
				}
			}
		}

		$tags = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag() ) {
			if ( ! in_array( $tags->get_tag(), array( 'INPUT', 'SELECT', 'TEXTAREA' ), true ) ) {
				continue;
			}
			if ( $tags->has_class( 'wpcf7-validates-as-required' ) || $tags->has_class( 'lbx-required' ) ) {
				$tags->set_attribute( 'required', true );
			}
			$name = (string) $tags->get_attribute( 'name' );
			foreach ( $extra[ $name ] ?? array() as $attr => $value ) {
				$tags->set_attribute( $attr, $value );
			}
		}
		return $tags->get_updated_html();
	},
	20
);

/* -------------------------------------------------------------------------
 * Formularios del tema (se crean una vez; luego se editan en Contacto > Formularios de contacto)
 * ---------------------------------------------------------------------- */

/**
 * Plantilla del formulario de contacto: marcado de contacto.html con etiquetas de CF7.
 */
function lbx_contact_form_template(): string {
	$regions = '"' . implode( '" "', array( 'Arica y Parinacota', 'Tarapacá', 'Antofagasta', 'Atacama', 'Coquimbo', 'Valparaíso', 'Metropolitana de Santiago', "Libertador General Bernardo O'Higgins", 'Maule', 'Ñuble', 'Biobío', 'La Araucanía', 'Los Ríos', 'Los Lagos', 'Aysén del General Carlos Ibáñez del Campo', 'Magallanes y de la Antártica Chilena' ) ) . '"';

	return <<<CF7
<div class="row g-3">
<div class="col-12">
<label class="form-label" for="contactType">Tipo de solicitud *</label>
[lbx_tipo* tipo id:contactType]
<div class="form-text" id="contactTypeHint" data-type-hint></div>
</div>
<div class="col-md-6">
<label class="form-label" for="cName">Nombre y apellido *</label>
[text* nombre id:cName class:form-control autocomplete:name]
<div class="invalid-feedback">Ingresa tu nombre.</div>
</div>
<div class="col-md-6">
<label class="form-label" for="cCompany">Empresa</label>
[text empresa id:cCompany class:form-control autocomplete:organization]
</div>
<div class="col-md-6">
<label class="form-label" for="cEmail">Correo electrónico *</label>
[email* email id:cEmail class:form-control autocomplete:email]
<div class="invalid-feedback">Ingresa un correo válido.</div>
</div>
<div class="col-md-6">
<label class="form-label" for="cPhone">Teléfono</label>
[tel telefono id:cPhone class:form-control autocomplete:tel inputmode:tel placeholder "+56 9 1234 5678"]
</div>
<div class="col-md-6">
<label class="form-label" for="cRegion">Región</label>
[select zona id:cRegion class:form-select first_as_label "Selecciona tu región" {$regions}]
</div>
<div class="col-md-6">
<label class="form-label" for="cCity">Comuna</label>
[text comuna id:cCity class:form-control autocomplete:address-level2]
</div>

<!-- Campos según el tipo de solicitud (contact.js). Un <fieldset disabled> no se valida ni se envía.
     class:lbx-required = obligatorio solo para los motivos de data-show-for. -->
<fieldset class="col-md-6" data-show-for="distribuidor cliente" hidden disabled>
<label class="form-label" for="cBusiness">Tipo de negocio *</label>
[select negocio id:cBusiness class:form-select class:lbx-required first_as_label "Selecciona" "Lubricentro" "Taller mecánico" "Distribuidora de lubricantes" "Repuestos o ferretería" "Flota o empresa de transporte" "Agrícola, minera o industrial" "Otro"]
<div class="invalid-feedback">Selecciona el tipo de negocio.</div>
</fieldset>
<fieldset class="col-md-6" data-show-for="distribuidor cliente industrial" hidden disabled>
<label class="form-label" for="cVolume">Volumen mensual estimado</label>
[select volumen id:cVolume class:form-select first_as_label "Selecciona" "Menos de 1.000 L" "1.000 a 5.000 L" "5.000 a 20.000 L" "Más de 20.000 L"]
</fieldset>
<fieldset class="col-md-6" data-show-for="consulta industrial pedido analisis reclamo-producto garantia" hidden disabled>
<label class="form-label" for="cProduct">Producto (si aplica)</label>
[lbx_producto producto id:cProduct "Selecciona un producto"]
</fieldset>
<fieldset class="col-md-6" data-show-for="reclamo-producto garantia" hidden disabled>
<label class="form-label" for="cLot">Número de lote *</label>
[text lote id:cLot class:form-control class:lbx-required aria-describedby:cLotHint]
<div class="form-text" id="cLotHint">Está impreso en el envase, cerca de la fecha de fabricación.</div>
<div class="invalid-feedback">Ingresa el número de lote.</div>
</fieldset>
<fieldset class="col-md-6" data-show-for="reclamo-producto reclamo-servicio garantia pedido" hidden disabled>
<label class="form-label" for="cInvoice">Factura o pedido (si aplica)</label>
[text factura id:cInvoice class:form-control]
</fieldset>
<fieldset class="col-md-6" data-show-for="capacitacion" hidden disabled>
<label class="form-label" for="cPeople">Número de participantes</label>
[number participantes id:cPeople class:form-control min:1 max:500 inputmode:numeric]
</fieldset>
<fieldset class="col-md-6" data-show-for="capacitacion" hidden disabled>
<label class="form-label" for="cMode">Modalidad</label>
[select modalidad id:cMode class:form-select "Presencial" "En línea"]
</fieldset>
<fieldset class="col-md-6" data-show-for="analisis" hidden disabled>
<label class="form-label" for="cEquipment">Equipo o flota a analizar</label>
[text equipo id:cEquipment class:form-control placeholder "Ej.: 12 camiones Volvo FH, motor D13"]
</fieldset>

<div class="col-12">
<label class="form-label" for="cMessage">Cuéntanos qué necesitas *</label>
[textarea* mensaje id:cMessage class:form-control minlength:10 x5]
<div class="invalid-feedback">Escribe tu mensaje (mínimo 10 caracteres).</div>
</div>
<div class="col-12">
[lbx_acepto* privacidad id:cPrivacy "Acepto la {política de privacidad} y el uso de mis datos para responder esta solicitud."]
</div>
<div class="col-12 d-flex flex-wrap align-items-center gap-3">
[lbx_enviar class:btn-lbx-dark icon:send "Enviar mensaje"]
<p class="small text-body-secondary mb-0">Te enviamos el número de caso a tu correo.</p>
</div>
</div>
[lbx_antispam]
CF7;
}

/**
 * Plantilla del newsletter: marcado del footer de la maqueta.
 */
function lbx_newsletter_form_template(): string {
	return <<<CF7
<label for="newsletterEmail" class="visually-hidden">Correo electrónico</label>
[email* email id:newsletterEmail class:form-control autocomplete:email placeholder "Tu correo electrónico"]
[lbx_enviar class:btn-lbx class:btn-sm "Suscribirme"]
<div class="invalid-feedback">Ingresa un correo válido.</div>
[lbx_antispam]
CF7;
}

/**
 * Crea los formularios "Contacto" y "Newsletter" la primera vez (o si se borraron) y guarda sus ID.
 */
function lbx_install_forms(): void {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return;
	}

	$ids    = (array) get_option( 'lbx_cf7_forms', array() );
	$domain = wp_parse_url( home_url(), PHP_URL_HOST );
	$from   = sprintf( '%s <wordpress@%s>', get_bloginfo( 'name' ), preg_replace( '/^www\./', '', (string) $domain ) );

	$forms = array(
		'contact'    => array(
			'title'      => 'Contacto',
			'form'       => lbx_contact_form_template(),
			'mail'       => array(
				'active'             => true,
				'recipient'          => '[_lbx_area_email]',
				'sender'             => $from,
				'subject'            => '[_lbx_caso] · [_lbx_motivo] · [nombre]',
				'additional_headers' => 'Reply-To: [nombre] <[email]>',
				'body'               => "<p><strong>Nueva solicitud desde el sitio web</strong><br>Caso: [_lbx_caso] · Área: [_lbx_area]</p>\n[_lbx_resumen]\n<p style=\"color:#666;font-size:12px\">Enviado desde [_post_url] el [_date] a las [_time].</p>",
				'attachments'        => '',
				'use_html'           => true,
				'exclude_blank'      => false,
			),
			'mail_2'     => array(
				'active'             => true,
				'recipient'          => '[email]',
				'sender'             => $from,
				'subject'            => 'Recibimos tu mensaje · caso [_lbx_caso]',
				'additional_headers' => 'Reply-To: [_lbx_area_email]',
				'body'               => "<p>Hola [nombre]:</p>\n<p>Recibimos tu solicitud de <strong>[_lbx_motivo]</strong>. Tu número de caso es <strong>[_lbx_caso]</strong>.</p>\n<p>Un ejecutivo te contactará dentro de 24 horas hábiles. Si necesitas agregar información, responde este correo indicando el número de caso.</p>\n[_lbx_resumen]\n<p>Equipo Lubrax</p>",
				'attachments'        => '',
				'use_html'           => true,
				'exclude_blank'      => false,
			),
			'additional' => '',
		),
		'newsletter' => array(
			'title'      => 'Newsletter',
			'form'       => lbx_newsletter_form_template(),
			'mail'       => array(
				'active'             => true,
				'recipient'          => '[_site_admin_email]',
				'sender'             => $from,
				'subject'            => 'Nueva suscripción al newsletter: [email]',
				'additional_headers' => '',
				'body'               => 'Correo: [email]',
				'attachments'        => '',
				'use_html'           => false,
				'exclude_blank'      => false,
			),
			// Las suscripciones quedan en Contact Forms (CFDB7), sin aviso por correo.
			'additional' => 'skip_mail: on',
		),
	);

	$messages = array(
		'mail_sent_ok'     => '¡Recibimos tu mensaje! Te enviaremos el número de caso a tu correo.',
		'mail_sent_ng'     => 'No pudimos enviar tu mensaje. Inténtalo de nuevo o escríbenos a ' . lbx_option( 'email' ) . '.',
		'validation_error' => 'Revisa los campos marcados.',
		'spam'             => 'No pudimos enviar tu mensaje. Recarga la página e inténtalo de nuevo.',
		'invalid_required' => 'Completa este campo.',
		'invalid_email'    => 'Ingresa un correo válido.',
		'invalid_tel'      => 'Ingresa un teléfono válido.',
		'invalid_too_short' => 'El texto es demasiado corto.',
	);

	foreach ( $forms as $key => $data ) {
		if ( ! empty( $ids[ $key ] ) && 'wpcf7_contact_form' === get_post_type( (int) $ids[ $key ] ) ) {
			continue;
		}
		$form = WPCF7_ContactForm::get_template( array( 'title' => $data['title'], 'locale' => get_locale() ) );
		$form->set_properties(
			array(
				'form'                => $data['form'],
				'mail'                => $data['mail'],
				'mail_2'              => $data['mail_2'] ?? array_merge( $form->prop( 'mail_2' ), array( 'active' => false ) ),
				'messages'            => array_merge( $form->prop( 'messages' ), 'newsletter' === $key ? array( 'mail_sent_ok' => '¡Listo! Te suscribiste al newsletter.' ) + $messages : $messages ),
				'additional_settings' => $data['additional'],
			)
		);
		$ids[ $key ] = $form->save();
	}

	update_option( 'lbx_cf7_forms', $ids, false );
}

add_action(
	'admin_init',
	function () {
		if ( class_exists( 'WPCF7_ContactForm' ) && get_option( 'lbx_forms_version' ) !== LBX_FORMS_VERSION ) {
			lbx_install_forms();
			update_option( 'lbx_forms_version', LBX_FORMS_VERSION, false );
		}
	}
);
