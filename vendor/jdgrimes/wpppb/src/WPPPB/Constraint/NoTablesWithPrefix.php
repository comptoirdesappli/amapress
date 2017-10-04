<?php

/**
 * Database has no tables with prefix constraint.
 *
 * @package WPPPB
 * @since 0.2.0
 */

/**
 * Database has no tables with prefix constraint matcher.
 *
 * @since 0.2.0
 */
class WPPPB_Constraint_NoTablesWithPrefix extends PHPUnit_Framework_Constraint {

	/**
	 * The tables in the database that have the prefix.
	 *
	 * @since 0.2.0
	 *
	 * @type array $prefixed_tables
	 */
	protected $prefixed_tables = array();

	/**
	 * Checks that no rows in the specified table column have the $prefix.
	 *
	 * @since 0.2.0
	 *
	 * @param string $prefix The prefix that should not be present.
	 *
	 * @return bool Whether the prefix is absent.
	 */
	public function matches( $prefix ) {

		global $wpdb;

		$prefix = esc_sql( $prefix ) . '%';

		$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefix ) );

		if ( 0 === count( $tables ) ) {
			return true;
		}

		$this->prefixed_tables = $tables;

		return false;
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function toString() {

		return "prefix does not match any tables in the database.\n"
		       . "The following tables were found:\n\t" . implode( "\n\t", $this->prefixed_tables ) . "\n";
	}
}

// EOF
