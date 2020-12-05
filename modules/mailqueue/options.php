<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_mailing_queue_menu_options() {
	return array(
		'subpage'  => true,
		'id'       => 'amapress_mailqueue_options_page',
		'type'     => 'panel',
		'settings' => array(
			'name'       => __( 'File d\'attente &amp; SMTP', 'amapress' ),
			'capability' => 'manage_options',
			'menu_icon'  => 'dashicons-migrate',
		),
		'tabs'     => array(
			__( 'Options de la file des emails sortants', 'amapress' )   => array(
				'id'      => 'amapress_mailqueue_options',
				'options' => array(
					array(
						'id'      => 'mail_queue_use_queue',
						'name'    => __( 'Utiliser la file d\'envoi des emails sortants', 'amapress' ),
						'type'    => 'checkbox',
						'default' => '1',
					),
					array(
						'type' => 'note',
						'desc' => function ( $o ) {
							$ret = '<p>' . __( 'Nombre maximum d\'emails envoyés par heure :', 'amapress' ) . ' <span id="amps-mail-per-hour">0</span></p>';
							$ret .= '<p>' . __( 'Nombre maximum d\'emails envoyés par jour :', 'amapress' ) . ' <span id="amps-mail-per-day">0</span></p>';
							$ret .= '<script type="application/javascript">
jQuery(function($) {
    var $per_hour = $("#amps-mail-per-hour");
    var $per_day = $("#amps-mail-per-day");
    function updateCounters() {
        var mail_queue_limit = parseInt($("#amapress_mail_queue_limit").val());
        var mail_queue_interval = parseInt($("#amapress_mail_queue_interval").val());
        $per_hour.text((mail_queue_limit / mail_queue_interval * 3600.0).toFixed(0));
        $per_day.text((mail_queue_limit / mail_queue_interval * 3600.0 * 24.0).toFixed(0));
    }
    $("#amapress_mail_queue_limit, #amapress_mail_queue_interval").change(updateCounters);
    updateCounters();
});
</script>';

							return $ret;
						}
					),
					array(
						'id'      => 'mail_queue_limit',
						'name'    => __( 'Emails par intervalle', 'amapress' ),
						'type'    => 'number',
						'min'     => 1,
						'unit'    => __( 'emails', 'amapress' ),
						'desc'    => __( 'Nombre d\'emails envoyés à chaque intervalle d\'exécution de la file d\'envoi des emails sortants', 'amapress' ),
						'default' => AMAPRESS_MAIL_QUEUE_DEFAULT_LIMIT,
					),
					array(
						'id'      => 'mail_queue_interval',
						'name'    => __( 'Intervalle', 'amapress' ),
						'type'    => 'number',
						'min'     => 30,
						'unit'    => __( 'secondes', 'amapress' ),
						'desc'    => __( 'Intervalle d\'exécution de la file d\'envoi des emails sortants', 'amapress' ) .
						             __( '<br/>Cet intervalle sert également à cadencer l\'envoi dans les files d\'attentes des Emails groupés (avec SMTP externe) : ', 'amapress' ) .
						             Amapress::makeLink( admin_url( 'edit.php?post_type=amps_mlgrp' ), __( ' Tableau de bord>Emails groupés>Comptes', 'amapress' ), true, true ),
						'default' => AMAPRESS_MAIL_QUEUE_DEFAULT_INTERVAL,
					),
					array(
						'id'      => 'avoid_send_wp_from',
						'name'    => __( 'Envoi au site', 'amapress' ),
						'type'    => 'checkbox',
						'desc'    => function ( $o ) {
							return sprintf( __( 'Eviter d\'envoyer les emails avec destinataires en Cc/Bcc à l\'adresse email du site (%s)', 'amapress' ), amapress_mail_from( amapress_get_default_wordpress_from_email() ) );
						},
						'default' => true,
					),
					//
					array(
						'type' => 'save',
					),
				)
			),
			'SMTP externe'                                               => array(
				'id'      => 'amapress_mailqueue_stmp',
				'options' => array(
					array(
						'id'     => 'mail_queue_send_test_mail',
						'name'   => __( 'Tester', 'amapress' ),
						'type'   => 'custom',
						'custom' => function ( $option ) {
							$url = add_query_arg(
								array(
									'action' => 'amapress_test_mail_config',
								),
								admin_url( 'admin.php' )
							);

							return '<p>' . sprintf( __( 'Cliquez <a href="%s" target="_blank">ici</a> pour tester la configuration emails sortants actuelle', 'amapress' ), $url ) . '</p>';
						}
					),
					array(
						'type' => 'note',
						'desc' => __( 'Laisser vide pour utiliser la configuration mail de l\'hébergement (recommandé) ou saisir la configuration SMTP de votre fournisseur', 'amapress' ),
					),
					array(
						'id'   => 'mail_queue_from_name',
						'name' => __( 'From Name', 'amapress' ),
						'type' => 'text',
					),
					array(
						'id'   => 'mail_queue_from_email',
						'name' => __( 'From Email', 'amapress' ),
						'type' => 'text',
					),
					array(
						'id'      => 'mail_queue_encryption',
						'name'    => __( 'Encryption', 'amapress' ),
						'type'    => 'select',
						'options' => array(
							''    => __( 'None', 'amapress' ),
							'tls' => 'TLS',
							'ssl' => 'SSL',
						)
					),
					array(
						'id'   => 'mail_queue_smtp_host',
						'name' => 'SMTP Host',
						'type' => 'text',
					),
					array(
						'id'     => 'mail_queue_smtp_port',
						'name'   => 'SMTP Port',
						'desc'   => __( 'Default ports : SMTP 25; SMTP SSL 465; SMTP TLS 587', 'amapress' ),
						'type'   => 'number',
						'max'    => 65535,
						'slider' => false,
					),
					array(
						'id'   => 'mail_queue_smtp_timeout',
						'name' => __( 'Timeout', 'amapress' ),
						'type' => 'number',
					),
					array(
						'id'   => 'mail_queue_smtp_use_authentication',
						'name' => __( 'Use authentication', 'amapress' ),
						'type' => 'checkbox',
					),
					array(
						'id'           => 'mail_queue_smtp_auth_username',
						'name'         => __( 'Username', 'amapress' ),
						'autocomplete' => false,
						'type'         => 'text',
					),
					array(
						'id'           => 'mail_queue_smtp_auth_password',
						'name'         => __( 'Password', 'amapress' ),
						'type'         => 'text',
						'autocomplete' => false,
						'is_password'  => true,
					),
					array(
						'type' => 'save',
					),
				)
			),
			__( 'Emails sortants en attente', 'amapress' ) . ' <span class="badge">' .
			amapress_mailing_queue_waiting_mail_list_count() . '</span>' => array(
				'id'      => 'amapress_mailqueue_waiting_mails',
				'options' => array(
					array(
						'id'     => 'mail_queue_waiting_list',
						'type'   => 'custom',
						'name'   => __( 'En attente', 'amapress' ),
						'custom' => 'amapress_mailing_queue_waiting_mail_list',
					),
				),
			),
			__( 'Emails sortants en erreur', 'amapress' ) . ' <span class="badge">' .
			amapress_mailing_queue_errored_mail_list_count() . '</span>' => array(
				'id'      => 'amapress_mailqueue_errored_mails',
				'options' => array(
					array(
						'id'     => 'mail_queue_errored_list',
						'type'   => 'custom',
						'name'   => __( 'En erreur', 'amapress' ),
						'custom' => 'amapress_mailing_queue_errored_mail_list',
					),
				),
			),
			__( 'Log des emails sortants', 'amapress' )                  => array(
				'id'      => 'amapress_mailqueue_mail_logs',
				'options' => array(
					array(
						'id'      => 'mail_queue_log_clean_days',
						'type'    => 'number',
						'step'    => 1,
						'default' => 90,
						'name'    => __( 'Nettoyer les logs et mails en erreur (jours)', 'amapress' ),
					),
					array(
						'type' => 'save',
					),
					array(
						'id'     => 'mail_queue_logged_list',
						'type'   => 'custom',
						'name'   => __( 'Logs', 'amapress' ),
						'custom' => 'amapress_mailing_queue_logged_mail_list',
					),
				),
			),
		),
	);
}

function amapress_mailing_queue_waiting_mail_list( $mlgrp_id = '' ) {
	return amapress_mailing_queue_mail_list( 'waiting-mails', $mlgrp_id, 'waiting' );
}

function amapress_mailing_queue_waiting_mail_list_count( $mlgrp_id = '' ) {
	require_once( AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueue.php' );

	return count( glob( AmapressSMTPMailingQueue::getUploadDir( $mlgrp_id, 'waiting' ) . '*.json' ) );
}

function amapress_mailing_queue_errored_mail_list_count( $mlgrp_id = '' ) {
	require_once( AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueue.php' );

	return count( glob( AmapressSMTPMailingQueue::getUploadDir( $mlgrp_id, 'errored' ) . '*.json' ) );
}

function amapress_mailing_queue_errored_mail_list( $mlgrp_id = '' ) {
	$href = add_query_arg(
		array(
			'action'   => 'amapress_retry_queue_send_all_msg',
			'mlgrp_id' => $mlgrp_id,
		),
		admin_url( 'admin.php' )
	);
	$ret  = '<p><a class="button button-secondary" href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la nouvelle tentative d\'envoi des emails en erreur ?', 'amapress' ) ) . '\')">' . __( 'Renvoyer tous les emails en erreur', 'amapress' ) . '</a></p>';
	$ret  .= amapress_mailing_queue_mail_list( 'errored-mails', $mlgrp_id, 'errored' );

	return $ret;
}

function amapress_mailing_queue_logged_mail_list( $mlgrp_id = '' ) {
	return amapress_mailing_queue_mail_list( 'logged-mails', $mlgrp_id, 'logged', [
		'order' => [ 0, 'desc' ],
	] );
}

function amapress_mailing_queue_mail_list( $id, $mlgrp_id, $type, $options = [] ) {
	//compact('to', 'subject', 'message', 'headers', 'attachments', 'time', 'errors')
	$columns   = array();
	$columns[] = array(
		'title'              => __( 'Date', 'amapress' ),
		'data'               => array(
			'_'    => 'time.display',
			'sort' => 'time.val',
		),
		'responsivePriority' => 1,
		'className'          => 'min-tablet',
	);
	$columns[] = array(
		'title'              => __( 'To', 'amapress' ),
		'data'               => 'to',
		'responsivePriority' => 1,
		'className'          => 'min-tablet',
	);
	$columns[] = array(
		'title'              => __( 'Sujet', 'amapress' ),
		'data'               => 'subject',
		'responsivePriority' => 1,
		'className'          => 'min-tablet',
	);
	$columns[] = array(
		'title'     => __( 'Message', 'amapress' ),
		'data'      => 'message',
		'className' => 'none',
	);
	if ( 'errored' == $type ) {
		$columns[] = array(
			'title' => __( 'Erreurs', 'amapress' ),
			'data'  => 'errors',
		);
		$columns[] = array(
			'title' => __( 'Essais', 'amapress' ),
			'data'  => 'retries_count',
		);
	}
	$columns[] = array(
		'title'     => __( 'Headers', 'amapress' ),
		'data'      => 'headers',
		'className' => 'none',
	);
//        array(
//            'title' => '',
//            'data' => '',
//        ),
//);
	$emails = AmapressSMTPMailingQueue::loadDataFromFiles( $mlgrp_id, true, $type );
	$data   = array();
	foreach ( $emails as $email ) {
		$raw_headers = is_array( $email['headers'] ) ? $email['headers'] : [];
		$headers     = implode( '<br/>', array_map( function ( $h ) {
			return esc_html( $h );
		}, $raw_headers ) );
		$cc          = '';
		$bcc         = '';
		foreach ( $raw_headers as $raw_header ) {
			if ( 0 === strpos( $raw_header, 'Cc:' ) ) {
				$cc = $raw_header;
			} elseif ( 0 === strpos( $raw_header, 'Bcc:' ) ) {
				$bcc = $raw_header;
			}
		}
		$msg = $email['message'];
		if ( is_array( $msg ) ) {
			if ( isset( $msg['text'] ) ) {
				$msg = $msg['text'];
			}
		}
		if ( false === strpos( $headers, 'text/html' )
		     && false === strpos( $msg, '<p>' )
		     && false === strpos( $msg, '<br />' ) ) {
			$msg = esc_html( $msg );
		}

		$link_delete_msg = '';

		if ( 'errored' == $type || 'waiting' == $type ) {
			$href            = add_query_arg(
				array(
					'action'   => 'amapress_mailgroup_edit_msg',
					'type'     => $type,
					'mlgrp_id' => $mlgrp_id,
					'msg_file' => $email['basename'],
				),
				admin_url( 'admin.php' )
			);
			$link_delete_msg .= '<br/><a href="' . esc_attr( $href ) . '">' . __( 'Editer les entêtes', 'amapress' ) . '</a>';
		}
		$href            = add_query_arg(
			array(
				'action'   => 'amapress_delete_queue_msg',
				'type'     => $type,
				'mlgrp_id' => $mlgrp_id,
				'msg_file' => $email['basename'],
			),
			admin_url( 'admin.php' )
		);
		$link_delete_msg .= '<br/><a href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la suppression de cet email ?', 'amapress' ) ) . '\')">' . __( 'Supprimer', 'amapress' ) . '</a>';

		if ( isset( $email['message'] ) && is_array( $email['message'] ) && isset( $email['message']['ml_grp_msg_id'] ) ) {
			$href            = add_query_arg(
				array(
					'action'        => 'amapress_delete_queue_msg',
					'type'          => $type,
					'msg_file'      => $email['basename'],
					'mlgrp_id'      => $mlgrp_id,
					'ml_grp_msg_id' => $email['message']['ml_grp_msg_id'],
				),
				admin_url( 'admin.php' )
			);
			$link_delete_msg .= '<br/><a href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la suppression ?', 'amapress' ) ) . '\')">' . __( 'Supprimer pour tous les destinataires', 'amapress' ) . '</a>';
		}

		$href           = add_query_arg(
			array(
				'action'   => 'amapress_retry_queue_send_msg',
				'mlgrp_id' => $mlgrp_id,
				'msg_file' => $email['basename'],
			),
			admin_url( 'admin.php' )
		);
		$link_retry_msg = '<br/><a href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la nouvelle tentative d\'envoi de cet email ?', 'amapress' ) ) . '\')">' . __( 'Renvoyer', 'amapress' ) . '</a>';

		$msg    = wpautop( $msg );
		$data[] = array(
			'time'          => array(
				'val'     => $email['time'],
				'display' => date_i18n( 'd/m/Y H:i', intval( $email['time'] ) )
				             . $link_delete_msg
				             . ( 'errored' == $email['type'] ? $link_retry_msg : '' ),
			),
			'to'            => esc_html( str_replace( ',', ', ', $email['to'] ) )
			                   . ( ! empty( $cc ) ? '<br/>' . str_replace( ',', ', ', $cc ) : '' )
			                   . ( ! empty( $bcc ) ? '<br/>' . str_replace( ',', ', ', $bcc ) : '' ),
			'subject'       => esc_html( $email['subject'] ),
//			'message' => '<div style="word-break: break-all">' . wpautop( $email['message'] ) . '</div>',
			'message'       => $msg,
			'errors'        => var_export( $email['errors'], true ),
			'retries_count' => isset( $email['retries_count'] ) ? $email['retries_count'] : 0,
			'headers'       => $headers,
		);
	}

	return amapress_get_datatable( $id, $columns, $data,
		array_merge(
			$options,
			array(
				'paging' => true,
				'nowrap' => false,
			)
		)
	);
}


add_action( 'admin_action_amapress_delete_queue_msg', 'admin_action_amapress_delete_queue_msg' );
function admin_action_amapress_delete_queue_msg() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}

	$type          = $_REQUEST['type'];
	$msg_file      = $_REQUEST['msg_file'];
	$mlgrp_id      = isset( $_REQUEST['mlgrp_id'] ) ? $_REQUEST['mlgrp_id'] : '';
	$ml_grp_msg_id = isset( $_REQUEST['ml_grp_msg_id'] ) ? $_REQUEST['ml_grp_msg_id'] : '';
	if ( ! empty( $ml_grp_msg_id ) ) {
		AmapressSMTPMailingQueue::deleteMessageByGroup( $mlgrp_id, $ml_grp_msg_id, $type );
	} else {
		AmapressSMTPMailingQueue::deleteFile( $mlgrp_id, $type, $msg_file );
	}
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo sprintf( __( "Email %s supprimé avec succès", 'amapress' ), $msg_file );
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_retry_queue_send_msg', 'admin_action_amapress_retry_queue_send_msg' );
function admin_action_amapress_retry_queue_send_msg() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}

	$msg_file = $_REQUEST['msg_file'];
	$mlgrp_id = isset( $_REQUEST['mlgrp_id'] ) ? $_REQUEST['mlgrp_id'] : '';
	$res      = AmapressSMTPMailingQueue::retrySendMessage( $mlgrp_id, $msg_file );
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		if ( $res ) {
			echo sprintf( __( 'Email %s renvoyé avec succès', 'amapress' ), $msg_file );
		} else {
			echo sprintf( __( 'Email %s non renvoyé', 'amapress' ), $msg_file );
		}
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_retry_queue_send_all_msg', 'admin_action_amapress_retry_queue_send_all_msg' );
function admin_action_amapress_retry_queue_send_all_msg() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}

	$mlgrp_id = isset( $_REQUEST['mlgrp_id'] ) ? $_REQUEST['mlgrp_id'] : '';
	AmapressSMTPMailingQueue::retrySendAllErroredMessages( $mlgrp_id );
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo __( 'Emails en erreur remis pour envoi avec succès', 'amapress' );
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}


add_action( 'admin_action_amapress_test_mail_config', 'amapress_test_mail_config' );
function amapress_test_mail_config() {
	$default_email = wp_get_current_user()->user_email;
	if ( empty( $_REQUEST['target'] ) ) {
		$url = add_query_arg(
			array(
				'action' => 'amapress_test_mail_config',
			),
			admin_url( 'admin.php' )
		);
		echo '<form action="' . $url . '" method="post">
	<label for="target">' . __( 'Envoyer le mail de test à :', 'amapress' ) . '</label>
	<br/>
	<input type="email" id="target" name="target" value="' . $default_email . '" />
	<br/>
	<input type="submit" value="' . esc_attr__( 'Envoyer', 'amapress' ) . '" />
</form>';
		die;
	}

	$email = $_REQUEST['target'];
	add_action( 'phpmailer_init', function ( $phpmailer ) {
		/** @var PHPMailer $phpmailer */
		$phpmailer->SMTPDebug = 2;
	} );
	require_once( AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueueOriginal.php' );
	$errors = AmapressSMTPMailingQueueOriginal::wp_mail( $email,
		__( 'Test configuration email', 'amapress' ),
		'<p>' . __( 'Ceci est un test de la configuration email', 'amapress' ) . '</p>',
		'Content-Type: text/html; charset=UTF-8' );

	if ( empty( $errors ) ) {
		echo '<p>' . __( 'L\'email de test vous a été envoyé avec succès', 'amapress' ) . '</p>';
	} else {
		echo '<p>' . __( 'Des erreurs se sont produites pendant l\'envoi de l\'email de test (Le transcript SMTP se trouve au dessus) :', 'amapress' ) . '</p>';
		echo implode( '<br/>', $errors );
	}
}

add_action( 'admin_action_amapress_mailgroup_edit_msg', 'admin_action_amapress_mailgroup_edit_msg' );
function admin_action_amapress_mailgroup_edit_msg() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Accès non autorisé', 'amapress' ) );
	}

	$mlgrp_id = isset( $_REQUEST['mlgrp_id'] ) ? $_REQUEST['mlgrp_id'] : '';
	$type     = $_REQUEST['type'];
	if ( 'logged' == $type ) {
		wp_die( 'Cannot edit an already sent mail !' );
	}
	$msg_file = $_REQUEST['msg_file'];
	$msg      = AmapressSMTPMailingQueue::loadMessage( $type, $mlgrp_id, $msg_file );
	if ( null == $msg ) {
		wp_die( __( 'Message not found', 'amapress' ) );
	}

	if ( isset( $_POST['save'] ) ) {
		if ( isset( $_POST['to'] ) ) {
			$msg['to'] = wp_unslash( $_POST['to'] );
		}
		if ( isset( $_POST['headers'] ) ) {
			$msg['headers'] = explode( "\n", wp_unslash( $_POST['headers'] ) );
		}
		$msg['retries_count'] = 0;
		if ( ! AmapressSMTPMailingQueue::saveMessage( $mlgrp_id, $type, $msg_file, $msg ) ) {
			wp_die( 'Error while saving !' );
		}
		$redir = '';
		switch ( $type ) {
			case 'waiting':
				if ( ! empty( $mlgrp_id ) ) {
					$redir = admin_url( "admin.php?page=mailinggroup_mailqueue&tab=mailgrp-mailqueue-tab-$mlgrp_id" );
				} else {
					$redir = admin_url( 'options-general.php?page=amapress_mailqueue_options_page&tab=amapress_mailqueue_waiting_mails' );
				}
			case 'errored':
				if ( ! empty( $mlgrp_id ) ) {
					$redir = admin_url( "admin.php?page=mailinggroup_mailerrors&tab=mailgrp-mailerrors-tab-$mlgrp_id" );
				} else {
					$redir = admin_url( 'options-general.php?page=amapress_mailqueue_options_page&tab=amapress_mailqueue_errored_mails' );
				}
		}
		wp_redirect_and_exit( $redir );
	} else {
		echo '<style>p {margin: 0; padding: 0}</style>';
		echo '<form action="' . esc_attr( admin_url( 'admin.php' ) ) . '" method="post">';
		echo '<input type="hidden" name="action" value="amapress_mailgroup_edit_msg" />';
		echo '<input type="hidden" name="mlgrp_id" value="' . esc_attr( $mlgrp_id ) . '" />';
		echo '<input type="hidden" name="msg_file" value="' . esc_attr( $msg_file ) . '" />';
		echo '<input type="hidden" name="type" value="' . esc_attr( $type ) . '" />';
		echo '<p><label for="to">To: </label><br/><input type="text" style="width:25em;font-family:monospace" name="to" id="to" value="' . esc_html( $msg['to'] ) . '" /></p>';
		echo '<p><label for="headers">Headers: </label><br/><textarea cols="50" rows="18" name="headers" id="headers">' . esc_textarea( is_array( $msg['headers'] ) ? implode( "\n", $msg['headers'] ) : $msg['headers'] ) . '</textarea></p>';
		//
		echo '<p>Date: ' . esc_html( ! empty( $m['time'] ) ? date_i18n( 'd/m/Y H:i:s', $m['time'] ) : '' ) . '</p>';
		echo '<p>Errors: ' . esc_html( empty( $msg['errors'] ) ? '' : implode( ' ; ', $msg['errors'] ) ) . '</p>';
		echo '<p>Retries: ' . esc_html( empty( $msg['retries_count'] ) ? '0' : count( $msg['retries_count'] ) ) . '</p>';
		echo '<p>Attachments: ' . esc_html( empty( $msg['attachments'] ) ? '0' : count( $msg['attachments'] ) ) . '</p>';
		echo '<p>Subject: ' . esc_html( $msg['subject'] ) . '</p>';
		echo '<p>Content: <pre>' . ( ! empty( $msg['message']['text'] ) ? esc_html( $msg['message']['text'] ) : wp_kses_post( $msg['message']['text'] ) ) . '</pre></p>';
		echo '<p><input type="submit" name="save" value="Enregistrer"></p>';
		echo '</form>';
	}
	exit();
}