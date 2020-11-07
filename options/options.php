<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_register_permalien_options() {
	add_settings_section( 'amapress_permalink', 'Amapress', function ( $args ) {
		echo '<p>' . 'Cette section permet de régler les slugs des types de post d\'Amapress' . '</p>';
	}, 'permalink' );
	foreach ( AmapressEntities::getPostTypes() as $name => $conf ) {
		if ( isset( $conf['public'] ) && $conf['public'] === true ) {
			register_setting( 'permalink', "amps_{$name}_slug", 'string' );
			add_settings_field( "amps_{$name}_slug",
				"{$conf['singular']} slug",
				'amapress_output_permalink_option',
				'permalink',
				'amapress_permalink',
				[
					'id'      => "amps_{$name}_slug",
					'default' => $conf['slug'],
				] );
		}
	}
}

function amapress_output_permalink_option( $args ) {
	$option = get_option( $args['id'], $args['default'] );
	echo '<input type="text" id="' . $args['id'] . '" name="' . $args['id'] . '" value="' . $option . '" class="regular-text" />';
}

//TODO do it faster if needed
//add_action( 'admin_init', 'amapress_register_permalien_options' );

