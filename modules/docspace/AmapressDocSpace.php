<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 15/10/2018
 * Time: 07:43
 */

class AmapressDocSpace {
	private $name;
	private $list_capability;
	private $upload_capability;
	private $download_capability;

	/**
	 * AmapressDocSpace constructor.
	 *
	 * @param $name
	 */
	public function __construct( $name, $list_capability, $upload_capability, $download_capability ) {
		$this->name                = $name;
		$this->list_capability     = $list_capability;
		$this->upload_capability   = $upload_capability;
		$this->download_capability = $download_capability;
		add_action( "wp_ajax_amp_upload_docspace_$name", [ $this, 'handleUpload' ] );
		add_action( "wp_ajax_amp_docspace_remove_$name", [ $this, 'handleRemove' ] );
		add_action( "wp_ajax_amp_docspace_list_$name", [ $this, 'echoFileList' ] );
		add_action( "wp_ajax_nopriv_amp_docspace_list_$name", [ $this, 'echoFileList' ] );
		add_action( "admin_post_amp_docspace_download_$name", [ $this, 'handleDownload' ] );
		add_action( "admin_post_nopriv_amp_docspace_download_$name", [ $this, 'handleDownload' ] );
		amapress_register_shortcode( "docspace-$name", [ $this, 'echoDropZone' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
	}

	public function enqueueScripts() {
		wp_enqueue_script(
			'dropzonejs',
			'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js',
			array(),
			AMAPRESS_VERSION
		);
		// Load custom dropzone javascript
//		wp_enqueue_script(
//			'customdropzonejs',
//			AMAPRESS__PLUGIN_URL . '/modules/docspace/customize_dropzonejs.js',
//			array( 'dropzonejs' ),
//			AMAPRESS_VERSION
//		);
		wp_enqueue_style(
			'dropzonecss',
			'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css',
			array(),
			AMAPRESS_VERSION
		);
		wp_enqueue_script( 'docspace-sortable',
			AMAPRESS__PLUGIN_URL . 'js/sorttable.js'
		);
	}

	public function echoDropZone( $atts ) {
		amapress_ensure_no_cache();

		$url             = admin_url( 'admin-ajax.php' );
		$nonce_files     = wp_nonce_field( 'amp_upload_file', 'amp_upload_file_nonce', true, false );
		$sanitized_name  = sanitize_html_class( $this->name );
		$space_name      = esc_html( $this->name );
		$get_list_url    = add_query_arg( 'action', 'amp_docspace_list_' . $this->name, $url );
		$remove_file_url = add_query_arg( 'action', 'amp_docspace_remove_' . $this->name, $url );

		$ret = "<div id='amps-file-list-$sanitized_name''></div>";
		if ( $this->hasCurrentUserUploadRight() ) {
			$ret .= <<<ENDFORM
<div id="amps-dz-$sanitized_name"><form action="$url" class="dropzone needsclick dz-clickable" id="amps-dz-form-$sanitized_name">
	$nonce_files
	<div class="dz-message needsclick">
		Déposez des fichiers ici ou cliquez pour téléverser.<br>
		<span class="note needsclick">(Les fichiers seront téléversés dans l'espace $space_name)</span>
  	</div>
	<input type='hidden' name='action' value='amp_upload_docspace_$this->name'>
	<label for="amp_override"><input type="checkbox" id="amp_override" name="amp_override" /> Ecraser les fichiers existants</label>
</form></div>
ENDFORM;
		}
		$ret .= <<<ENDFORM
<script type="text/javascript">
    //<![CDATA[
	function updateList$sanitized_name() {
	    jQuery.get('$get_list_url', function(data) {
	        jQuery('#amps-file-list-$sanitized_name').html(data);
	        sorttable.makeSortable(jQuery('#amps-file-list-$sanitized_name table').get(0));
            jQuery('.docspace-remove').on('click', function() {
                var name = jQuery(this).data('name');
                if (!confirm('Etes-vous sûr de vouloir supprimer ' + name + ' ?'))
                    return;
                
                jQuery.get('$remove_file_url&name=' + name, function(data) {
                    updateList$sanitized_name();
                }).fail(function() {
                    alert( "Une erreur s'est produite pendant la suppression du fichier. Merci de rafraichir la page." );
                  });
            });	        
	    }).fail(function() {
                    alert( "Une erreur s'est produite pendant la mise à jour de la liste des fichiers. Merci de rafraichir la page." );
                  });;
	}
	
	Dropzone.autoDiscover = false;

	jQuery(function() {
	    updateList$sanitized_name();

	    if (jQuery('#amps-dz-form-$sanitized_name').length == 1) {
            var dz = new Dropzone('#amps-dz-form-$sanitized_name');
            dz.on('success', function(file) {
                this.removeFile(file);            
                updateList$sanitized_name();
            }).on("addedfile", function(file) {
                var zone = this;
                file.previewElement.addEventListener("click", function() {
                    zone.removeFile(file);
                });
            });
        }
	    var clip = new Clipboard('.clip-file-copy');
	    clip.on('success', function(e) {
	        alert('Lien copié');
        });
    });
	//]]>
</script>
ENDFORM;

		return $ret;
	}

	public function handleRemove() {
		if ( ! $this->hasCurrentUserUploadRight() ) {
			http_response_code( 403 );
			die();
		}
		$name = $_GET['name'];
		if ( empty( $name ) ) {
			http_response_code( 401 );
			die();
		}

		$full_path = join( '/', array( rtrim( self::getUploadDir( $this->name ), '/' ), rtrim( $name, '/' ) ) );
		if ( file_exists( $full_path ) ) {
			unlink( $full_path );
		}
	}

	public function handleUpload() {
		if ( ! empty( $_FILES ) && wp_verify_nonce( $_REQUEST['amp_upload_file_nonce'], 'amp_upload_file' ) ) {
			$upload_dir = self::getUploadDir( $this->name );
			$uploadfile = $upload_dir . $_FILES['file']['name'];
			if ( ! isset( $_POST['amp_override'] ) && file_exists( $uploadfile ) ) {
				http_response_code( 403 );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [ 'error' => sprintf( 'Le fichier %s existe déjà', $_FILES['file']['name'] ) ] );
				die();
			}
			if ( move_uploaded_file( $_FILES['file']['tmp_name'], $uploadfile ) ) {
				http_response_code( 200 );
				die();
			} else {
				http_response_code( 401 );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [ 'error' => 'Erreur inconnue' ] );
				die();
			}
			//$_FILES['file']['tmp_name'];
			//$_FILES['file']['name'];
		}
		die();
	}

	public function echoFileList() {
		if ( ! $this->hasCurrentUserDownloadRight() ) {
			echo '<p>Vous n\'avez pas les droits pour accéder à cet Espace documents</p>';
			die();
		}
		?>
        <table class="docspace_list sortable" style="table-layout: auto">
            <thead>
            <tr>
                <th>Nom du fichier</th>
                <th>Type</th>
                <th>Taille</th>
                <th>Date de dernière modification</th>
                <th></th>
            </tr>
            </thead>
            <tbody><?php

			// Adds pretty filesizes
			function pretty_filesize( $file ) {
				$size = filesize( $file );
				if ( $size < 1024 ) {
					$size = $size . "o";
				} elseif ( ( $size < 1048576 ) && ( $size > 1023 ) ) {
					$size = round( $size / 1024, 1 ) . "Ko";
				} elseif ( ( $size < 1073741824 ) && ( $size > 1048575 ) ) {
					$size = round( $size / 1048576, 1 ) . "Mo";
				} else {
					$size = round( $size / 1073741824, 1 ) . "Go";
				}

				return $size;
			}

			// Opens directory
			$dir         = self::getUploadDir( $this->name );
			$myDirectory = opendir( $dir );

			$dirArray = [];
			// Gets each entry
			while ( $entryName = readdir( $myDirectory ) ) {
				$dirArray[] = $entryName;
			}

			// Closes directory
			closedir( $myDirectory );

			// Counts elements in array
			$indexCount = count( $dirArray );

			// Sorts files
			sort( $dirArray );

			// Loops through the array of files
			for ( $index = 0; $index < $indexCount; $index ++ ) {

				// Decides if hidden files should be displayed, based on query above.
				if ( 'index.php' != $dirArray[ $index ] && substr( "$dirArray[$index]", 0, 1 ) != '.' ) {
					$full_path = join( '/', array( rtrim( $dir, '/' ), rtrim( $dirArray[ $index ], '/' ) ) );
					// Resets Variables
					$favicon = "";
					$class   = "file";

					// Gets File Names
					$name = $dirArray[ $index ];

					// Gets Date Modified
					$modtime = date( "j M Y H:i", filemtime( $full_path ) );
					$timekey = date( "YmdHis", filemtime( $full_path ) );

					// Separates directories, and performs operations on those directories
					if ( is_dir( $full_path ) ) {
						$extn    = "&lt;Dossier&gt;";
						$size    = "&lt;Dossier&gt;";
						$sizekey = "0";
						$class   = "dir";
					} // File-only operations
					else {
						// Gets file extension
						$extn = pathinfo( $dirArray[ $index ], PATHINFO_EXTENSION );

						// Prettifies file type
						switch ( $extn ) {
							case "png":
								$extn = "Image PNG";
								break;
							case "jpg":
								$extn = "Image JPEG";
								break;
							case "jpeg":
								$extn = "Image JPEG";
								break;
							case "svg":
								$extn = "Image SVG";
								break;
							case "gif":
								$extn = "Image GIF";
								break;
							case "ico":
								$extn = "Icône";
								break;

							case "txt":
								$extn = "Fichier texte";
								break;
							case "log":
								$extn = "Fichier log";
								break;
							case "htm":
								$extn = "Fichier HTML";
								break;
							case "html":
								$extn = "Fichier HTML";
								break;
							case "xhtml":
								$extn = "Fichier HTML";
								break;
							case "shtml":
								$extn = "Fichier HTML";
								break;
							case "php":
								$extn = "PHP Script";
								break;
							case "js":
								$extn = "Javascript File";
								break;
							case "css":
								$extn = "Stylesheet";
								break;

							case "pdf":
								$extn = "Document PDF";
								break;
							case "xls":
								$extn = "Feuille de calcul Excel";
								break;
							case "xlsx":
								$extn = "Feuille de calcul Excel";
								break;
							case "ods":
								$extn = "Feuille de calcul LibreOffice";
								break;
							case "doc":
								$extn = "Fichier Word";
								break;
							case "docx":
								$extn = "Fichier Word";
								break;
							case "odt":
								$extn = "Document LibreOffice";
								break;

							case "zip":
								$extn = "Archive ZIP";
								break;

							default:
								if ( $extn != "" ) {
									$extn = "Fichier " . strtoupper( $extn );
								} else {
									$extn = "Inconnu";
								}
								break;
						}

						// Gets and cleans up file size
						$size    = pretty_filesize( $full_path );
						$sizekey = filesize( $full_path );
					}

					$download_href = $this->getDownloadLink( $name );
					$esc_name      = esc_attr( $name );

					$btn_delete = '';
					if ( $this->hasCurrentUserUploadRight() ) {
						$btn_delete = "<span class='btn docspace-remove dashicons dashicons-dismiss' data-name='$esc_name'></span>";
					}
					// Output
					echo( "
		<tr class='$class'>
			<td><a href='$download_href'$favicon class='name'>$name</a>&nbsp;|&nbsp;<span class='clip-file-copy' role='button' style='cursor: pointer' title='Copier le lien' data-clipboard-text='{$download_href}'><i class=\"fa fa-copy\"></i></span></td>
			<td>$extn</td>
			<td sorttable_customkey='$sizekey'>$size</td>
			<td sorttable_customkey='$timekey'>$modtime</td>
			<td>
			$btn_delete
			</td>
		</tr>" );
				}
			}
			?>
            </tbody>
        </table>
		<?php
		die();
	}

	public function getDownloadLink( $file_name ) {
		return add_query_arg(
			[
				'action' => 'amp_docspace_download_' . $this->name,
				'file'   => urlencode( $file_name ),
			],
			admin_url( 'admin-post.php' ) );
	}

	public function handleDownload() {
		if ( ! $this->hasCurrentUserDownloadRight() ) {
			http_response_code( 403 );
			die();
		}
		if ( ! isset( $_GET['file'] ) ) {
			http_response_code( 401 );
			die();
		}

		$filepath = self::getUploadDir( $this->name ) . urldecode( $_GET['file'] );
		if ( file_exists( $filepath ) ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: ' . mime_content_type( $filepath ) );
			header( 'Content-Disposition: attachment; filename="' . basename( $filepath ) . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $filepath ) );
			flush(); // Flush system output buffer
			readfile( $filepath );
			die();
		}

		http_response_code( 404 );
		die();
	}

	public function hasCurrentUserUploadRight() {
		return empty( $this->upload_capability ) || current_user_can( $this->upload_capability );
	}

	public function hasCurrentUserDownloadRight() {
		return empty( $this->download_capability ) || current_user_can( $this->download_capability );
	}

	public function hasCurrentUserListRight() {
		return empty( $this->list_capability ) || current_user_can( $this->list_capability );
	}

	public static function getUploadDir( $name ) {
		$subfolder = "$name/";
		$dir       = wp_upload_dir()['basedir'] . '/amapress-docspace/';
		$created   = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', "w" );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', "w" );
			fclose( $handle );
		}

		if ( ! empty( $subfolder ) ) {
			$dir     = $dir . $subfolder;
			$created = wp_mkdir_p( $dir );
			if ( $created ) {
				$handle = @fopen( $dir . '.htaccess', "w" );
				fwrite( $handle, 'DENY FROM ALL' );
				fclose( $handle );
				$handle = @fopen( $dir . 'index.php', "w" );
				fclose( $handle );
			}
		}

		return $dir;
	}

}