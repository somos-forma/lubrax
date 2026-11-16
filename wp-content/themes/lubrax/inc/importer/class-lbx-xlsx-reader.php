<?php
/**
 * Lector mínimo de .xlsx (ZipArchive + SimpleXML): devuelve las filas de cada hoja como texto.
 *
 * Soporta textos compartidos (incluido texto enriquecido), textos en línea, números, booleanos y fórmulas
 * (usa el último valor calculado). No lee estilos, así que las fechas llegan como número de serie de Excel.
 *
 * @package Lubrax
 */

defined( 'ABSPATH' ) || exit;

class LBX_Xlsx_Reader {

	private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
	private const NS_REL  = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

	/**
	 * @return array<string, array<int, array<int, string>>> Hojas por nombre → filas (índice desde 1) → celdas (índice desde 0).
	 * @throws RuntimeException Si el archivo no es un .xlsx válido.
	 */
	public static function read( string $file ): array {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new RuntimeException( __( 'El servidor no tiene la extensión zip de PHP.', 'lubrax' ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $file ) ) {
			throw new RuntimeException( __( 'No se pudo abrir el archivo. ¿Es un .xlsx?', 'lubrax' ) );
		}

		try {
			$strings = self::shared_strings( $zip );
			$sheets  = array();

			foreach ( self::sheet_paths( $zip ) as $name => $path ) {
				$xml = $zip->getFromName( $path );
				if ( false !== $xml ) {
					$sheets[ $name ] = self::rows( $xml, $strings );
				}
			}
		} finally {
			$zip->close();
		}

		return $sheets;
	}

	/**
	 * Nombre de cada hoja → ruta de su XML dentro del zip.
	 */
	private static function sheet_paths( ZipArchive $zip ): array {
		$workbook = self::xml( $zip->getFromName( 'xl/workbook.xml' ) );
		$rels     = self::xml( $zip->getFromName( 'xl/_rels/workbook.xml.rels' ) );

		$targets = array();
		foreach ( $rels->Relationship as $rel ) {
			$target                            = ltrim( (string) $rel['Target'], '/' );
			$targets[ (string) $rel['Id'] ] = str_starts_with( $target, 'xl/' ) ? $target : 'xl/' . $target;
		}

		// Ojo: tras children( $ns ), $el['attr'] busca el atributo en ese namespace. Los atributos sin
		// prefijo se leen con attributes().
		$paths = array();
		foreach ( $workbook->children( self::NS_MAIN )->sheets->sheet as $sheet ) {
			$id = (string) $sheet->attributes( self::NS_REL )->id;
			if ( isset( $targets[ $id ] ) ) {
				$paths[ (string) $sheet->attributes()->name ] = $targets[ $id ];
			}
		}

		return $paths;
	}

	private static function shared_strings( ZipArchive $zip ): array {
		$xml = $zip->getFromName( 'xl/sharedStrings.xml' );
		if ( false === $xml ) {
			return array();
		}

		$strings = array();
		foreach ( self::xml( $xml )->children( self::NS_MAIN )->si as $si ) {
			$strings[] = self::text( $si );
		}
		return $strings;
	}

	/**
	 * Texto de un <si> o <is>: <t> directo o la suma de los <r><t> (texto enriquecido).
	 */
	private static function text( SimpleXMLElement $node ): string {
		$node = $node->children( self::NS_MAIN );
		if ( isset( $node->t ) ) {
			return (string) $node->t;
		}
		$text = '';
		foreach ( $node->r as $run ) {
			$text .= (string) $run->children( self::NS_MAIN )->t;
		}
		return $text;
	}

	private static function rows( string $xml, array $strings ): array {
		$rows = array();

		foreach ( self::xml( $xml )->children( self::NS_MAIN )->sheetData->row as $row ) {
			$cells = array();
			foreach ( $row->children( self::NS_MAIN )->c as $cell ) {
				$col           = self::column_index( (string) $cell->attributes()->r );
				$type          = (string) $cell->attributes()->t;
				$children      = $cell->children( self::NS_MAIN );
				$value         = isset( $children->v ) ? (string) $children->v : '';
				$cells[ $col ] = match ( $type ) {
					's'         => $strings[ (int) $value ] ?? '',
					'inlineStr' => isset( $children->is ) ? self::text( $children->is ) : '',
					'b'         => '1' === $value ? '1' : '0',
					default     => $value,
				};
			}

			if ( $cells ) {
				$max  = max( array_keys( $cells ) );
				$line = array_fill( 0, $max + 1, '' );
				foreach ( $cells as $col => $value ) {
					$line[ $col ] = trim( $value );
				}
				$rows[ (int) $row->attributes()->r ] = $line;
			}
		}

		return $rows;
	}

	/**
	 * "C12" → 2 (A = 0).
	 */
	private static function column_index( string $ref ): int {
		$letters = preg_replace( '/\d+/', '', strtoupper( $ref ) );
		$index   = 0;
		foreach ( str_split( $letters ) as $letter ) {
			$index = $index * 26 + ( ord( $letter ) - 64 );
		}
		return $index - 1;
	}

	private static function xml( $content ): SimpleXMLElement {
		$xml = false === $content ? false : simplexml_load_string( $content, 'SimpleXMLElement', LIBXML_NONET );
		if ( false === $xml ) {
			throw new RuntimeException( __( 'El archivo .xlsx está dañado o incompleto.', 'lubrax' ) );
		}
		return $xml;
	}
}
