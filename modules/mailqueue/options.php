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
			'name'       => 'File d\'attente &amp; SMTP',
			'capability' => 'manage_options',
			'menu_icon'  => 'dashicons-migrate',
		),
		'tabs'     => array(
			'Options de la file des emails sortants'                     => array(
				'id'      => 'amapress_mailqueue_options',
				'desc'    => '',
				'options' => array(
					array(
						'id'      => 'mail_queue_use_queue',
						'name'    => 'Utiliser la file d\'envoi des emails sortants',
						'type'    => 'checkbox',
						'default' => '1',
					),
					array(
						'id'      => 'mail_queue_limit',
						'name'    => 'Emails par interval',
						'type'    => 'number',
						'desc'    => 'Nombre d\'emails envoyés à chaque interval d\'exécution de la file d\'envoi des emails sortants',
						'default' => AMAPRESS_MAIL_QUEUE_DEFAULT_LIMIT,
					),
					array(
						'id'      => 'mail_queue_interval',
						'name'    => 'Interval',
						'type'    => 'number',
						'desc'    => 'Interval d\'exécution de la file d\'envoi des emails sortants',
						'default' => AMAPRESS_MAIL_QUEUE_DEFAULT_INTERVAL,
					),
					array(
						'id'      => 'avoid_send_wp_from',
						'name'    => 'Envoi au site',
						'type'    => 'checkbox',
						'desc'    => sprintf( 'Eviter d\'envoyer les emails avec destinataires en Cc/Bcc à l\'adresse email du site (%s)', amapress_mail_from( amapress_get_default_wordpress_from_email() ) ),
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
				'desc'    => '',
				'options' => array(
					array(
						'id'     => 'mail_queue_send_test_mail',
						'name'   => 'Tester',
						'type'   => 'custom',
						'custom' => function ( $option ) {
							$url = add_query_arg(
								array(
									'action' => 'amapress_test_mail_config',
								),
								admin_url( 'admin.php' )
							);

							return '<p>' . sprintf( 'Cliquez <a href="%s" target="_blank">ici</a> pour tester la configuration emails sortants actuelle', $url ) . '</p>';
						}
					),
					array(
						'type' => 'note',
						'desc' => 'Laisser vide pour utiliser la configuration mail de l\'hébergement (recommandé) ou saisir la configuration SMTP de votre fournisseur',
					),
					array(
						'id'   => 'mail_queue_from_name',
						'name' => 'From Name',
						'type' => 'text',
					),
					array(
						'id'   => 'mail_queue_from_email',
						'name' => 'From Email',
						'type' => 'text',
					),
					array(
						'id'      => 'mail_queue_encryption',
						'name'    => 'Encryption',
						'type'    => 'select',
						'options' => array(
							''    => 'None',
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
						'desc'   => 'Default ports : SMTP 25; SMTP SSL 465; SMTP TLS 587',
						'type'   => 'number',
						'max'    => 65535,
						'slider' => false,
					),
					array(
						'id'   => 'mail_queue_smtp_timeout',
						'name' => 'Timeout',
						'type' => 'number',
					),
					array(
						'id'   => 'mail_queue_smtp_use_authentication',
						'name' => 'Use authentication',
						'type' => 'checkbox',
					),
					array(
						'id'           => 'mail_queue_smtp_auth_username',
						'name'         => 'Username',
						'autocomplete' => false,
						'type'         => 'text',
					),
					array(
						'id'           => 'mail_queue_smtp_auth_password',
						'name'         => 'Password',
						'type'         => 'text',
						'autocomplete' => false,
						'is_password'  => true,
					),
					array(
						'type' => 'save',
					),
				)
			),
			'Emails sortants en attente' . ' <span class="badge">' .
			amapress_mailing_queue_waiting_mail_list_count() . '</span>' => array(
				'id'      => 'amapress_mailqueue_waiting_mails',
				'desc'    => '',
				'options' => array(
					array(
						'id'     => 'mail_queue_waiting_list',
						'type'   => 'custom',
						'name'   => 'En attente',
						'custom' => 'amapress_mailing_queue_waiting_mail_list',
					),
				),
			),
			'Emails sortants en erreur' . ' <span class="badge">' .
			amapress_mailing_queue_errored_mail_list_count() . '</span>' => array(
				'id'      => 'amapress_mailqueue_errored_mails',
				'desc'    => '',
				'options' => array(
					array(
						'id'     => 'mail_queue_errored_list',
						'type'   => 'custom',
						'name'   => 'En erreur',
						'custom' => 'amapress_mailing_queue_errored_mail_list',
					),
				),
			),
			'Log des emails sortants'                                    => array(
				'id'      => 'amapress_mailqueue_mail_logs',
				'desc'    => '',
				'options' => array(
					array(
						'id'      => 'mail_queue_log_clean_days',
						'type'    => 'number',
						'step'    => 1,
						'default' => 90,
						'name'    => 'Nettoyer les logs et mails en erreur (jours)',
					),
					array(
						'type' => 'save',
					),
					array(
						'id'     => 'mail_queue_logged_list',
						'type'   => 'custom',
						'name'   => 'Logs',
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
	$ret  = '<p><a class="button button-secondary" href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la nouvelle tentative d\'envoi des emails en erreur ?', 'amapress' ) ) . '\')">' . 'Renvoyer tous les emails en erreur' . '</a></p>';
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
		'title' => 'Date',
		'data'  => array(
			'_'    => 'time.display',
			'sort' => 'time.val',
		),
	);
	$columns[] = array(
		'title' => 'To',
		'data'  => 'to',
	);
	$columns[] = array(
		'title' => 'Sujet',
		'data'  => 'subject',
	);
	$columns[] = array(
		'title' => 'Message',
		'data'  => 'message',
	);
	if ( 'errored' == $type ) {
		$columns[] = array(
			'title' => 'Erreurs',
			'data'  => 'errors',
		);
		$columns[] = array(
			'title' => 'Essais',
			'data'  => 'retries_count',
		);
	}
	$columns[] = array(
		'title' => 'Headers',
		'data'  => 'headers',
	);
//        array(
//            'title' => '',
//            'data' => '',
//        ),
//);
	$emails = AmapressSMTPMailingQueue::loadDataFromFiles( $mlgrp_id, true, $type );
	$data   = array();
	foreach ( $emails as $email ) {
		$headers = implode( '<br/>', array_map( function ( $h ) {
			return esc_html( $h );
		}, is_array( $email['headers'] ) ? $email['headers'] : [] ) );
		$msg     = $email['message'];
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

		$href            = add_query_arg(
			array(
				'action'   => 'amapress_delete_queue_msg',
				'type'     => $type,
				'mlgrp_id' => $mlgrp_id,
				'msg_file' => $email['basename'],
			),
			admin_url( 'admin.php' )
		);
		$link_delete_msg = '<br/><a href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la suppression de cet email ?', 'amapress' ) ) . '\')">' . 'Supprimer' . '</a>';

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
			$link_delete_msg .= '<br/><a href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la suppression ?', 'amapress' ) ) . '\')">' . 'Supprimer pour tous les destinataires' . '</a>';
		}

		$href           = add_query_arg(
			array(
				'action'   => 'amapress_retry_queue_send_msg',
				'mlgrp_id' => $mlgrp_id,
				'msg_file' => $email['basename'],
			),
			admin_url( 'admin.php' )
		);
		$link_retry_msg = '<br/><a href="' . esc_attr( $href ) . '" onclick="return confirm(\'' . esc_js( __( 'Confirmez-vous la nouvelle tentative d\'envoi de cet email ?', 'amapress' ) ) . '\')">' . 'Renvoyer' . '</a>';

		$msg    = wpautop( $msg );
		$data[] = array(
			'time'          => array(
				'val'     => $email['time'],
				'display' => date_i18n( 'd/m/Y H:i', intval( $email['time'] ) )
				             . $link_delete_msg
				             . ( 'errored' == $email['type'] ? $link_retry_msg : '' ),
			),
			'to'            => esc_html( str_replace( ',', ', ', $email['to'] ) ),
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
		wp_die( 'Accès non autorisé' );
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
		echo "Email $msg_file supprimé avec succès";
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_retry_queue_send_msg', 'admin_action_amapress_retry_queue_send_msg' );
function admin_action_amapress_retry_queue_send_msg() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accès non autorisé' );
	}

	$msg_file = $_REQUEST['msg_file'];
	$mlgrp_id = isset( $_REQUEST['mlgrp_id'] ) ? $_REQUEST['mlgrp_id'] : '';
	$res      = AmapressSMTPMailingQueue::retrySendMessage( $mlgrp_id, $msg_file );
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		if ( $res ) {
			echo "Email $msg_file renvoyé avec succès";
		} else {
			echo "Email $msg_file non renvoyé";
		}
	} else {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}
	exit();
}

add_action( 'admin_action_amapress_retry_queue_send_all_msg', 'admin_action_amapress_retry_queue_send_all_msg' );
function admin_action_amapress_retry_queue_send_all_msg() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accès non autorisé' );
	}

	$mlgrp_id = isset( $_REQUEST['mlgrp_id'] ) ? $_REQUEST['mlgrp_id'] : '';
	AmapressSMTPMailingQueue::retrySendAllErroredMessages( $mlgrp_id );
	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		echo 'Emails en erreur remis pour envoi avec succès';
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
	<label for="target">' . 'Envoyer le mail de test à :' . '</label>
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
		'Test configuration email',
		'<p>' . 'Ceci est un test de la configuration email' . '</p>',
		'Content-Type: text/html; charset=UTF-8' );

	if ( empty( $errors ) ) {
		echo '<p>' . 'L\'email de test vous a été envoyé avec succès' . '</p>';
	} else {
		echo '<p>' . 'Des erreurs se sont produites pendant l\'envoi de l\'email de test (Le transcript SMTP se trouve au dessus) :' . '</p>';
		echo implode( '<br/>', $errors );
	}
}