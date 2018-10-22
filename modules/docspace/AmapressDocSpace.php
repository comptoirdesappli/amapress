<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 15/10/2018
 * Time: 07:43
 */

class AmapressDocSpace {
	private $name;

	public function enqueueScripts() {
		wp_enqueue_script(
			'dropzonejs',
			'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js',
			array(),
			AMAPRESS_VERSION
		);
		// Load custom dropzone javascript
		wp_enqueue_script(
			'customdropzonejs',
			AMAPRESS__PLUGIN_URL . '/modules/docspace/customize_dropzonejs.js',
			array( 'dropzonejs' ),
			AMAPRESS_VERSION
		);
		wp_enqueue_style(
			'dropzonecss',
			'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css',
			array(),
			AMAPRESS_VERSION
		);
	}

	public function echoDropZone( $atts ) {
		$url            = admin_url( 'admin-ajax.php' );
		$nonce_files    = wp_nonce_field( 'amp_upload_file', 'amp_upload_file_nonce' );
		$sanitized_name = sanitize_html_class( $this->name );
		$space_name     = esc_html( $this->name );

		return <<<ENDFORM
<div id="dropzone-wordpress-$sanitized_name"><form action="$url" class="dropzone needsclick dz-clickable" id="dropzone-wordpress-form-$sanitized_name">
	$nonce_files
	<div class="dz-message needsclick">
		Déposez des fichiers ici ou cliquez pour téléverser.<br>
		<span class="note needsclick">(Les fichiers seront téléversés dans l'espace $space_name)</span>
  	</div>
	<input type='hidden' name='action' value='amp_upload_docspace'>
</form></div>
ENDFORM;
	}

	public function handleUpload() {
		if ( ! empty( $_FILES ) && wp_verify_nonce( $_REQUEST['amp_upload_file_nonce'], 'amp_upload_file' ) ) {
			//$_FILES['file']['tmp_name'];
			//$_FILES['file']['name'];
		}
		die();
	}

	public function ensureUniqueFileName() {

	}

	public function echoFileList() {

	}

	public function getDownloadLink() {

	}

	public function handleDownload() {

	}

	public function hasCurrentUserUploadRight() {

	}

	public function hasCurrentUserDownloadRight() {

	}

	public function hasCurrentUserListRight() {

	}

	public static function getUploadDir( $name ) {
		$subfolder = "$name/";
		$dir       = wp_upload_dir()['basedir'] . '/amapress-docspace/';
		$created   = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', "w" );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
		}

		if ( ! empty( $subfolder ) ) {
			$dir = $dir . $subfolder;
			wp_mkdir_p( $dir );
		}

		return $dir;
	}

}