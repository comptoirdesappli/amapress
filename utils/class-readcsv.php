<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * @package uc_stock_update
 * @version 6.x-2.0.2
 * @licence GNU GPL v2
 */
// $Id: ReadCSV.inc 3 2011-09-19 02:32:14Z david $

/**
 * Use this to read CSV files. PHP's fgetcsv() does not conform to RFC
 * 4180. In particular, it doesn't handle the correct quote escaping
 * syntax. See http://tools.ietf.org/html/rfc4180
 *
 * David Houlder May 2010
 * http://davidhoulder.com
 */
class ReadCSV {
	private $rows;

	public function __construct( $inputFileName, $skip = "" ) {
		require_once( AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php' );

		$inputFileType = PHPExcel_IOFactory::identify( $inputFileName );
		if ( 'CSV' == $inputFileType ) {
			throw new Exception( 'Les fichiers CSV ne sont plus supportés pour cause d\'intéropérabilité. Veuillez convertir en XLSX, ODS ou XLS' );
		}
		$objReader     = PHPExcel_IOFactory::createReader( $inputFileType );
		$objPHPExcel   = $objReader->load( $inputFileName );
		$this->rows    = $objPHPExcel->getActiveSheet()->toArray( null, true, true );
//		var_dump($this->rows);
//		die();
	}

	/**
	 * Get next record from CSV file.
	 *
	 * @return array()
	 *  array of strings from the next record in the CSV file, or NULL if
	 *  there are no more records.
	 */
	public function get_row() {
		return array_shift( $this->rows );
	}
}
