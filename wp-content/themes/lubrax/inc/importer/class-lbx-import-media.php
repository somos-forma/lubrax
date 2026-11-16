<?php
/**
 * Descarga de medios del importador: imagen principal del producto y documentos PDF (ficha técnica, SDS).
 *
 * - Acepta enlaces públicos directos, de Google Drive (/file/d/ID/…, ?id=ID) y de Dropbox.
 * - Cada URL se descarga una sola vez: el adjunto guarda su URL de origen (_lbx_source_url) y se reutiliza.
 * - En segundo plano usa Action Scheduler (incluido en WooCommerce), grupo "lbx-importador".
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

class LBX_Import_Media {

	public const HOOK  = 'lbx_import_media';
	public const GROUP = 'lbx-importador';

	private const MIME_EXTENSIONS = array(
		'image/jpeg'      => 'jpg',
		'image/png'       => 'png',
		'image/webp'      => 'webp',
		'image/avif'      => 'avif',
		'application/pdf' => 'pdf',
	);

	public static function init(): void {
		add_action( self::HOOK, array( __CLASS__, 'process' ), 10, 4 );
	}

	/**
	 * Procesa en el momento ($inline) o encola la descarga.
	 *
	 * @param string $kind     'imagen' (imagen principal) o 'documento'.
	 * @param string $doc_type 'ficha-tecnica' | 'hoja-seguridad' (solo documentos).
	 * @return true|WP_Error
	 */
	public static function handle( int $post_id, string $kind, string $url, string $doc_type, bool $inline ) {
		if ( $inline || ! function_exists( 'as_enqueue_async_action' ) ) {
			return self::process( $post_id, $kind, $url, $doc_type );
		}

		$args = array( $post_id, $kind, $url, $doc_type );
		if ( ! as_has_scheduled_action( self::HOOK, $args, self::GROUP ) ) {
			as_enqueue_async_action( self::HOOK, $args, self::GROUP );
		}
		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	public static function process( int $post_id, string $kind, string $url, string $doc_type = '' ) {
		$attachment_id = self::find_attachment( $url );

		// Un adjunto reutilizado también debe ser del tipo esperado (imagen o PDF).
		if ( $attachment_id && ( 'imagen' === $kind ) !== wp_attachment_is_image( $attachment_id ) ) {
			$error = new WP_Error(
				'lbx_download',
				/* translators: %s: URL. */
				sprintf( 'imagen' === $kind ? __( 'Se esperaba una imagen en %s.', 'lubrax' ) : __( 'Se esperaba un PDF en %s.', 'lubrax' ), $url )
			);
			if ( doing_action( self::HOOK ) ) {
				throw new RuntimeException( esc_html( $error->get_error_message() ) );
			}
			return $error;
		}

		if ( ! $attachment_id ) {
			$attachment_id = self::download( $post_id, $url, 'imagen' === $kind ? 'image' : 'pdf' );
			if ( is_wp_error( $attachment_id ) ) {
				// En segundo plano, el error queda en el registro de Action Scheduler.
				if ( doing_action( self::HOOK ) ) {
					throw new RuntimeException( esc_html( $attachment_id->get_error_message() ) );
				}
				return $attachment_id;
			}
		}

		if ( 'imagen' === $kind ) {
			set_post_thumbnail( $post_id, $attachment_id );
		} else {
			self::attach_document( $post_id, $attachment_id, $doc_type );
		}

		return true;
	}

	/**
	 * Agrega o reemplaza el documento del mismo tipo en el repetidor "documentos".
	 */
	private static function attach_document( int $post_id, int $attachment_id, string $doc_type ): void {
		$rows  = array_values( array_filter( (array) get_field( lbx_field_key( 'documentos', $post_id ), $post_id ) ) );
		$found = false;

		foreach ( $rows as &$row ) {
			if ( ( $row['tipo'] ?? '' ) === $doc_type ) {
				$row['archivo'] = $attachment_id;
				$found          = true;
				break;
			}
		}
		unset( $row );

		if ( ! $found ) {
			$rows[] = array(
				'tipo'        => $doc_type,
				'archivo'     => $attachment_id,
				'titulo'      => '',
				'actualizado' => '',
			);
		}

		lbx_update_field( 'documentos', $rows, $post_id );
	}

	private static function find_attachment( string $url ): int {
		// Archivo de la biblioteca de este sitio (por ejemplo, en un Excel exportado): se reutiliza sin descargarlo.
		$local = attachment_url_to_postid( $url );
		if ( $local ) {
			return $local;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_lbx_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Descarga el archivo y lo sube a la biblioteca de medios, asociado al producto.
	 *
	 * @param string $expected 'image' o 'pdf'.
	 * @return int|WP_Error ID del adjunto.
	 */
	private static function download( int $post_id, string $url, string $expected ) {
		$response = wp_safe_remote_get(
			self::direct_url( $url ),
			array(
				'timeout'     => 60,
				'redirection' => 5,
			)
		);
		if ( is_wp_error( $response ) ) {
			/* translators: 1: URL, 2: error. */
			return new WP_Error( 'lbx_download', sprintf( __( 'No se pudo descargar %1$s: %2$s', 'lubrax' ), $url, $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$mime = strtolower( trim( explode( ';', (string) wp_remote_retrieve_header( $response, 'content-type' ) )[0] ) );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code || '' === $body ) {
			/* translators: 1: código HTTP, 2: URL. */
			return new WP_Error( 'lbx_download', sprintf( __( 'Error %1$d al descargar %2$s.', 'lubrax' ), $code, $url ) );
		}
		if ( 'text/html' === $mime ) {
			/* translators: %s: URL. */
			return new WP_Error( 'lbx_download', sprintf( __( 'El enlace no es público o no apunta a un archivo: %s (en Google Drive, compártelo como "Cualquier persona con el enlace").', 'lubrax' ), $url ) );
		}

		// Algunos servidores responden application/octet-stream: se deduce por el contenido.
		if ( ! isset( self::MIME_EXTENSIONS[ $mime ] ) ) {
			$mime = self::sniff_mime( $body );
		}
		$is_image = str_starts_with( $mime, 'image/' );
		if ( ! isset( self::MIME_EXTENSIONS[ $mime ] ) || ( 'image' === $expected ) !== $is_image ) {
			/* translators: 1: tipo esperado, 2: URL. */
			return new WP_Error( 'lbx_download', sprintf( __( 'Se esperaba %1$s en %2$s.', 'lubrax' ), 'image' === $expected ? __( 'una imagen (JPG, PNG, WebP o AVIF)', 'lubrax' ) : __( 'un PDF', 'lubrax' ), $url ) );
		}

		$filename = self::filename( $response, $url, $post_id, self::MIME_EXTENSIONS[ $mime ] );
		$tmp      = wp_tempnam( $filename );
		file_put_contents( $tmp, $body ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_sideload(
			array(
				'name'     => $filename,
				'tmp_name' => $tmp,
			),
			$post_id,
			get_the_title( $post_id )
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp );
			return $attachment_id;
		}

		update_post_meta( $attachment_id, '_lbx_source_url', $url );
		if ( $is_image ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', get_the_title( $post_id ) );
		}

		return (int) $attachment_id;
	}

	/**
	 * Enlaces para compartir → descarga directa.
	 */
	public static function direct_url( string $url ): string {
		if ( preg_match( '#drive\.google\.com/(?:file/d/|open\?id=|uc\?(?:.*&)?id=)([\w-]+)#', $url, $m ) ) {
			return 'https://drive.google.com/uc?export=download&id=' . $m[1];
		}
		if ( str_contains( $url, 'dropbox.com' ) ) {
			return remove_query_arg( 'dl', $url ) . ( str_contains( $url, '?' ) ? '&' : '?' ) . 'dl=1';
		}
		return $url;
	}

	private static function sniff_mime( string $body ): string {
		$head = substr( $body, 0, 16 );
		return match ( true ) {
			str_starts_with( $head, '%PDF' )                                    => 'application/pdf',
			str_starts_with( $head, "\xFF\xD8\xFF" )                            => 'image/jpeg',
			str_starts_with( $head, "\x89PNG" )                                 => 'image/png',
			str_starts_with( $head, 'RIFF' ) && 'WEBP' === substr( $head, 8, 4 ) => 'image/webp',
			'ftypavif' === substr( $head, 4, 8 )                                => 'image/avif',
			default                                                              => '',
		};
	}

	/**
	 * Nombre del archivo: el de Content-Disposition, el de la URL o el slug del producto.
	 */
	private static function filename( $response, string $url, int $post_id, string $ext ): string {
		$name        = '';
		$disposition = (string) wp_remote_retrieve_header( $response, 'content-disposition' );
		if ( preg_match( '/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $m ) ) {
			$name = rawurldecode( $m[1] );
		} elseif ( ! str_contains( $url, 'drive.google.com' ) ) {
			$name = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		}

		$name = pathinfo( $name, PATHINFO_FILENAME ) ?: get_post_field( 'post_name', $post_id ) ?: 'archivo';

		return sanitize_file_name( $name . '.' . $ext );
	}
}

LBX_Import_Media::init();
