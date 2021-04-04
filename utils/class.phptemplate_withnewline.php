<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Phptemplate_withnewline extends \PhpOffice\PhpWord\TemplateProcessor {

	public function __construct( $documentTemplate = null ) {
		parent::__construct( $documentTemplate );
	}

	private static function extractText( $obj, $nested = 0 ) {
		if ( null == $obj ) {
			return '';
		}
		$txt = "";
		if ( method_exists( $obj, 'getSections' ) ) {
			foreach ( $obj->getSections() as $section ) {
				$txt .= " " . self::extractText( $section, $nested + 1 );
			}
		} elseif ( method_exists( $obj, 'getElements' ) ) {
			foreach ( $obj->getElements() as $element ) {
				$txt .= self::extractText( $element, $nested + 1 );
			}
		} elseif ( method_exists( $obj, 'getText' ) ) {
			// --------------------------------------------------------------
			// THIS IS THE DIFFERENT BLOCK
			$extracted = $obj->getText();
			if ( is_string( $extracted ) === true ) {
				$txt .= $extracted;
			} else {
				$txt .= self::extractText( $extracted, $nested + 1 );
			}
			// --------------------------------------------------------------
		} elseif ( method_exists( $obj, 'getRows' ) ) {
			foreach ( $obj->getRows() as $row ) {
				$txt .= " " . self::extractText( $row, $nested + 1 );
			}
		} elseif ( method_exists( $obj, 'getCells' ) ) {
			foreach ( $obj->getCells() as $cell ) {
				$txt .= " " . self::extractText( $cell, $nested + 1 );
			}
		}

		return $txt;
	}

	public static function getAllPlaceholders( $document_file_name ) {
		$phpWord      = \PhpOffice\PhpWord\IOFactory::load( $document_file_name );
		$text         = self::extractText( $phpWord );
		$placeholders = [];
		if ( preg_match_all( '/\\$\\{([^\\}]+)\\}/', $text, $placeholders ) ) {
			return $placeholders[1];
		}

		return [];
	}

	public static function getUnknownPlaceholders( $document_file_name, $placeholders ) {
		$placeholder_names = [];
		if ( ! is_array( $placeholders ) ) {
			$placeholders = [];
		}
		foreach ( array_keys( $placeholders ) as $placeholder ) {
			$placeholder_names[] = preg_replace( '/#.+/', '', $placeholder );
		}

		return array_unique( array_diff( self::getAllPlaceholders( $document_file_name ), $placeholder_names ) );
	}

	public static function getPlaceholderStatus( $model_file, $placeholders, $model_title ) {
		if ( empty( $model_file ) ) {
			return [
				'message' => $model_title . ': pas de modèle DOCX associé',
				'status'  => 'info'
			];
		}

		try {
			$zipClass = new ZipArchive();
			if ( ! $zipClass->open( $model_file ) ) {
				throw new Exception( __( 'Fichier non DOCX ou corrompu', 'amapress' ) );
			}
			$is_invalid = @empty( $zipClass->count() );
			@$zipClass->close();
			if ( $is_invalid ) {
				throw new Exception( __( 'Fichier non DOCX ou corrompu', 'amapress' ) );
			}

			$unknowns = Phptemplate_withnewline::getUnknownPlaceholders( $model_file, $placeholders );
			if ( ! empty( $unknowns ) ) {
				return [
					'message' => sprintf( __( '%s: placeholders DOCX inconnus : %s ; causes possibles: mauvais type de modèle ou erreur de frappe', 'amapress' ), $model_title, implode( ', ', array_map( function ( $p ) {
						return '${' . $p . '}';
					}, $unknowns ) ) ),
					'status'  => 'error'
				];
			}
		} catch ( Exception $ex ) {
			return [
				'message' => sprintf( __( '%s: modèle DOCX invalide: %s', 'amapress' ), $model_title, $ex->getMessage() ),
				'status'  => 'error'
			];
		}

		return true;
	}

	public function setValue( $search, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT ) {
		$replace = str_replace(
			array( '<br/>', '<br />', '<br>' ), "\n", $replace
		);
		$replace = strip_tags( $replace );
		$replace = esc_html( $replace );
		\PhpOffice\PhpWord\TemplateProcessor::setValue( $search, $replace, $limit ); // TODO: Change the autogenerated stub
	}


	/**
	 * Find and replace macros in the given XML section.
	 *
	 * @param mixed $search
	 * @param mixed $replace
	 * @param string $documentPartXML
	 * @param int $limit
	 *
	 * @return string
	 */
	protected function setValueForPart( $search, $replace, $documentPartXML, $limit ) {
		// Shift-Enter
		if ( is_array( $replace ) ) {
			foreach ( $replace as &$item ) {
				$item = preg_replace( '~\R~u', '</w:t><w:br/><w:t>', $item );
			}
		} else {
			$replace = preg_replace( '~\R~u', '</w:t><w:br/><w:t>', $replace );
		}

		// Note: we can't use the same function for both cases here, because of performance considerations.
		if ( self::MAXIMUM_REPLACEMENTS_DEFAULT === $limit ) {
			return str_replace( $search, $replace, $documentPartXML );
		}
		$regExpEscaper = new PhpOffice\PhpWord\Escaper\RegExp();

		return preg_replace( $regExpEscaper->escape( $search ), $replace, $documentPartXML, $limit );
	}
}
