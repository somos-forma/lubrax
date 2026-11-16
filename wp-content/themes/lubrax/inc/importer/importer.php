<?php
/**
 * Importador de productos y distribuidores desde Excel.
 *
 * - Escritorio: Productos > Importar desde Excel (simulación por defecto).
 * - WP-CLI:     wp lubrax importar "PLANTILLAS CONTENIDO WEB.xlsx" [--simular] [--estado=draft]
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'update_field' ) ) {
	return;
}

require_once __DIR__ . '/class-lbx-xlsx-reader.php';
require_once __DIR__ . '/class-lbx-import-media.php';
require_once __DIR__ . '/class-lbx-importer.php';

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Importar desde Excel', 'lubrax' ),
			__( 'Importar desde Excel', 'lubrax' ),
			'edit_products',
			'lbx-importar',
			'lbx_render_importer_page'
		);
	}
);

/**
 * Pantalla del importador: formulario y, después de enviarlo, el informe.
 */
function lbx_render_importer_page(): void {
	$can_import = current_user_can( 'edit_products' ) && current_user_can( 'edit_distribuidores' ) && current_user_can( 'upload_files' );
	$report     = null;
	$error      = '';

	if ( $can_import && isset( $_POST['lbx_import_nonce'] ) ) {
		check_admin_referer( 'lbx_import', 'lbx_import_nonce' );

		$file = $_FILES['lbx_file'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $file || UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$error = __( 'Sube el archivo .xlsx de la plantilla.', 'lubrax' );
		} elseif ( 'xlsx' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) ) {
			$error = __( 'El archivo debe ser .xlsx (Excel).', 'lubrax' );
		} else {
			try {
				$importer = new LBX_Importer(
					array(
						'dry_run' => ! empty( $_POST['lbx_dry_run'] ),
						'status'  => sanitize_key( $_POST['lbx_status'] ?? 'publish' ),
					)
				);
				$report   = $importer->run( LBX_Xlsx_Reader::read( $file['tmp_name'] ) );
			} catch ( Throwable $e ) {
				$error = $e->getMessage();
			}
		}
	}

	$template_url = lbx_asset( 'import/plantilla-contenido-web.xlsx' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Importar productos y distribuidores', 'lubrax' ); ?></h1>

		<?php if ( ! $can_import ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Necesitas permisos para editar productos, distribuidores y subir archivos.', 'lubrax' ); ?></p></div>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<div class="card" style="max-width:none">
			<h2><?php esc_html_e( 'Cómo funciona', 'lubrax' ); ?></h2>
			<ol>
				<li><?php printf( wp_kses( __( 'Completa la <a href="%s">plantilla Excel</a> (hojas «Productos» y «Distribuidores»). Las columnas se reconocen por su título; puedes agregar las opcionales <code>SKU</code>, <code>Tipo de producto</code>, <code>Descripción corta</code>, <code>Normas y homologaciones</code> y, en distribuidores, <code>Región</code>, <code>Comuna</code> (lista dependiente de la región), <code>Zona</code>, <code>Correo</code>, <code>Latitud</code> y <code>Longitud</code>.', 'lubrax' ), array( 'a' => array( 'href' => array() ), 'code' => array() ) ), esc_url( $template_url ) ); ?></li>
				<li><?php esc_html_e( 'Varios valores en una celda: sepáralos con coma, punto y coma o salto de línea.', 'lubrax' ); ?></li>
				<li><?php esc_html_e( 'Imágenes y PDF: enlaces públicos (Google Drive compartido como «Cualquier persona con el enlace»). Se descargan en segundo plano después de importar.', 'lubrax' ); ?></li>
				<li><?php esc_html_e( 'Primero ejecuta una simulación: muestra qué se creará o actualizará sin guardar nada. Volver a importar actualiza los mismos registros (no duplica).', 'lubrax' ); ?></li>
			</ol>
		</div>

		<form method="post" enctype="multipart/form-data" style="margin-top:1.5rem">
			<?php wp_nonce_field( 'lbx_import', 'lbx_import_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="lbx_file"><?php esc_html_e( 'Archivo .xlsx', 'lubrax' ); ?></label></th>
					<td><input type="file" id="lbx_file" name="lbx_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Modo', 'lubrax' ); ?></th>
					<td><label><input type="checkbox" name="lbx_dry_run" value="1" checked> <?php esc_html_e( 'Simulación (no guarda cambios)', 'lubrax' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="lbx_status"><?php esc_html_e( 'Estado de los registros nuevos', 'lubrax' ); ?></label></th>
					<td>
						<select id="lbx_status" name="lbx_status">
							<option value="publish"><?php esc_html_e( 'Publicado', 'lubrax' ); ?></option>
							<option value="draft"><?php esc_html_e( 'Borrador (para revisar antes de publicar)', 'lubrax' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Procesar archivo', 'lubrax' ) ); ?>
		</form>

		<?php
		if ( $report ) {
			lbx_render_import_report( $report );
		}
		?>
	</div>
	<?php
}

/**
 * Informe: totales, avisos generales y detalle por fila.
 */
function lbx_render_import_report( array $report ): void {
	$labels = array(
		'crear'      => __( 'Crear', 'lubrax' ),
		'actualizar' => __( 'Actualizar', 'lubrax' ),
		'error'      => __( 'Error', 'lubrax' ),
	);
	?>
	<h2><?php echo $report['dry_run'] ? esc_html__( 'Resultado de la simulación (no se guardó nada)', 'lubrax' ) : esc_html__( 'Resultado de la importación', 'lubrax' ); ?></h2>
	<ul>
		<?php foreach ( array( 'productos', 'distribuidores' ) as $type ) : ?>
			<li><strong><?php echo esc_html( ucfirst( $type ) ); ?>:</strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: crear, 2: actualizar, 3: errores. */
						__( '%1$d nuevos, %2$d actualizados, %3$d con error', 'lubrax' ),
						$report['totals'][ $type ]['crear'],
						$report['totals'][ $type ]['actualizar'],
						$report['totals'][ $type ]['error']
					)
				);
				?>
			</li>
		<?php endforeach; ?>
		<?php if ( ! $report['dry_run'] && $report['totals']['medios'] ) : ?>
			<li>
				<?php
				/* translators: %d: cantidad de archivos. */
				echo esc_html( sprintf( __( '%d imágenes y documentos en cola de descarga (segundo plano).', 'lubrax' ), $report['totals']['medios'] ) );
				if ( current_user_can( 'manage_woocommerce' ) ) {
					printf( ' <a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=' . LBX_Import_Media::HOOK ) ), esc_html__( 'Ver estado', 'lubrax' ) );
				}
				?>
			</li>
		<?php endif; ?>
	</ul>

	<?php foreach ( $report['notices'] as $notice ) : ?>
		<div class="notice notice-warning inline"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endforeach; ?>

	<?php if ( $report['rows'] ) : ?>
		<table class="widefat striped" style="margin-top:1rem">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Hoja', 'lubrax' ); ?></th>
					<th><?php esc_html_e( 'Fila', 'lubrax' ); ?></th>
					<th><?php esc_html_e( 'Nombre', 'lubrax' ); ?></th>
					<th><?php esc_html_e( 'Acción', 'lubrax' ); ?></th>
					<th><?php esc_html_e( 'Avisos', 'lubrax' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $report['rows'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( ucfirst( $row['type'] ) ); ?></td>
						<td><?php echo esc_html( (string) $row['line'] ); ?></td>
						<td>
							<?php if ( $row['post_id'] ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $row['post_id'] ) ); ?>"><?php echo esc_html( $row['name'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $row['name'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $labels[ $row['action'] ] ?? $row['action'] ); ?></td>
						<td><?php echo esc_html( implode( ' ', $row['warnings'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php elseif ( ! $report['notices'] ) : ?>
		<p><?php esc_html_e( 'El archivo no tiene filas con datos (solo encabezados).', 'lubrax' ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * WP-CLI: wp lubrax importar <archivo> [--simular] [--estado=<publish|draft>]
 * Descarga imágenes y PDF en el momento (sin cola).
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'lubrax importar',
		function ( array $args, array $assoc ) {
			$file = $args[0] ?? '';
			if ( ! is_readable( $file ) ) {
				WP_CLI::error( "No se puede leer el archivo: $file" );
			}

			$importer = new LBX_Importer(
				array(
					'dry_run'      => isset( $assoc['simular'] ),
					'status'       => $assoc['estado'] ?? 'publish',
					'inline_media' => true,
					'logger'       => fn( $line ) => WP_CLI::log( $line ),
				)
			);

			try {
				$report = $importer->run( LBX_Xlsx_Reader::read( $file ) );
			} catch ( Throwable $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			foreach ( $report['notices'] as $notice ) {
				WP_CLI::warning( $notice );
			}
			foreach ( array( 'productos', 'distribuidores' ) as $type ) {
				$t = $report['totals'][ $type ];
				WP_CLI::log( sprintf( '%s: %d nuevos, %d actualizados, %d con error', ucfirst( $type ), $t['crear'], $t['actualizar'], $t['error'] ) );
			}
			WP_CLI::success( $report['dry_run'] ? 'Simulación terminada (no se guardó nada).' : 'Importación terminada.' );
		},
		array(
			'shortdesc' => 'Importa productos y distribuidores desde la plantilla Excel.',
			'synopsis'  => array(
				array(
					'type'        => 'positional',
					'name'        => 'archivo',
					'description' => 'Ruta del .xlsx.',
				),
				array(
					'type'        => 'flag',
					'name'        => 'simular',
					'description' => 'No guarda cambios; solo muestra el informe.',
					'optional'    => true,
				),
				array(
					'type'        => 'assoc',
					'name'        => 'estado',
					'description' => 'Estado de los registros nuevos.',
					'optional'    => true,
					'default'     => 'publish',
					'options'     => array( 'publish', 'draft' ),
				),
			),
		)
	);
}
