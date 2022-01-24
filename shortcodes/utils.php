<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $all_amapress_shortcodes;
global $all_amapress_shortcodes_descs;
/**
 * @param string $tag
 * @param callable $func
 * @param array $desc
 */
function amapress_register_shortcode( $tag, $func, $desc = [] ) {
	global $all_amapress_shortcodes;
	if ( empty( $all_amapress_shortcodes ) ) {
		$all_amapress_shortcodes = [];
	}
	$all_amapress_shortcodes[ $tag ] = $func;

	global $all_amapress_shortcodes_descs;
	if ( empty( $all_amapress_shortcodes_descs ) ) {
		$all_amapress_shortcodes_descs = [];
	}
	$all_amapress_shortcodes_descs[ $tag ] = $desc;

	add_shortcode( $tag, $func );
}

function amapress_get_honeypots() {
	ob_start();
	amapress_echo_honeypots();

	return ob_get_clean();
}

function amapress_echo_honeypots() {
	$honey_1_id = uniqid( 'amps-firstname' );
	$honey_2_id = uniqid( 'amps-lastname' );
	?>
    <span id="<?php echo $honey_1_id; ?>">
                <label for="amps-firstname"><?php _e( 'Laisser vide', 'amapress' ); ?></label>
                <input type="text" value="" name="amps-firstname"
                       id="amps-firstname"
                       size="40" tabindex="-1" autocomplete="off"/>
    </span>
    <span id="<?php echo $honey_2_id; ?>" style="display:none !important; visibility:hidden !important;">
                <label for="amps-lastname"><?php _e( 'Laisser vide', 'amapress' ); ?></label>
                <input type="text" value="" name="amps-lastname"
                       id="amps-lastname"
                       size="40" tabindex="-1" autocomplete="off"/>
    </span>
	<?php

	$hp_css = '#' . $honey_1_id . ' {display:none !important; visibility:hidden !important}';
	wp_register_style( 'inscr-' . $honey_1_id . '-inline', false );
	wp_enqueue_style( 'inscr-' . $honey_1_id . '-inline' );
	wp_add_inline_style( 'inscr-' . $honey_1_id . '-inline', $hp_css );
}

function amapress_checkhoneypots() {
	if ( ! empty( $_REQUEST['amps-firstname'] ) || ! isset( $_REQUEST['amps-firstname'] )
	     || ! empty( $_REQUEST['amps-lastname'] ) || ! isset( $_REQUEST['amps-lastname'] ) ) {
		wp_die( __( 'Spam detected !!!', 'amapress' ) );
	}
}