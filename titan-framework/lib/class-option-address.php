<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionAddress extends TitanFrameworkOption {
	public static $google_map_api_key = '';

	public $defaultSecondarySettings = array(
		'placeholder'            => '', // show this when blank
		'user'                   => false,
		'use_as_field'           => true,
		'field_name_prefix'      => null,
		'address_field_name'     => null,
		'postal_code_field_name' => null,
		'town_field_name'        => null,
		'sanitize_callbacks'     => array(),
	);

	public static function lookup_address( $string ) {
		if ( empty( $string ) ) {
			return null;
		}

		$string      = str_replace( " ", "+", urlencode( $string ) );
		$key         = self::$google_map_api_key;
		$details_url = "https://maps.googleapis.com/maps/api/geocode/json?address={$string}&sensor=false&key={$key}";

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $details_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$response = json_decode( curl_exec( $ch ), true );

		// If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
		if ( $response['status'] != 'OK' ) {
			return null;
		}

		$geometry = $response['results'][0]['geometry'];

		return array(
			'latitude'      => $geometry['location']['lat'],
			'longitude'     => $geometry['location']['lng'],
			'location_type' => $geometry['location_type'],
		);
	}

	public function customSave( $postID ) {
		if ( ! $postID ) {
			$postID = $_REQUEST['post'];
		}
		$id        = ! empty( $this->settings['field_name_prefix'] ) ? $this->settings['field_name_prefix'] : $this->getID();
		$save_fn   = $this->settings['user'] ? 'update_user_meta' : 'update_post_meta';
		$delete_fn = $this->settings['user'] ? 'delete_user_meta' : 'delete_post_meta';

		if ( $this->settings['use_as_field'] ) {
			if ( ! array_key_exists( $id, $_REQUEST ) ) {
				return false;
			}
			$address_content = $_REQUEST[ $id ];
		} else {
			if ( ! array_key_exists( $this->settings['address_field_name'], $_REQUEST ) ) {
				return true;
			}
			if ( ! array_key_exists( $this->settings['postal_code_field_name'], $_REQUEST ) ) {
				return true;
			}
			if ( ! array_key_exists( $this->settings['town_field_name'], $_REQUEST ) ) {
				return true;
			}
			$address_content = $_REQUEST[ $this->settings['address_field_name'] ] . ', ' . $_REQUEST[ $this->settings['postal_code_field_name'] ] . ' ' . $_REQUEST[ $this->settings['town_field_name'] ];
		}

		if ( ! empty( $this->settings['sanitize_callbacks'] ) ) {
			foreach ( $this->settings['sanitize_callbacks'] as $callback ) {
				$address_content = call_user_func_array( $callback, array( $address_content, $this ) );
			}
		}

		$address = self::lookup_address( $address_content );
		if ( $address ) {
			call_user_func( $save_fn, $postID, "{$id}_long", $address['longitude'] );
			call_user_func( $save_fn, $postID, "{$id}_lat", $address['latitude'] );
			call_user_func( $save_fn, $postID, "{$id}_location_type", $address['location_type'] );
		} else {
			call_user_func( $delete_fn, $postID, "{$id}_long" );
			call_user_func( $delete_fn, $postID, "{$id}_lat" );
			call_user_func( $delete_fn, $postID, "{$id}_location_type" );
		}

		return ! $this->settings['use_as_field'];
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader( true );
		if ( $this->settings['use_as_field'] ) {
			printf( "<textarea class='large-text %s' name=\"%s\" placeholder=\"%s\" id=\"%s\" rows='10' cols='50'>%s</textarea>",
				$this->settings['required'] ? 'required' : '',
				$this->getID(),
				$this->settings['placeholder'],
				$this->getID(),
				esc_textarea( stripslashes( $this->getValue() ) )
			);
		}
		self::echoLoc();
		$this->echoOptionFooter( false );
	}

	private function echoLoc( $postID = null ) {
		$get_fn = $this->settings['user'] ? 'get_user_meta' : 'get_post_meta';
		$postID = $this->getPostID( $postID );
		$id     = ! empty( $this->settings['field_name_prefix'] ) ? $this->settings['field_name_prefix'] : $this->getID();
		$loc    = call_user_func( $get_fn, $postID, "{$id}_location_type", true );
		if ( ! empty( $loc ) ) {
			$lat = call_user_func( $get_fn, $postID, "{$id}_lat", true );
			$lng = call_user_func( $get_fn, $postID, "{$id}_long", true );
			echo '<p class="' . $id . ' localized-address">Localisé <a href="http://maps.google.com/maps?q=' . $lat . ',' . $lng . '">Voir sur Google Maps</a></p>';
		} else {
			echo '<p class="' . $id . ' unlocalized-address">Adresse non localisée</p>';
		}
	}

	public function columnDisplayValue( $post_id ) {
		if ( $this->settings['use_as_field'] ) {
			printf( '<span class="large-text %s" >%s</span>',
				isset( $this->settings['is_code'] ) ? 'code' : '',
				$this->getValue( $post_id )
			);
		}
		self::echoLoc( $post_id );
	}

	public function cleanValueForSaving( $value ) {
		if ( ! empty( $this->settings['sanitize_callbacks'] ) ) {
			foreach ( $this->settings['sanitize_callbacks'] as $callback ) {
				$value = call_user_func_array( $callback, array( $value, $this ) );
			}
		}

		return $value;
	}

}
