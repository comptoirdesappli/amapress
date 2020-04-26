<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionAddress extends TitanFrameworkOption {
	public static $google_map_api_key = '';
	public static $here_map_app_id = '';
	public static $here_map_app_code = '';
	public static $geoprovider = 'google';

	public $defaultSecondarySettings = array(
		'placeholder'            => '', // show this when blank
		'user'                   => false,
		'use_as_field'           => true,
		'field_name_prefix'      => null,
		'address_field_name'     => null,
		'postal_code_field_name' => null,
		'town_field_name'        => null,
		'use_enter_gps'          => false,
		'sanitize_callbacks'     => array(),
	);

	public static function lookup_address( $string ) {
		if ( empty( $string ) || ',' == trim( $string ) ) {
			return null;
		}

		$string = preg_replace( '/\s+/', " ", trim( $string ) );
		$string = preg_replace( '/(?:\s+-\s+|,\s*)?(\d\s*\d\s*\d\s*\d\s*\d|2\s*[AB]\s*\d\s*\d\s*\d)\s+([^,]+)(?:,\s*\1\s+\2)+/i', ', $1 $2', $string );

		if ( 'google' == self::$geoprovider ) {
			$string      = urlencode( $string );
			$key         = self::$google_map_api_key;
			$details_url = "https://maps.googleapis.com/maps/api/geocode/json?address={$string}&sensor=false&key={$key}";

			$request = wp_remote_get( $details_url );
			if ( is_wp_error( $request ) ) {
				return $request;
			}

			$response = json_decode( wp_remote_retrieve_body( $request ), true );

			if ( $response['status'] != 'OK' ) {
				$res = $response['status'];
				error_log( "Google Maps resolution failed ($res): $string" );

				return null;
			}

			$geometry = $response['results'][0]['geometry'];

			return array(
				'latitude'      => $geometry['location']['lat'],
				'longitude'     => $geometry['location']['lng'],
				'location_type' => $geometry['location_type'],
			);
		} else if ( 'nominatim' == self::$geoprovider ) {
			$string      = urlencode( $string );
			$details_url = "https://nominatim.openstreetmap.org/search?q={$string}&format=json";


			$request = wp_remote_get( $details_url, [
				'headers' => 'Referer: ' . wp_get_referer()
			] );
			if ( is_wp_error( $request ) ) {
				return $request;
			}

			$response = json_decode( wp_remote_retrieve_body( $request ), true );

			if ( ! is_array( $response ) || empty( $response ) ) {
				error_log( "Nominatim resolution failed: $details_url" );

				return null;
			}

			return array(
				'latitude'      => $response[0]['lat'],
				'longitude'     => $response[0]['lon'],
				'location_type' => $response[0]['type'],
			);
		} else if ( 'here' == self::$geoprovider ) {
			if ( empty( self::$here_map_app_code ) || empty( self::$here_map_app_id ) ) {
				return new WP_Error( 'App Id et App Code Here Maps non renseignés' );
			}
			$string      = urlencode( $string );
			$app_id      = urlencode( self::$here_map_app_id );
			$app_code    = urlencode( self::$here_map_app_code );
			$details_url = "https://geocoder.api.here.com/6.2/geocode.json?app_id={$app_id}&app_code={$app_code}&searchtext={$string}";


			$request = wp_remote_get( $details_url );
			if ( is_wp_error( $request ) ) {
				return $request;
			}

			$response = json_decode( wp_remote_retrieve_body( $request ), true );

			if ( ! is_array( $response ) || empty( $response['Response']['View'][0]['Result'] ) ) {
				error_log( "Here Maps resolution failed: $details_url" );

				return null;
			}

			return array(
				'latitude'      => $response['Response']['View'][0]['Result'][0]['Location']['DisplayPosition']['Latitude'],
				'longitude'     => $response['Response']['View'][0]['Result'][0]['Location']['DisplayPosition']['Longitude'],
				'location_type' => $response['Response']['View'][0]['Result'][0]['MatchLevel'],
			);
		} else {
			return null;
		}
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

		$custom_lat = isset( $_REQUEST["$id-custom-lat"] ) ? floatval( $_REQUEST["$id-custom-lat"] ) : 0;
		$custom_lng = isset( $_REQUEST["$id-custom-lng"] ) ? floatval( $_REQUEST["$id-custom-lng"] ) : 0;
		if ( ! empty( $custom_lat ) && ! empty( $custom_lng ) ) {
			call_user_func( $save_fn, $postID, "{$id}_cusgeo", [ $custom_lat, $custom_lng ] );
			call_user_func( $save_fn, $postID, "{$id}_long", $custom_lng );
			call_user_func( $save_fn, $postID, "{$id}_lat", $custom_lat );
			call_user_func( $save_fn, $postID, "{$id}_location_type", 'm' );
			call_user_func( $delete_fn, $postID, "{$id}_loc_err" );
		} else {
			if ( ! empty( $this->settings['sanitize_callbacks'] ) ) {
				foreach ( $this->settings['sanitize_callbacks'] as $callback ) {
					$address_content = call_user_func_array( $callback, array( $address_content, $this ) );
				}
			}

			$address = self::lookup_address( $address_content );
			if ( $address && ! is_wp_error( $address ) ) {
				call_user_func( $delete_fn, $postID, "{$id}_cusgeo" );
				call_user_func( $save_fn, $postID, "{$id}_long", $address['longitude'] );
				call_user_func( $save_fn, $postID, "{$id}_lat", $address['latitude'] );
				call_user_func( $save_fn, $postID, "{$id}_location_type", $address['location_type'] );
				call_user_func( $delete_fn, $postID, "{$id}_loc_err" );
			} else {
				call_user_func( $delete_fn, $postID, "{$id}_cusgeo" );
				call_user_func( $delete_fn, $postID, "{$id}_long" );
				call_user_func( $delete_fn, $postID, "{$id}_lat" );
				call_user_func( $delete_fn, $postID, "{$id}_location_type" );
				if ( is_wp_error( $address ) ) {
					/** @var WP_Error $address */
					call_user_func( $save_fn, $postID, "{$id}_loc_err", $address->get_error_message() );
				} else {
					call_user_func( $delete_fn, $postID, "{$id}_loc_err" );
				}
			}
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
		self::echoLoc( null, true );
		$this->echoOptionFooter( false );
	}

	private function echoLoc( $postID = null, $with_help = false, $for_column = false ) {
		$get_fn     = $this->settings['user'] ? 'get_user_meta' : 'get_post_meta';
		$postID     = $this->getPostID( $postID );
		$id         = ! empty( $this->settings['field_name_prefix'] ) ? $this->settings['field_name_prefix'] : $this->getID();
		$loc        = call_user_func( $get_fn, $postID, "{$id}_location_type", true );
		$coords_gps = 'inconnues';
		if ( ! empty( $loc ) ) {
			$lat        = call_user_func( $get_fn, $postID, "{$id}_lat", true );
			$lng        = call_user_func( $get_fn, $postID, "{$id}_long", true );
			$coords_gps = "lat.=$lat;lng.=$lng";
			if ( 'google' != self::$geoprovider ) {
				echo '<p class="' . $id . ' localized-address">Localisé <a target="_blank" href="https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=17/' . $lat . '/' . $lng . '">Voir sur Open Street Map</a></p>';
			} else {
				echo '<p class="' . $id . ' localized-address">Localisé <a target="_blank" href="http://maps.google.com/maps?q=' . $lat . ',' . $lng . '">Voir sur Google Maps</a></p>';
			}
		} else {
			$loc_err = call_user_func( $get_fn, $postID, "{$id}_loc_err", true );
			if ( ! empty( $loc_err ) ) {
				$loc_err = " ($loc_err)";
			}
			if ( 'google' == self::$geoprovider && empty( self::$google_map_api_key ) ) {
				echo "<p class='$id unlocalized-address'><strong>Pas de clé Google API configurée</strong> - Adresse non localisée$loc_err</p>";
			} else {
				if ( $this->settings['use_as_field'] ) {
					$full_address = $this->getValue( $postID );
				} elseif ( ! empty( $this->settings['address_field_name'] )
				           && ! empty( $this->settings['postal_code_field_name'] )
				           && ! empty( $this->settings['town_field_name'] ) ) {
					$full_address = call_user_func( $get_fn, $postID, $this->settings['address_field_name'], true ) .
					                ', ' . call_user_func( $get_fn, $postID, $this->settings['postal_code_field_name'], true ) .
					                ' ' . call_user_func( $get_fn, $postID, $this->settings['town_field_name'], true );
				} elseif ( ! empty( $this->settings['address_field_name'] ) ) {
					$full_address = call_user_func( $get_fn, $postID, $this->settings['address_field_name'], true );
				}
				$help = '';
				if ( $with_help ) {
					if ( ! empty( $full_address ) ) {
						$help = '<br/><a target="_blank" href="' . esc_url(
								'https://www.openstreetmap.org/search?query=' . $full_address
							) . '">Rechercher l\'adresse sur OpenStreetMap</a> afin d\'en trouver la bonne forme (par exemple, sans précision du batiment, étage, ...)';
					}
				}
				if ( ! empty( $full_address ) ) {
					echo "<p class='$id unlocalized-address'>Adresse non localisée$loc_err$help</p>";
				} else {
					echo "<p class='$id'>Pas d'adresse</p>";
				}
			}
		}

		if ( ! $for_column && $this->settings['use_enter_gps'] ) {
			$cusgeo = call_user_func( $get_fn, $postID, "{$id}_cusgeo", true );
			if ( empty( $cusgeo ) ) {
				$cusgeo = [ 0, 0 ];
			}
			echo "<p>Coordonnées GPS actuelles: $coords_gps<br/>
Saisie manuelle: lat.=<input name='$id-custom-lat' type='number' min='-180' max='180' value='{$cusgeo[0]}' step='any' /> ; lng.=<input name='$id-custom-lng' type='number' min='-180' max='180' value='{$cusgeo[1]}' step='any' /> </p>";
		}
	}

	public function columnDisplayValue( $post_id ) {
		if ( $this->settings['use_as_field'] ) {
			printf( '<span class="large-text %s" >%s</span>',
				isset( $this->settings['is_code'] ) ? 'code' : '',
				$this->getValue( $post_id )
			);
		}
		self::echoLoc( $post_id, false, true );
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
