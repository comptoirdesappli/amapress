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
			array(
				'type' => 'note',
				'desc' => 'ici vous pouvez gérer...'
			),
		),
		'tabs'     => array(
			'Options de la file de messages' => array(
				'desc'    => '',
				'options' => array(
					array(
						'id'      => 'mail_queue_use_queue',
						'name'    => 'Use Queue',
						'type'    => 'checkbox',
						'default' => '1',
					),
					array(
						'id'      => 'mail_queue_limit',
						'name'    => 'Queue Limit',
						'type'    => 'number',
						'desc'    => 'Amount of mails processed per cronjob execution.',
						'default' => '1',
					),
					array(
						'id'      => 'mail_queue_interval',
						'name'    => 'wp_cron interval',
						'type'    => 'number',
						'desc'    => 'Time in seconds wp_cron waits until next execution.',
						'default' => '60',
					),
					array(
						'type' => 'save',
					),
				)
			),
			'SMTP externe'                   => array(
				'desc'    => '',
				'options' => array(
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
			'Mails en attente' => array(
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
			'Mails en erreur'  => array(
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
			'Log des mails'    => array(
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
	return amapress_mailing_queue_mail_list( 'logged-mails', 'logged' );
}

function amapress_mailing_queue_mail_list( $id, $type ) {
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
		$data[] = array(
			'time'    => array(
				'val'     => $email['time'],
				'display' => date_i18n( 'd/m/Y H:i', intval( $email['time'] ) ),
			),
			'to'      => esc_html( $email['to'] ),
			'subject' => esc_html( $email['subject'] ),
//			'message' => '<div style="word-break: break-all">' . wpautop( $email['message'] ) . '</div>',
			'message' => wpautop( $email['message'] ),
			'errors'  => var_export( $email['errors'], true ),
			'headers' => implode( '<br/>', array_map( function ( $h ) {
				return esc_html( $h );
			}, $email['headers'] ) ),
		);
	}

	return amapress_get_datatable( $id, $columns, $data,
		array(
			'paging' => true,
			'nowrap' => false,
		)
	);
}