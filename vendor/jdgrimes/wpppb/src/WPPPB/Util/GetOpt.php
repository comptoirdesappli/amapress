<?php

/**
 * An extension of PHPUnit's commandline option parsing utility.
 *
 * @package WPPPB
 * @since 0.1.0
 */

/**
 * Check the 'group' long option to see if we are running the uninstall group.
 *
 * @since 0.1.0
 */
class WPPPB_Util_GetOpt extends PHPUnit_Util_Getopt {

	/**
	 * The long options we are interested in.
	 *
	 * @since 0.1.0
	 *
	 * @type string[] $longOptions
	 */
	protected $longOptions = array( 'configuration=' );

	/**
	 * The short options we are interested in.
	 *
	 * @since 0.1.0
	 *
	 * @type string[] $shortOptions
	 */
	protected $shortOptions = 'c:';

	/**
	 * Whether the uninstall group is being run.
	 *
	 * @since 0.1.0
	 *
	 * @type bool $uninstall_group
	 */
	protected $uninstall_group = false;

	/**
	 * Parse the options to see if we are running the uninstall group.
	 *
	 * @since 0.1.0
	 *
	 * @param array $argv The commandline arguments.
	 */
	public function __construct( $argv ) {

		array_shift( $argv );

		$options = array();

		while ( list( $i, $arg ) = each( $argv ) ) {

			try {

				if ( strlen( $arg ) > 1 && $arg[0] === '-' ) {
					if ( $arg[1] === '-' ) {
						PHPUnit_Util_Getopt::parseLongOption(
							substr( $arg, 2 ),
							$this->longOptions,
							$options,
							$argv
						);
					} else {
						PHPUnit_Util_Getopt::parseShortOption(
							substr( $arg, 1 ),
							$this->shortOptions,
							$options,
							$argv
						);
					}
				}

			} catch ( PHPUnit_Framework_Exception $e ) {

				// Right now we don't really care what the arguments are like.
				continue;
			}
		}

		foreach ( $options as $option ) {

			switch ( $option[0] ) {

				case '--configuration' :
				case 'c':
					$this->uninstall_group = strpos( $option[1], 'uninstall' );
				break 2;
			}
		}

		if ( ! $this->uninstall_group ) {
			echo 'Not running plugin install/uninstall tests... To execute these, use -c phpunit.uninstall.xml.dist.' . PHP_EOL;
		} else {
			echo 'Running plugin install/uninstall tests...' . PHP_EOL;
		}
	}

	/**
	 * Check if the uninstall group is being run.
	 *
	 * @since 0.1.0
	 *
	 * @return bool Whether the uninstall group is being run.
	 */
	public function running_uninstall_group() {

		return $this->uninstall_group;
	}
}

// EOF
