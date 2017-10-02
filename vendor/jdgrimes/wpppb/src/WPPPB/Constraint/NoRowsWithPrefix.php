<?php

/**
 * Database table column has no rows with prefix constraint.
 *
 * @package WPPPB
 * @since 0.1.0
 */

/**
 * Database table column has no rows with prefix constraint matcher.
 *
 * @since 0.1.0
 */
class WPPPB_Constraint_NoRowsWithPrefix
	extends PHPUnit_Framework_Constraint {

	/**
	 * The table to check in.
	 *
	 * @since 0.1.0
	 *
	 * @type string $table
	 */
	protected $table;

	/**
	 * The column to check in.
	 *
	 * @since 0.1.0
	 *
	 * @type string $column
	 */
	protected $column;

	/**
	 * The prefix that should not be present.
	 *
	 * @since 0.1.0
	 *
	 * @type string $prefix
	 */
	protected $prefix;

	/**
	 * The rows in the table that have the prefix.
	 *
	 * @since 0.1.0
	 *
	 * @type array $prefixed_rows
	 */
	protected $prefixed_rows = array();

	/**
	 * Construct the class.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $prefix
	 */
	public function __construct( $table, $column, $prefix ) {

		$this->table  = esc_sql( $table );
		$this->column = esc_sql( $column );
		$this->prefix = $prefix;

		// Back-compat for PHPUnit <4.0. See JDGrimes/wp-plugin-uninstall-tester#14.
		if ( is_callable( 'parent::__construct' ) ) {
			parent::__construct();
		}
	}

	/**
	 * Checks that no rows in the specified table column have the $prefix.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix The prefix that should not be present.
	 *
	 * @return bool Whether the prefix is absent.
	 */
	public function matches( $prefix ) {

		global $wpdb;

		$prefix = esc_sql( $prefix );

		$rows = $wpdb->get_var(
			"
				SELECT COUNT(`{$this->column}`)
				FROM `{$this->table}`
				WHERE `{$this->column}` LIKE '{$prefix}%'
			"
		);

		if ( 0 == $rows ) {
			return true;
		}

		$prefixed_rows = $wpdb->get_col(
			"
				SELECT `{$this->column}`
				FROM `{$this->table}`
				WHERE `{$this->column}` LIKE '{$prefix}%'
			"
		);

		if ( is_array( $prefixed_rows ) ) {
			$this->prefixed_rows = array_unique( $prefixed_rows );
		}

		return false;
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function toString() {

		return "prefix does not exist in `{$this->table}`.`{$this->column}`.\n"
			. "The following rows were found:\n\t" . implode( "\n\t", $this->prefixed_rows ) . "\n";
	}
}

// EOF
