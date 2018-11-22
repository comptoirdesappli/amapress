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
			'name'       => 'Queue &amp; SMTP',
			'position'   => '25.16',
			'capability' => 'manage_options',
			'icon'       => 'dashicons-admin-tools',
		),
		'options'  => array(
//			array(
//				'type' => 'note',
//				'desc' => 'ici vous pouvez gérer...'
//			),
		),
		'tabs'     => array(
			'Options de la file de messages' => array(
				'id'      => 'amapress_mailqueue_options',
				'desc'    => '',
				'options' => array(
					array(
						'id'      => 'mail_queue_use_queue',
						'name'    => 'Utiliser la file d\'envoi de mails',
						'type'    => 'checkbox',
						'default' => '1',
					),
					array(
						'id'      => 'mail_queue_limit',
						'name'    => 'Mails par interval',
						'type'    => 'number',
						'desc'    => 'Nombre de mails envoyés à chaque interval d\'exécution de la file d\'envoi de mails',
						'default' => '2',
					),
					array(
						'id'      => 'mail_queue_interval',
						'name'    => 'Interval',
						'type'    => 'number',
						'desc'    => 'Interval d\'exécution de la file d\'envoi de mails',
						'default' => '30',
					),
					array(
						'type' => 'save',
					),
				)
			),
			'SMTP externe'                   => array(
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

							return '<p>Cliquez <a href="' . $url . '" target="_blank">ici</a> pour tester la configuraiton mail actuelle</p>';
						}
					),
					array(
						'type' => 'note',
						'desc' => 'Saisir la configuration SMTP de votre fournisseur ou laisser vide pour utiliser la configuration mail de l\'hébergement',
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
						'name' => 'Host',
						'type' => 'text',
					),
					array(
						'id'   => 'mail_queue_smtp_port',
						'name' => 'Port',
						'type' => 'number',
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
						'id'   => 'mail_queue_smtp_auth_username',
						'name' => 'Username',
						'type' => 'text',
					),
					array(
						'id'          => 'mail_queue_smtp_auth_password',
						'name'        => 'Password',
						'type'        => 'text',
						'is_password' => true,
					),
					array(
						'type' => 'save',
					),
				)
			),
			'Mails en attente'               => array(
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
			'Mails en erreur'                => array(
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
			'Log des mails'                  => array(
				'id'      => 'amapress_mailqueue_mail_logs',
				'desc'    => '',
				'options' => array(
					array(
						'id'      => 'mail_queue_log_clean_days',
						'type'    => 'number',
						'step'    => 1,
						'default' => 90,
						'name'    => 'Nettoyer les logs (jours)',
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

function amapress_mailing_queue_waiting_mail_list() {
	return amapress_mailing_queue_mail_list( 'waiting-mails', 'waiting' );
}

function amapress_mailing_queue_errored_mail_list() {
	return amapress_mailing_queue_mail_list( 'errored-mails', 'errored' );
}

function amapress_mailing_queue_logged_mail_list() {
	return amapress_mailing_queue_mail_list( 'logged-mails', 'logged', [
		'order' => [ 0, 'desc' ],
	] );
}

function amapress_mailing_queue_mail_list( $id, $type, $options = [] ) {
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
	$emails = AmapressSMTPMailingQueue::loadDataFromFiles( true, $type );
	$data   = array();
	foreach ( $emails as $email ) {
		$headers = implode( '<br/>', array_map( function ( $h ) {
			return esc_html( $h );
		}, is_array( $email['headers'] ) ? $email['headers'] : [] ) );
		$msg     = $email['message'];
		if ( false === strpos( $headers, 'text/html' )
		     && false === strpos( $msg, '<p>' )
		     && false === strpos( $msg, '<br />' ) ) {
			$msg = esc_html( $msg );
		}
		$msg    = wpautop( $msg );
		$data[] = array(
			'time'          => array(
				'val'     => $email['time'],
				'display' => date_i18n( 'd/m/Y H:i', intval( $email['time'] ) ),
			),
			'to'            => esc_html( $email['to'] ),
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
	<label for="target">Envoyer le mail de test à :</label>
	<br/>
	<input type="email" id="target" name="target" value="' . $default_email . '" />
	<br/>
	<input type="submit" value="Envoyer" />
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
		'Test configuration mail',
		'<p>Ceci est un test de la configuration mail</p>',
		'Content-Type: text/html; charset=UTF-8' );

	if ( empty( $errors ) ) {
		echo '<p>Le mail de test vous a été envoyé avec succès</p>';
	} else {
		echo '<p>Des erreurs se sont produites pendant l\'envoi du mail de test (Le transcript SMTP se trouve au dessus) :</p>';
		echo implode( '<br/>', $errors );
	}
}