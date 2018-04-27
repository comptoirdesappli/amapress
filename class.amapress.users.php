<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * class short summary.
 *
 * class description.
 *
 * @version 1.0
 * @author Guillaume
 */
class AmapressUsers {
	public static $initiated = false;

//	private static $vp = null;

	public static function to_displayname( $user ) {
		$dn = $user->display_name;
		if ( ! empty( $user->last_name ) ) {
			$dn = sprintf( '%s %s', $user->first_name, $user->last_name );
		}

		return $dn;
//        return '<a href="' . get_author_posts_url($user->ID) . '">' . $dn . '</a>';
	}

	public static function unaccent( $string ) {
		if ( ! preg_match( '/[\x80-\xff]/', $string ) ) {
			return $string;
		}

		$chars = [
			// Decompositions for Latin-1 Supplement
			chr( 194 ) . chr( 170 )              => 'a',
			chr( 194 ) . chr( 186 )              => 'o',
			chr( 195 ) . chr( 128 )              => 'A',
			chr( 195 ) . chr( 129 )              => 'A',
			chr( 195 ) . chr( 130 )              => 'A',
			chr( 195 ) . chr( 131 )              => 'A',
			chr( 195 ) . chr( 132 )              => 'A',
			chr( 195 ) . chr( 133 )              => 'A',
			chr( 195 ) . chr( 134 )              => 'AE',
			chr( 195 ) . chr( 135 )              => 'C',
			chr( 195 ) . chr( 136 )              => 'E',
			chr( 195 ) . chr( 137 )              => 'E',
			chr( 195 ) . chr( 138 )              => 'E',
			chr( 195 ) . chr( 139 )              => 'E',
			chr( 195 ) . chr( 140 )              => 'I',
			chr( 195 ) . chr( 141 )              => 'I',
			chr( 195 ) . chr( 142 )              => 'I',
			chr( 195 ) . chr( 143 )              => 'I',
			chr( 195 ) . chr( 144 )              => 'D',
			chr( 195 ) . chr( 145 )              => 'N',
			chr( 195 ) . chr( 146 )              => 'O',
			chr( 195 ) . chr( 147 )              => 'O',
			chr( 195 ) . chr( 148 )              => 'O',
			chr( 195 ) . chr( 149 )              => 'O',
			chr( 195 ) . chr( 150 )              => 'O',
			chr( 195 ) . chr( 153 )              => 'U',
			chr( 195 ) . chr( 154 )              => 'U',
			chr( 195 ) . chr( 155 )              => 'U',
			chr( 195 ) . chr( 156 )              => 'U',
			chr( 195 ) . chr( 157 )              => 'Y',
			chr( 195 ) . chr( 158 )              => 'TH',
			chr( 195 ) . chr( 159 )              => 's',
			chr( 195 ) . chr( 160 )              => 'a',
			chr( 195 ) . chr( 161 )              => 'a',
			chr( 195 ) . chr( 162 )              => 'a',
			chr( 195 ) . chr( 163 )              => 'a',
			chr( 195 ) . chr( 164 )              => 'a',
			chr( 195 ) . chr( 165 )              => 'a',
			chr( 195 ) . chr( 166 )              => 'ae',
			chr( 195 ) . chr( 167 )              => 'c',
			chr( 195 ) . chr( 168 )              => 'e',
			chr( 195 ) . chr( 169 )              => 'e',
			chr( 195 ) . chr( 170 )              => 'e',
			chr( 195 ) . chr( 171 )              => 'e',
			chr( 195 ) . chr( 172 )              => 'i',
			chr( 195 ) . chr( 173 )              => 'i',
			chr( 195 ) . chr( 174 )              => 'i',
			chr( 195 ) . chr( 175 )              => 'i',
			chr( 195 ) . chr( 176 )              => 'd',
			chr( 195 ) . chr( 177 )              => 'n',
			chr( 195 ) . chr( 178 )              => 'o',
			chr( 195 ) . chr( 179 )              => 'o',
			chr( 195 ) . chr( 180 )              => 'o',
			chr( 195 ) . chr( 181 )              => 'o',
			chr( 195 ) . chr( 182 )              => 'o',
			chr( 195 ) . chr( 184 )              => 'o',
			chr( 195 ) . chr( 185 )              => 'u',
			chr( 195 ) . chr( 186 )              => 'u',
			chr( 195 ) . chr( 187 )              => 'u',
			chr( 195 ) . chr( 188 )              => 'u',
			chr( 195 ) . chr( 189 )              => 'y',
			chr( 195 ) . chr( 190 )              => 'th',
			chr( 195 ) . chr( 191 )              => 'y',
			chr( 195 ) . chr( 152 )              => 'O',
			// Decompositions for Latin Extended-A
			chr( 196 ) . chr( 128 )              => 'A',
			chr( 196 ) . chr( 129 )              => 'a',
			chr( 196 ) . chr( 130 )              => 'A',
			chr( 196 ) . chr( 131 )              => 'a',
			chr( 196 ) . chr( 132 )              => 'A',
			chr( 196 ) . chr( 133 )              => 'a',
			chr( 196 ) . chr( 134 )              => 'C',
			chr( 196 ) . chr( 135 )              => 'c',
			chr( 196 ) . chr( 136 )              => 'C',
			chr( 196 ) . chr( 137 )              => 'c',
			chr( 196 ) . chr( 138 )              => 'C',
			chr( 196 ) . chr( 139 )              => 'c',
			chr( 196 ) . chr( 140 )              => 'C',
			chr( 196 ) . chr( 141 )              => 'c',
			chr( 196 ) . chr( 142 )              => 'D',
			chr( 196 ) . chr( 143 )              => 'd',
			chr( 196 ) . chr( 144 )              => 'D',
			chr( 196 ) . chr( 145 )              => 'd',
			chr( 196 ) . chr( 146 )              => 'E',
			chr( 196 ) . chr( 147 )              => 'e',
			chr( 196 ) . chr( 148 )              => 'E',
			chr( 196 ) . chr( 149 )              => 'e',
			chr( 196 ) . chr( 150 )              => 'E',
			chr( 196 ) . chr( 151 )              => 'e',
			chr( 196 ) . chr( 152 )              => 'E',
			chr( 196 ) . chr( 153 )              => 'e',
			chr( 196 ) . chr( 154 )              => 'E',
			chr( 196 ) . chr( 155 )              => 'e',
			chr( 196 ) . chr( 156 )              => 'G',
			chr( 196 ) . chr( 157 )              => 'g',
			chr( 196 ) . chr( 158 )              => 'G',
			chr( 196 ) . chr( 159 )              => 'g',
			chr( 196 ) . chr( 160 )              => 'G',
			chr( 196 ) . chr( 161 )              => 'g',
			chr( 196 ) . chr( 162 )              => 'G',
			chr( 196 ) . chr( 163 )              => 'g',
			chr( 196 ) . chr( 164 )              => 'H',
			chr( 196 ) . chr( 165 )              => 'h',
			chr( 196 ) . chr( 166 )              => 'H',
			chr( 196 ) . chr( 167 )              => 'h',
			chr( 196 ) . chr( 168 )              => 'I',
			chr( 196 ) . chr( 169 )              => 'i',
			chr( 196 ) . chr( 170 )              => 'I',
			chr( 196 ) . chr( 171 )              => 'i',
			chr( 196 ) . chr( 172 )              => 'I',
			chr( 196 ) . chr( 173 )              => 'i',
			chr( 196 ) . chr( 174 )              => 'I',
			chr( 196 ) . chr( 175 )              => 'i',
			chr( 196 ) . chr( 176 )              => 'I',
			chr( 196 ) . chr( 177 )              => 'i',
			chr( 196 ) . chr( 178 )              => 'IJ',
			chr( 196 ) . chr( 179 )              => 'ij',
			chr( 196 ) . chr( 180 )              => 'J',
			chr( 196 ) . chr( 181 )              => 'j',
			chr( 196 ) . chr( 182 )              => 'K',
			chr( 196 ) . chr( 183 )              => 'k',
			chr( 196 ) . chr( 184 )              => 'k',
			chr( 196 ) . chr( 185 )              => 'L',
			chr( 196 ) . chr( 186 )              => 'l',
			chr( 196 ) . chr( 187 )              => 'L',
			chr( 196 ) . chr( 188 )              => 'l',
			chr( 196 ) . chr( 189 )              => 'L',
			chr( 196 ) . chr( 190 )              => 'l',
			chr( 196 ) . chr( 191 )              => 'L',
			chr( 197 ) . chr( 128 )              => 'l',
			chr( 197 ) . chr( 129 )              => 'L',
			chr( 197 ) . chr( 130 )              => 'l',
			chr( 197 ) . chr( 131 )              => 'N',
			chr( 197 ) . chr( 132 )              => 'n',
			chr( 197 ) . chr( 133 )              => 'N',
			chr( 197 ) . chr( 134 )              => 'n',
			chr( 197 ) . chr( 135 )              => 'N',
			chr( 197 ) . chr( 136 )              => 'n',
			chr( 197 ) . chr( 137 )              => 'N',
			chr( 197 ) . chr( 138 )              => 'n',
			chr( 197 ) . chr( 139 )              => 'N',
			chr( 197 ) . chr( 140 )              => 'O',
			chr( 197 ) . chr( 141 )              => 'o',
			chr( 197 ) . chr( 142 )              => 'O',
			chr( 197 ) . chr( 143 )              => 'o',
			chr( 197 ) . chr( 144 )              => 'O',
			chr( 197 ) . chr( 145 )              => 'o',
			chr( 197 ) . chr( 146 )              => 'OE',
			chr( 197 ) . chr( 147 )              => 'oe',
			chr( 197 ) . chr( 148 )              => 'R',
			chr( 197 ) . chr( 149 )              => 'r',
			chr( 197 ) . chr( 150 )              => 'R',
			chr( 197 ) . chr( 151 )              => 'r',
			chr( 197 ) . chr( 152 )              => 'R',
			chr( 197 ) . chr( 153 )              => 'r',
			chr( 197 ) . chr( 154 )              => 'S',
			chr( 197 ) . chr( 155 )              => 's',
			chr( 197 ) . chr( 156 )              => 'S',
			chr( 197 ) . chr( 157 )              => 's',
			chr( 197 ) . chr( 158 )              => 'S',
			chr( 197 ) . chr( 159 )              => 's',
			chr( 197 ) . chr( 160 )              => 'S',
			chr( 197 ) . chr( 161 )              => 's',
			chr( 197 ) . chr( 162 )              => 'T',
			chr( 197 ) . chr( 163 )              => 't',
			chr( 197 ) . chr( 164 )              => 'T',
			chr( 197 ) . chr( 165 )              => 't',
			chr( 197 ) . chr( 166 )              => 'T',
			chr( 197 ) . chr( 167 )              => 't',
			chr( 197 ) . chr( 168 )              => 'U',
			chr( 197 ) . chr( 169 )              => 'u',
			chr( 197 ) . chr( 170 )              => 'U',
			chr( 197 ) . chr( 171 )              => 'u',
			chr( 197 ) . chr( 172 )              => 'U',
			chr( 197 ) . chr( 173 )              => 'u',
			chr( 197 ) . chr( 174 )              => 'U',
			chr( 197 ) . chr( 175 )              => 'u',
			chr( 197 ) . chr( 176 )              => 'U',
			chr( 197 ) . chr( 177 )              => 'u',
			chr( 197 ) . chr( 178 )              => 'U',
			chr( 197 ) . chr( 179 )              => 'u',
			chr( 197 ) . chr( 180 )              => 'W',
			chr( 197 ) . chr( 181 )              => 'w',
			chr( 197 ) . chr( 182 )              => 'Y',
			chr( 197 ) . chr( 183 )              => 'y',
			chr( 197 ) . chr( 184 )              => 'Y',
			chr( 197 ) . chr( 185 )              => 'Z',
			chr( 197 ) . chr( 186 )              => 'z',
			chr( 197 ) . chr( 187 )              => 'Z',
			chr( 197 ) . chr( 188 )              => 'z',
			chr( 197 ) . chr( 189 )              => 'Z',
			chr( 197 ) . chr( 190 )              => 'z',
			chr( 197 ) . chr( 191 )              => 's',
			// Decompositions for Latin Extended-B
			chr( 200 ) . chr( 152 )              => 'S',
			chr( 200 ) . chr( 153 )              => 's',
			chr( 200 ) . chr( 154 )              => 'T',
			chr( 200 ) . chr( 155 )              => 't',
			// Euro Sign
			chr( 226 ) . chr( 130 ) . chr( 172 ) => 'E',
			// GBP (Pound) Sign
			chr( 194 ) . chr( 163 )              => '',
			// Vowels with diacritic (Vietnamese)
			// unmarked
			chr( 198 ) . chr( 160 )              => 'O',
			chr( 198 ) . chr( 161 )              => 'o',
			chr( 198 ) . chr( 175 )              => 'U',
			chr( 198 ) . chr( 176 )              => 'u',
			// grave accent
			chr( 225 ) . chr( 186 ) . chr( 166 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 167 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 176 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 177 ) => 'a',
			chr( 225 ) . chr( 187 ) . chr( 128 ) => 'E',
			chr( 225 ) . chr( 187 ) . chr( 129 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 146 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 147 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 156 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 157 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 170 ) => 'U',
			chr( 225 ) . chr( 187 ) . chr( 171 ) => 'u',
			chr( 225 ) . chr( 187 ) . chr( 178 ) => 'Y',
			chr( 225 ) . chr( 187 ) . chr( 179 ) => 'y',
			// hook
			chr( 225 ) . chr( 186 ) . chr( 162 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 163 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 168 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 169 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 178 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 179 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 186 ) => 'E',
			chr( 225 ) . chr( 186 ) . chr( 187 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 130 ) => 'E',
			chr( 225 ) . chr( 187 ) . chr( 131 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 136 ) => 'I',
			chr( 225 ) . chr( 187 ) . chr( 137 ) => 'i',
			chr( 225 ) . chr( 187 ) . chr( 142 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 143 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 148 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 149 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 158 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 159 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 166 ) => 'U',
			chr( 225 ) . chr( 187 ) . chr( 167 ) => 'u',
			chr( 225 ) . chr( 187 ) . chr( 172 ) => 'U',
			chr( 225 ) . chr( 187 ) . chr( 173 ) => 'u',
			chr( 225 ) . chr( 187 ) . chr( 182 ) => 'Y',
			chr( 225 ) . chr( 187 ) . chr( 183 ) => 'y',
			// tilde
			chr( 225 ) . chr( 186 ) . chr( 170 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 171 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 180 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 181 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 188 ) => 'E',
			chr( 225 ) . chr( 186 ) . chr( 189 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 132 ) => 'E',
			chr( 225 ) . chr( 187 ) . chr( 133 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 150 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 151 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 160 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 161 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 174 ) => 'U',
			chr( 225 ) . chr( 187 ) . chr( 175 ) => 'u',
			chr( 225 ) . chr( 187 ) . chr( 184 ) => 'Y',
			chr( 225 ) . chr( 187 ) . chr( 185 ) => 'y',
			// acute accent
			chr( 225 ) . chr( 186 ) . chr( 164 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 165 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 174 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 175 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 190 ) => 'E',
			chr( 225 ) . chr( 186 ) . chr( 191 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 144 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 145 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 154 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 155 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 168 ) => 'U',
			chr( 225 ) . chr( 187 ) . chr( 169 ) => 'u',
			// dot below
			chr( 225 ) . chr( 186 ) . chr( 160 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 161 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 172 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 173 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 182 ) => 'A',
			chr( 225 ) . chr( 186 ) . chr( 183 ) => 'a',
			chr( 225 ) . chr( 186 ) . chr( 184 ) => 'E',
			chr( 225 ) . chr( 186 ) . chr( 185 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 134 ) => 'E',
			chr( 225 ) . chr( 187 ) . chr( 135 ) => 'e',
			chr( 225 ) . chr( 187 ) . chr( 138 ) => 'I',
			chr( 225 ) . chr( 187 ) . chr( 139 ) => 'i',
			chr( 225 ) . chr( 187 ) . chr( 140 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 141 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 152 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 153 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 162 ) => 'O',
			chr( 225 ) . chr( 187 ) . chr( 163 ) => 'o',
			chr( 225 ) . chr( 187 ) . chr( 164 ) => 'U',
			chr( 225 ) . chr( 187 ) . chr( 165 ) => 'u',
			chr( 225 ) . chr( 187 ) . chr( 176 ) => 'U',
			chr( 225 ) . chr( 187 ) . chr( 177 ) => 'u',
			chr( 225 ) . chr( 187 ) . chr( 180 ) => 'Y',
			chr( 225 ) . chr( 187 ) . chr( 181 ) => 'y',
			// Vowels with diacritic (Chinese, Hanyu Pinyin)
			chr( 201 ) . chr( 145 )              => 'a',
			// macron
			chr( 199 ) . chr( 149 )              => 'U',
			chr( 199 ) . chr( 150 )              => 'u',
			// acute accent
			chr( 199 ) . chr( 151 )              => 'U',
			chr( 199 ) . chr( 152 )              => 'u',
			// caron
			chr( 199 ) . chr( 141 )              => 'A',
			chr( 199 ) . chr( 142 )              => 'a',
			chr( 199 ) . chr( 143 )              => 'I',
			chr( 199 ) . chr( 144 )              => 'i',
			chr( 199 ) . chr( 145 )              => 'O',
			chr( 199 ) . chr( 146 )              => 'o',
			chr( 199 ) . chr( 147 )              => 'U',
			chr( 199 ) . chr( 148 )              => 'u',
			chr( 199 ) . chr( 153 )              => 'U',
			chr( 199 ) . chr( 154 )              => 'u',
			// grave accent
			chr( 199 ) . chr( 155 )              => 'U',
			chr( 199 ) . chr( 156 )              => 'u',
		];


		return strtr( $string, $chars );
	}

	public static function generate_unique_username( $username ) {
		static $i;
		if ( null === $i ) {
			$i = 1;
		} else {
			$i ++;
		}

		$username = self::unaccent( $username );

		if ( ! username_exists( $username ) ) {
			return $username;
		}
		$new_username = sprintf( '%s%s', $username, $i );
		if ( ! username_exists( $new_username ) ) {
			return $new_username;
		} else {
			return call_user_func( __FUNCTION__, $username );
		}
	}

	public static function init() {
//		add_action('init', function() {
		if ( isset( $_REQUEST['action'] ) && 'createuser' == $_REQUEST['action'] ) {
			if ( empty( $_POST['user_login'] ) && ( ! empty( $_POST['first_name'] ) || ! empty( $_POST['last_name'] ) ) ) {
				$user_first_name     = isset( $_POST['first_name'] ) ? $_POST['first_name'] : '';
				$user_last_name      = isset( $_POST['last_name'] ) ? $_POST['last_name'] : '';
				$_POST['user_login'] = self::generate_unique_username( strtolower( $user_first_name . '.' . $user_last_name ) );
			}
		}
//		});
		add_action( 'admin_head-user-new.php', array( 'AmapressUsers', 'remove_user_unused_fields' ) );
		add_action( 'admin_head-user-edit.php', array( 'AmapressUsers', 'remove_user_unused_fields' ) );
		add_action( 'admin_head-profile.php', array( 'AmapressUsers', 'remove_user_unused_fields' ) );
		amapress_register_shortcode( 'users_near', array( 'AmapressUsers', 'users_near_shortcode' ) );
		amapress_register_shortcode( 'trombinoscope', array( 'AmapressUsers', 'trombinoscope_shortcode' ) );
		amapress_register_shortcode( 'trombinoscope_lieu', array( 'AmapressUsers', 'trombinoscope_lieu_shortcode' ) );
		amapress_register_shortcode( 'trombinoscope_role', array( 'AmapressUsers', 'trombinoscope_role_shortcode' ) );
		// enqueue and localise scripts
//        wp_enqueue_script('userlikes-handle', plugin_dir_url(__FILE__) . 'js/ajax-userlikes.js', array('jquery'));
//        wp_localize_script('userlikes-handle', 'user_produit_likebox', array('ajax_url' => admin_url('admin-ajax.php')));
		// THE AJAX ADD ACTIONS
//        add_action('wp_ajax_user_likebox_action', array('AmapressUsers', 'user_likebox_produit_action'));
//        add_action('wp_ajax_nopriv_user_likebox_action', array('AmapressUsers', 'user_likebox_produit_action'));

		add_filter( 'amapress_gallery_render_user_cell', 'AmapressUsers::amapress_gallery_render_user_cell' );
		add_filter( 'amapress_gallery_render_user_cell_contact', 'AmapressUsers::amapress_gallery_render_user_cell_contact' );
		add_filter( 'amapress_gallery_render_user_cell_with_role', 'AmapressUsers::amapress_gallery_render_user_cell_with_role' );


//        if (!self::$vp) self::$vp = new Virtual_Themed_Pages_BC();
//		self::$vp->add('#/amapiens-autour-de-(moi|.+)#i', array('AmapressUsers','virtual_aroundme'));
//		self::$vp->add('#/mon-profile#i', array('AmapressUsers','virtual_mon_profile'));
//		self::$vp->add('#/trombinoscope#i', array('AmapressUsers','virtual_trombi'));
	}

	public static function amapress_gallery_render_user_cell( $user ) {
		$usr = $user;
		if ( is_int( $usr ) ) {
			$usr = get_user_by( 'id', $usr );
		}

		if ( ! $usr ) {
			return '';
		}

		ob_start();

		self::echoUser( $usr, 'thumb' );

		$content = ob_get_clean();

		return $content;
	}

	public static function amapress_gallery_render_user_cell_contact( $user ) {
		$usr = $user;
		if ( is_int( $usr ) ) {
			$usr = get_user_by( 'id', $usr );
		}

		if ( ! $usr ) {
			return '';
		}

		ob_start();

		self::echoUser( $usr, array( 'telephone', 'mail' ) );

		$content = ob_get_clean();

		return $content;
	}

	public static function amapress_gallery_render_user_cell_with_role( $user ) {
		$usr = $user['user'];
		if ( is_int( $usr ) ) {
			$usr = get_user_by( 'id', $usr );
		}
		if ( ! $usr ) {
			return '';
		}

		ob_start();

		self::echoUser( $usr, 'thumb', $user['link'], $user['role'] );

		$content = ob_get_clean();

		return $content;
	}

	static function remove_user_unused_fields() {
		echo '<style>
                tr.user-rich-editing-wrap{ display: none; }
                tr.user-admin-color-wrap{ display: none; }
                tr.user-comment-shortcuts-wrap{ display: none; }
                tr.user-admin-bar-front-wrap{ display: none; }
                /*tr.user-profile-picture{ display: none; }*/
                /*tr.user-description-wrap{ display: none; }*/
                tr.user-url-wrap{ display: none; }
                tr.user-syntax-highlighting-wrap {display: none; }
              </style>';
		global $pagenow;
		if ( 'user-new.php' == $pagenow && ! is_multisite() ) {
			echo '<script type="text/javascript">
jQuery(function() {
              jQuery(".form-field").has("#url").hide();
              jQuery(".form-field").has("#user_login").hide();
});
</script>';

		}
	}

	public static function echoUserById( $user_id, $type, $custom_link = null, $custom_role = null ) {
		$user = get_user_by( 'id', $user_id );
		if ( empty( $user ) ) {
			return;
		}
		AmapressUsers::echoUser( $user, $type, $custom_link, $custom_role );
	}

	public static function echoUser( WP_User $user, $type, $custom_link = null, $custom_role = null ) {
		if ( ! amapress_is_user_logged_in() ) {
			$type = 'thumb';
		}
		$types = array();
		if ( is_string( $type ) ) {
			$types[] = $type;
		}

		$amapien = AmapressUser::getBy( $user );

		echo '<div class="user user-' . implode( '_', $user->roles ) . '">';
//        $url = amapress_get_avatar_url($user->ID, null, 'user-thumb', 'default_amapien.jpg', 1);
		$img = get_avatar( $user->ID );
		echo '<div class="user-photo">' . $img . '</div>';

//        $dn = $user->display_name;
//        if (!empty($user->last_name)) {
//            $dn = sprintf('%s %s', $user->first_name, $user->last_name);
//        }
		$dn = $amapien->getDisplayName();

		//echo '<p><a href="'.get_author_posts_url($user->ID).'">'.$user->display_name.'</a></p>';
		if ( ! in_array( 'no-name', $types ) ) {
			echo '<p class="user-name">' . ( ! empty( $custom_link ) ? '<a href="' . $custom_link . '">' . $dn . '</a>' : $dn ) . '</p>';
			//echo '<p class="user-name">'.$dn.'</p>';
		}
		if ( ! empty( $custom_role ) ) {
			echo '<p class="user-role">' . $custom_role . '</p>';
		} else {
			if ( ! in_array( 'no-role', $types ) ) {
				$role_desc = $amapien->getAmapRolesString();
				if ( ! empty( $role_desc ) ) {
					echo '<p class="user-role">' . $role_desc . '</p>';
				}
			}
		}
		if ( $type == 'thumb' ) {
			echo '</div>';

			return;
		}

		if ( in_array( 'telephone', $types ) || $type == 'full' ) {
			if ( get_post_meta( $user->ID, 'amapress_user_telephone', true ) ) {
				echo '<p class="user-phone">Téléphone : ' . get_user_meta( $user->ID, 'amapress_user_telephone', true ) . '</p>';
			}
			if ( get_post_meta( $user->ID, 'amapress_user_telephone2', true ) ) {
				echo '<p class="user-phone2">Téléphone 2 : ' . get_user_meta( $user->ID, 'amapress_user_telephone2', true ) . '</p>';
			}
		}
		if ( in_array( 'mail', $types ) || $type == 'full' ) {
			if ( $user->user_email ) {
				echo '<p class="user-mail">Mail : <a href="mailto:' . $user->user_email . '">' . $user->user_email . '</a></p>';
			}
		}
		if ( get_post_meta( $user->ID, 'amapress_user_adresse', true ) &&
		     ( amapress_current_user_can( 'responsable_amap' ) || amapress_current_user_can( 'administrator' ) || in_array( 'adresse', $types ) || $type == 'full' )
		) {
			echo '<p>Adresse : <pre>' . get_user_meta( $user->ID, 'amapress_user_adresse', true ) . '\n' . get_user_meta( $user->ID, 'amapress_user_code_postal', true ) . ' ' . get_user_meta( $user->ID, 'amapress_user_ville', true ) . '</pre></p>';
		}
		if ( get_post_meta( $user->ID, 'amapress_user_location_type', true ) &&
		     ( amapress_current_user_can( 'responsable_amap' ) || amapress_current_user_can( 'administrator' ) || in_array( 'adresse-loc-link', $types ) || $type == 'full' )
		) {
			echo '<a href="http://maps.google.com/maps?q=' . get_post_meta( $user->ID, 'amapress_user_lat', true ) . ',' . get_post_meta( $user->ID, 'amapress_user_long', true ) . '">Voir sur Google Maps</a>';
		}
		if ( get_post_meta( $user->ID, 'amapress_user_location_type', true ) &&
		     ( amapress_current_user_can( 'responsable_amap' ) || amapress_current_user_can( 'administrator' ) || in_array( 'adresse-loc-map', $types ) || $type == 'full' )
		) {
			echo do_shortcode( "[user-map user={$user->ID} mode=map" );
		}
		echo '</div>';
	}

	static function virtual_aroundme( $v, $url ) {
		if ( is_admin() ) {
			return;
		}
		if ( ! amapress_is_user_logged_in() ) {
			$v->redirect = '/wp-login.php';

			return;
		}
		$v->template = 'page'; // optional
		if ( preg_match( '#amapiens-autour-de-(moi|.+)#', $url, $m ) ) {
			if ( $m[1] == 'moi' ) {
				$v->body  = do_shortcode( '[users_near]' );
				$v->title = 'Les amapiens proches de moi';
			} else {
				$v->body  = do_shortcode( '[users_near user="' . $m[1] . '"]' );
				$v->title = 'Les amapiens proches de ' . $m[1];
			}
		}
	}

	public static function distance( $lat1, $lon1, $lat2, $lon2, $unit ) {
		$theta = $lon1 - $lon2;
		$dist  = sin( deg2rad( $lat1 ) ) * sin( deg2rad( $lat2 ) ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * cos( deg2rad( $theta ) );
		$dist  = acos( $dist );
		$dist  = rad2deg( $dist );
		$miles = $dist * 60 * 1.1515;
		$unit  = strtoupper( $unit );

		if ( $unit == "K" ) {
			return ( $miles * 1.609344 );
		} else if ( $unit == "N" ) {
			return ( $miles * 0.8684 );
		} else {
			return $miles;
		}
	}

	public static function users_near_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'count' => 10,
			'user'  => amapress_current_user_id(),
		), $atts, 'users_near' );

//        $lieu_ids = AmapressUsers::get_current_user_lieu_ids();

		ob_start();
		$user_id = AmapressUsers::get_user_id( $atts['user'] );
		$loc     = get_user_meta( $user_id, 'amapress_user_location_type', true );
		if ( empty( $loc ) ) {
			if ( $user_id == amapress_current_user_id() ) {
				return 'Votre adresse n\'est pas localisée.';
			} else {
				return 'Adresse non localisée.';
			}
		}

		$lat = floatval( get_user_meta( $user_id, 'amapress_user_lat', true ) );
		$lng = floatval( get_user_meta( $user_id, 'amapress_user_long', true ) );

		$users = get_users( array(
			'meta_query' => array(
				'relation' => 'OR',
				array( 'key' => 'pw_user_status', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'pw_user_status', 'value' => 'approved', 'compare' => '=' ),
			),
			'order'      => 'ASC',
			'orderby'    => 'display_name',
			'exclude'    => array( $user_id ),
		) );

		$users_dists = array();
		foreach ( $users as $user ) {
			$loc = get_user_meta( $user_id, 'amapress_user_location_type', true );
			if ( ! empty( $loc ) ) {
				$u_lat         = floatval( get_user_meta( $user->ID, 'amapress_user_lat', true ) );
				$u_lng         = floatval( get_user_meta( $user->ID, 'amapress_user_long', true ) );
				$users_dists[] = array(
					'user' => $user,
					'dist' => AmapressUsers::distance( $lat, $lng, $u_lat, $u_lng, 'K' )
				);
			}
		}
		usort( $users_dists, array( 'AmapressUsers', 'sort_user_dist' ) );

		$cnt = count( $users_dists );
		if ( $cnt > $atts['count'] ) {
			$cnt = $atts['count'];
		}
		echo '<table>';
		echo '<tr><th>Amapien</th><th>Distance</th></tr>';
		for ( $i = 0; $i < $cnt; $i ++ ) {
			echo '<tr><td><a href="' . get_author_posts_url( $users_dists[ $i ]['user']->ID ) . '">' . $users_dists[ $i ]['user']->display_name . '</a></td><td>' . $users_dists[ $i ]['dist'] . ' km</td></tr>';
		}
		echo '</table>';

		$t = ob_get_contents();
		ob_end_clean();

		return $t;
	}

	public static function sort_user_dist( $a, $b ) {
		if ( $a['dist'] < $b['dist'] ) {
			return - 1;
		} else if ( $a['dist'] > $b['dist'] ) {
			return 1;
		} else {
			return 0;
		}
	}

	public static function trombinoscope_role_shortcode( $atts ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'role' => 'all',
			'lieu' => null,
		), $atts, 'trombinoscope_role' );

		if ( ! empty( $atts['lieu'] ) ) {
			$lieu_id = Amapress::resolve_post_id( $atts['lieu'], AmapressLieu_distribution::INTERNAL_POST_TYPE );
			if ( $lieu_id ) {
				$lieu_ids = array( $lieu_id );
			} else {
				$lieu_ids = Amapress::get_lieu_ids();
			}
		} else {
			if ( amapress_can_access_admin() ) {
				$lieu_ids = Amapress::get_lieu_ids();
			} else {
				$lieu_ids = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
			}
		}

		$base_query = array(
			'meta_query'    => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array( 'key' => 'pw_user_status', 'compare' => 'NOT EXISTS' ),
					array( 'key' => 'pw_user_status', 'value' => 'approved', 'compare' => '=' ),
				)
			),
			'amapress_lieu' => $lieu_ids,
			'order'         => 'ASC',
			'orderby'       => 'display_name',
		);

		$role = $atts['role'];
		if ( $role == 'producteurs' ) {
			$args = wp_parse_args(
				array( 'role' => 'producteur' ),
				$base_query );
			unset( $args['amapress_lieu'] );
			$users = get_users( $args );
		} else if ( $role == 'responsables' ) {
			$users    = get_users( wp_parse_args(
				array( 'amapress_role' => 'amap_role_any' ),
				$base_query ) );
			$user_ids = array_map( function ( $u ) {
				return $u->ID;
			}, $users );
			$admins   = get_users( wp_parse_args(
				array( 'amapress_role' => 'access_admin' ),
				$base_query ) );
			foreach ( $admins as $user ) {
				if ( in_array( $user->ID, $user_ids ) ) {
					continue;
				}
				$users[] = $user;
			}
		} else if ( $role == 'referents_lieux' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_role' => 'referent_lieu' ),
				$base_query ) );

		} else if ( $role == 'referents_producteurs' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_role' => 'referent_producteur' ),
				$base_query ) );
		} else if ( $role == 'amapiens' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_contrat' => 'active' ),
				$base_query ) );
		} else if ( $role == 'prochaine_distrib' ) {
			$users = get_users( wp_parse_args(
				array( 'amapress_role' => 'resp_distrib' ),
				$base_query ) );

			if ( count( $users ) == 0 ) {
				return 'Pas de responsable(s) inscrit(s)';
			}
		} else {
			$users = array();
		}

		usort( $users, function ( $a, $b ) {
			return strcmp( $a->display_name, $b->display_name );
		} );

		$n = implode( '-', $lieu_ids );

		return amapress_generic_gallery( $users, "trombi-$n-$role", 'user_cell' );
	}

	public static function get_user_id( $user ) {
		if ( is_numeric( $user ) ) {
			return intval( $user );
		}
		$user_id = - 1;
		if ( is_string( $user ) ) {
			$user_object = get_user_by( 'slug', $user );
			if ( $user_object ) {
				return $user_object->ID;
			}
			$user_object = get_user_by( 'login', $user );
			if ( $user_object ) {
				return $user_object->ID;
			}
			$user_object = get_user_by( 'email', $user );
			if ( $user_object ) {
				return $user_object->ID;
			}
		}

		return $user_id;
	}

	public static function trombinoscope_shortcode() {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$lieu_ids = AmapressUsers::get_user_lieu_ids( amapress_current_user_id() );
		$lieux    = get_posts( array(
			'post_type'      => 'amps_lieu',
			'posts_per_page' => - 1,
			'include'        => $lieu_ids
		) );

		amapress_echo_panel_start( 'Les responsables de l\'AMAP' );
		echo do_shortcode( '[trombinoscope_role role=responsables]' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les producteurs' );
		echo do_shortcode( '[trombinoscope_role role=producteurs]' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les référents producteurs' );
		echo do_shortcode( '[trombinoscope_role role=referents_producteurs]' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les référents lieux de distribution' );
		echo do_shortcode( '[trombinoscope_role role=referents_lieux]' );
		amapress_echo_panel_end();

		foreach ( $lieux as $lieu ) {
			if ( count( $lieux ) > 1 ) {
				echo '<h2>' . $lieu->post_title . '</h2>';
			}
			echo do_shortcode( '[trombinoscope_lieu lieu=' . $lieu->ID . ']' );
		}

		$t = ob_get_clean();

		return $t;
	}

	public static function trombinoscope_lieu_shortcode( $atts ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'lieu' => null,
		), $atts, 'trombinoscope_lieu' );

		$lieu_id = Amapress::get_lieu_id( $atts['lieu'] );
		//$lieu = get_post($lieu_id);
		ob_start();

		//echo '<h2>'.$lieu->post_title.'</h2>';
		amapress_echo_panel_start( 'Les responsables à la prochaine distribution', null, 'amap-panel-resp-dist' );
		echo do_shortcode( '[trombinoscope_role role=prochaine_distrib lieu=' . $lieu_id . ']' );
		amapress_echo_panel_end();
		amapress_echo_panel_start( 'Les amapiens', null, 'amap-panel-amapiens' );
		echo do_shortcode( '[trombinoscope_role role=amapiens lieu=' . $lieu_id . ']' );
		amapress_echo_panel_end();

		$t = ob_get_clean();

		return $t;
	}

//    public static function get_current_user_lieu_ids()
//    {
//        if (amapress_current_user_can('responsable_amap') || amapress_current_user_can('producteur') || amapress_current_user_can('administrator'))
//            $lieu_ids = array_map(array('Amapress', 'to_id'), get_posts(array(
//                'posts_per_page' => -1,
//                'post_type' => 'amps_lieu'
//            )));
//        else {
//            $abo_ids = AmapressContrats::get_active_contrat_instances_ids();
//            $user_ids = AmapressContrats::get_related_users(amapress_current_user_id(), false);
//            $lieu_ids = array_map(array('Amapress', 'to_adhesion_lieu'), get_posts(array(
//                'post_type' => 'amps_adhesion',
//                'posts_per_page' => -1,
//                'meta_query' => array(
//                    'relation' => 'AND',
//                    array(
//                        'key_num' => 'amapress_adhesion_contrat_instance',
//                        'value' => $abo_ids,
//                        'compare' => 'IN'),
//                    array('relation' => 'OR',
//                        array(
//                            'key' => 'amapress_adhesion_adherent',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_adhesion_adherent2',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                        array(
//                            'key' => 'amapress_adhesion_adherent3',
//                            'value' => $user_ids,
//                            'compare' => 'IN',
//                            'type' => 'INT'),
//                    ),
//                ))));
//        }
//        return $lieu_ids;
//    }

	public static function get_user_lieu_ids( $user_id, $date = null, $ignore_renouv_delta = false ) {
		$abo_ids = AmapressContrats::get_active_contrat_instances_ids( null, $date, $ignore_renouv_delta );
		$abo_key = implode( '-', $abo_ids );
		$key     = "amapress_get_user_lieu_ids_$user_id-$abo_key";

		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$user_ids = AmapressContrats::get_related_users( $user_id );
			$lieu_ids = array_map( array( 'Amapress', 'to_adhesion_lieu' ), get_posts( array(
				'post_type'      => 'amps_adhesion',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_adhesion_contrat_instance',
						'value'   => amapress_prepare_in( $abo_ids ),
						'compare' => 'IN',
						'type'    => 'NUMERIC'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'amapress_adhesion_adherent',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'amapress_adhesion_adherent2',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'amapress_adhesion_adherent3',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'NUMERIC'
						),
						array(
							'key'     => 'amapress_adhesion_adherent4',
							'value'   => $user_ids,
							'compare' => 'IN',
							'type'    => 'NUMERIC'
						),
					),
				)
			) ) );

			$res = array_unique( $lieu_ids );
			wp_cache_set( $key, $res );
		}

		return $res;
	}


//    public static function like_unlike_produit($user_id, $produit_id, $like)
//    {
//        $produit = get_post($produit_id);
//        $user = get_user_by('id', $user_id);
//        $user_produit_likes = get_posts(array(
//            'posts_per_page' => -1,
//            'post_type' => 'amps_user_produit_like',
//            'meta_query' => array(
//                'relation' => 'AND',
//                array(
//                    'key' => 'amapress_user_produit_like_user',
//                    'value' => $user_id,
//                ),
//                array(
//                    'key' => 'amapress_user_produit_like_produit',
//                    'value' => $produit_id,
//                ),
//            ),
//        ));
//        $like_cnt = get_post_meta($produit_id, 'amapress_produit_likes', true);
//        if (!$like_cnt) $like_cnt = 0;
//        $unlike_cnt = get_post_meta($produit_id, 'amapress_produit_unlikes', true);
//        if (!$unlike_cnt) $unlike_cnt = 0;
//
//        $insert = true;
//        if ($like == 0) {
//            $insert = false;
//            if (count($user_produit_likes) > 0) {
//                foreach ($user_produit_likes as $user_produit_like) {
//                    $v = get_post_meta($user_produit_like->ID, '', true);
//                    if ($v < 0)
//                        $unlike_cnt--;
//                    else if ($v > 0)
//                        $like_cnt--;
//
//                    delete_post($user_produit_like->ID);
//                }
//            }
//        } else if ($like > 0) {
//            if (count($user_produit_likes) > 0) {
//                foreach ($user_produit_likes as $user_produit_like) {
//                    $del = true;
//                    $v = get_post_meta($user_produit_like->ID, 'amapress_user_produit_like_vote', true);
//                    if ($v < 0)
//                        $unlike_cnt--;
//                    else if ($v > 0) {
//                        $insert = false;
//                        $del = false;
//                    }
//
//                    if ($del) delete_post($user_produit_like->ID);
//                }
//            } else
//                $like_cnt++;
//        } else {
//            if (count($user_produit_likes) > 0) {
//                foreach ($user_produit_likes as $user_produit_like) {
//                    $del = true;
//                    $v = get_post_meta($user_produit_like->ID, 'amapress_user_produit_like_vote', true);
//                    if ($v > 0)
//                        $like_cnt--;
//                    else if ($v < 0) {
//                        $insert = false;
//                        $del = false;
//                    }
//
//                    if ($del) delete_post($user_produit_like->ID);
//                }
//            } else
//                $unlike_cnt++;
//        }
//        if ($insert) {
//            $my_post = array(
//                'post_title' => 'L',
//                'post_content' => ($like > 0 ? 'L' : 'U'),
//                'post_status' => 'publish',
//            );
//            $id = wp_insert_post($my_post);
//            if ($id > 0) {
//                update_post_meta($id, 'amapress_user_produit_like_user', $user_id);
//                update_post_meta($id, 'amapress_user_produit_like_produit', $produit_id);
//                update_post_meta($id, 'amapress_user_produit_like_vote', $like);
//            } else
//                return;
//        }
//        update_post_meta($produit_id, 'amapress_produit_likes', $like_cnt);
//        update_post_meta($produit_id, 'amapress_produit_unlikes', $unlike_cnt);
//    }

//    public static function get_user_produit_likebox($user_id, $produit_id)
//    {
//        $produit = get_post($produit_id);
//        $user = get_user_by('id', $user_id);
//        $user_produit_likes = get_posts(array(
//            'posts_per_page' => -1,
//            'post_type' => 'amps_user_produit_like',
//            'meta_query' => array(
//                'relation' => 'AND',
//                array(
//                    'key' => 'amapress_user_produit_like_user',
//                    'value' => $user_id,
//                ),
//                array(
//                    'key' => 'amapress_user_produit_like_produit',
//                    'value' => $produit_id,
//                ),
//            ),
//        ));
//        $like_cnt = get_post_meta($produit_id, 'amapress_produit_likes', true);
//        if (!$like_cnt) $like_cnt = 0;
//        $unlike_cnt = get_post_meta($produit_id, 'amapress_produit_unlikes', true);
//        if (!$unlike_cnt) $unlike_cnt = 0;
//
//        $user_like = 0;
//        foreach ($user_produit_likes as $user_produit_like) {
//            $user_like = intval(get_post_meta($user_produit_like->ID, 'amapress_user_produit_like_vote', true));
//        }
//
//        $cls = 'none';
//        if ($user_like > 0) $cls = 'like';
//        if ($user_like < 0) $cls = 'unlike';
//        return '<div class="produit-likebox">' . sprintf('%d likes / %d unlikes', $like_cnt, $unlike_cnt) . ' - <span class="produit-like-button like-' . $cls . '" data-produit="' . $produit_id . '" data-like="' . ($user_like <= 0 ? 1 : 0) . '">Like</span> - <span class="produit-unlike-button unlike-' . $cls . '" data-produit="' . $produit_id . '" data-like="' . ($user_like >= 0 ? -1 : 0) . '">Unlike</span></div>';
//    }
//
//    function user_likebox_produit_action()
//    {
//        /* this area is very simple but being serverside it affords the possibility of retreiving data from the server and passing it back to the javascript function */
//        $produit_id = intval($_POST['produit']);
//        $user_id = amapress_current_user_id();
//        $like = intval($_POST['like']);
//        AmapressUsers::like_unlike_produit($user_id, $produit_id, $like);
//        echo AmapressUsers::get_user_produit_likebox($user_id, $produit_id);// this is passed back to the javascript function
//        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
//    }

//    public static function isUserLocalized($user_id) {
//        $loc = get_user_meta($user_id,'amapress_user_location_type',true);
//        $lat = get_user_meta($user_id,'amapress_user_lat',true);
//        $lng = get_user_meta($user_id,'amapress_user_long',true);
//        return (!empty($loc) ? 'Localisé <a href="http://maps.google.com/maps?q='.$lng.','.$lat.'">Voir sur Google Maps</a>' : 'Adresse non localisée');
//    }

	public static function resolveUserAddress( $user_id = null, $address_text = null ) {
		if ( empty( $user_id ) && ! empty( $_REQUEST['user_id'] ) ) {
			$user_id = $_REQUEST['user_id'];
		}
		if ( empty( $address_text ) && ! empty( $_REQUEST['amapress_user_adresse'] ) ) {
			$address_text = $_REQUEST['amapress_user_adresse'] . ', ' . $_REQUEST['amapress_user_code_postal'] . ' ' . $_REQUEST['amapress_user_ville'];
		}

		self::resolveUserFullAdress( $user_id, $address_text );
	}

	public static function resolveUserFullAdress( $user_id, $address_text ) {
		$address = TitanFrameworkOptionAddress::lookup_address( $address_text );
		if ( $address ) {
			update_user_meta( $user_id, 'amapress_user_long', $address['longitude'] );
			update_user_meta( $user_id, 'amapress_user_lat', $address['latitude'] );
			update_user_meta( $user_id, 'amapress_user_location_type', $address['location_type'] );

			return true;
		} else {
			delete_user_meta( $user_id, 'amapress_user_long' );
			delete_user_meta( $user_id, 'amapress_user_lat' );
			delete_user_meta( $user_id, 'amapress_user_location_type' );

			return false;
		}
	}
}
