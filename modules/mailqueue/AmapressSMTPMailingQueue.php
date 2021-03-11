<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressSMTPMailingQueue {
	private $mlgrp_id;

	public function __construct( $mlgrp_id = '' ) {
		$this->mlgrp_id = $mlgrp_id;
		$this->init();
	}

	/**
	 * Adds hooks, actions and filters for plugin.
	 */
	protected function init() {
		// Actions
		add_action( 'phpmailer_init', [ $this, 'initMailer' ] );

		if ( ! defined( 'FREE_PAGES_PERSO' ) || ! FREE_PAGES_PERSO ) {
			add_filter( 'cron_schedules', [ $this, 'addWpCronInterval' ] );

			$start_queue_hook_name = 'amps_smq_start_queue' . $this->mlgrp_id;
			add_action( $start_queue_hook_name, [ $this, 'processQueue' ] );
			if ( ! wp_next_scheduled( $start_queue_hook_name ) ) {
				wp_schedule_event( time(), 'amps_smq', $start_queue_hook_name );
			}

			$clean_log_hook_name = 'amps_smq_clean_log' . $this->mlgrp_id;
			add_action( $clean_log_hook_name, [ $this, 'cleanLogs' ] );
			if ( ! wp_next_scheduled( $clean_log_hook_name ) ) {
				wp_schedule_event( time(), 'daily', $clean_log_hook_name );
			}

			add_action( 'tf_set_value_amapress_mail_queue_interval', [ $this, 'refreshWpCron' ] );
		}
	}

	private static function saveMessageFile( $fileName, array $data ) {
		$handle = @fopen( $fileName, "w" );
		if ( $handle ) {
			if ( ! defined( 'JSON_INVALID_UTF8_IGNORE' ) ) {
				foreach ( $data as $k => $v ) {
					if ( is_string( $v ) ) {
						$data[ $k ] = iconv( 'UTF-8', 'UTF-8//IGNORE', $v );
					}
				}
				fwrite( $handle, json_encode( $data ) );
			} else {
				fwrite( $handle, json_encode( $data, JSON_INVALID_UTF8_IGNORE ) );
			}
			fclose( $handle );

			return true;
		} else {
			return false;
		}
	}

	/**
	 * (Re)sets wp_cron, e.g. on activation and interval update.
	 */
	public function refreshWpCron() {
		$start_queue_hook_name = 'amps_smq_start_queue' . $this->mlgrp_id;
		wp_clear_scheduled_hook( $start_queue_hook_name );
		wp_schedule_event( time(), 'amps_smq', $start_queue_hook_name );
	}

	/**
	 * Adds custom interval based on interval settings to wp_cron.
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function addWpCronInterval( $schedules ) {
		$mail_queue_interval   = Amapress::getOption( 'mail_queue_interval' );
		$interval              = ! empty( $mail_queue_interval ) ? intval( $mail_queue_interval ) : AMAPRESS_MAIL_QUEUE_DEFAULT_INTERVAL;
		$schedules['amps_smq'] = [
			'interval' => $interval,
			'display'  => __( 'Interval for sending mail', 'smtp-mailing-queue' )
		];

		return $schedules;
	}

	/**
	 * Writes mail data to json file or sends mail directly.
	 *
	 * @param string|array $to
	 * @param string $subject
	 * @param array|string $message
	 * @param array|string $headers
	 * @param array $attachments
	 *
	 * @return bool
	 */
	public function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		if ( empty( $headers ) ) {
			$headers = array();
		}
		if ( is_string( $headers ) ) {
			$headers = explode( "\n", $headers );
		}
		$headers = array_filter( $headers,
			function ( $h ) {
				return ! empty( $h ) && ! empty( trim( $h ) );
			} );
		$ct      = array_filter( $headers,
			function ( $h ) {
				return 0 === stripos( $h, 'Content-Type' );
			} );
		if ( empty( $ct ) && 'text/html' == apply_filters( 'wp_mail_content_type', 'text/plain' ) ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		$use = Amapress::getOption( 'mail_queue_use_queue' );
		if ( defined( 'FREE_PAGES_PERSO' ) && FREE_PAGES_PERSO ) {
			$use = false;
		}
		if ( empty( $use ) || ! $use || apply_filters( 'amapress_mail_queue_bypass', false ) ) {
			$retries = apply_filters( 'amapress_mail_queue_retries', 1 );
			do {
				$time    = amapress_time();
				$errors  = self::sendMail( $this->mlgrp_id,
					compact( 'to', 'subject', 'message', 'headers', 'attachments', 'time' ),
					1 == $retries );
				$retries -= 1;
			} while ( $retries > 0 && ! empty( $errors ) );

			return empty( $errors );
		} else {
			return self::storeMail( $this->mlgrp_id, 'waiting', $to, $subject, $message, $headers, $attachments );
		}
	}

	public function cleanLogs() {
		if ( ! empty( $this->mlgrp_id ) ) {
			$mail_queue_log_clean_days = Amapress::getOption( 'mail_group_waiting_log_clean_days' );
		} else {
			$mail_queue_log_clean_days = Amapress::getOption( 'mail_queue_log_clean_days' );

		}
		if ( $mail_queue_log_clean_days < 0 ) {
			return;
		}

		$clean_date = Amapress::add_days( Amapress::start_of_day( time() ), - $mail_queue_log_clean_days );
		foreach (
			self::loadDataFromFiles( $this->mlgrp_id, true, 'logged' )
			as $filename => $email
		) {
			if ( $email['time'] < $clean_date ) {
				@unlink( $filename );
			}
		}
		foreach (
			self::loadDataFromFiles( $this->mlgrp_id, true, 'errored' )
			as $filename => $email
		) {
			if ( $email['time'] < $clean_date ) {
				@unlink( $filename );
			}
		}
	}

	/**
	 * Writes mail data to json file.
	 *
	 * @param string $type
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param array|string $headers
	 * @param array $attachments
	 * @param string $time
	 *
	 * @return bool
	 */
	public static function storeMail(
		$mlgrp_id, $type, $to, $subject, $message,
		$headers = '', $attachments = array(), $time = null, $errors = null, $retries_count = 0,
		$ctime = 0
	) {
		require_once AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueueOriginal.php';
		AmapressSMTPMailingQueueOriginal::EnsurePHPMailerInit();
		global $phpmailer;

		$ctime = empty( $ctime ) ? amapress_time() : $ctime;
		$time  = $time ?: amapress_time();
		$data  = compact( 'to', 'subject', 'message', 'headers', 'attachments', 'time', 'errors', 'retries_count', 'ctime' );

		if ( ! is_array( $to ) ) {
			$to = explode( ',', $to );
		}

		$validEmails   = [];
		$invalidEmails = [];
		foreach ( (array) $to as $recipient ) {
			// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
			$email = $recipient;
			if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
				if ( count( $matches ) == 3 ) {
					$email = $matches[2];
				}
			}
			if ( $phpmailer->validateAddress( $email ) ) {
				$validEmails[] = $recipient;
			} else {
				$invalidEmails[] = $recipient;
			}
		}

		$fileName   = self::getUploadDir( $mlgrp_id, $type ) . microtime( true ) . '.json';
		$data['to'] = implode( ',', $validEmails );
		if ( ! empty( $invalidEmails ) ) {
			$data['invalid_to'] = implode( ',', $invalidEmails );
		}

		return self::saveMessageFile( $fileName, $data );
	}

	public static function getErroredMailsCount( $mlgrp_id = '' ) {
		return count( glob( self::getUploadDir( $mlgrp_id, 'errored' ) . '*.json' ) );
	}

	/**
	 * Creates upload dir if it not existing.
	 * Adds htaccess protection to upload dir.
	 *
	 * @param string $type
	 *
	 * @return string upload dir
	 */
	public static function getUploadDir( $mlgrp_id = '', $type = 'waiting' ) {
		$subfolder = '';
		switch ( $type ) {
			case 'errored':
				$subfolder = 'invalid/';
				break;
			case 'logged':
				$subfolder = 'logs/';
				break;
		}
		$dir     = wp_upload_dir()['basedir'] . '/amapress-smtp-mailing-queue' . ( ! empty( $mlgrp_id ) ? '-' . $mlgrp_id : '' ) . '/';
		$created = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', "w" );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', "w" );
			fclose( $handle );
		}

		if ( ! empty( $subfolder ) ) {
			$dir = $dir . $subfolder;
			wp_mkdir_p( $dir );
		}

		return $dir;
	}

	/**
	 * Loads mail data from json files.
	 *
	 * @param bool $ignoreLimit
	 * @param bool|string $invalid Load invalid emails
	 *
	 * @return array Mail data
	 */
	public static function loadDataFromFiles( $mlgrp_id, $ignoreLimit = false, $types = [ 'waiting' ] ) {
		$queue_limit = Amapress::getOption( 'mail_queue_limit' );
		if ( ! empty( $mlgrp_id ) ) {
			$mlgrp = AmapressMailingGroup::getBy( $mlgrp_id );
			if ( $mlgrp && $mlgrp->getSmtpMaxMailsPerHour() > 0 ) {
				$mail_queue_interval = Amapress::getOption( 'mail_queue_interval' );
				if ( empty( $mail_queue_interval ) ) {
					$mail_queue_interval = AMAPRESS_MAIL_QUEUE_DEFAULT_INTERVAL;
				}
				$queue_limit = $mlgrp->getSmtpMaxMailsPerHour() / 3600.0 * $mail_queue_interval;
			}
		}
		$emails = [];
		$i      = 0;
		if ( ! is_array( $types ) ) {
			$types = [ $types ];
		}

		foreach ( $types as $type ) {
			if ( 'waiting' == $type || 'errored' == $type || 'logged' == $type ) {
				foreach ( glob( self::getUploadDir( $mlgrp_id, $type ) . '*.json' ) as $filename ) {
					$emails[ $filename ]             = json_decode( file_get_contents( $filename ), true );
					$emails[ $filename ]['type']     = $type;
					$emails[ $filename ]['basename'] = basename( $filename );
					$i ++;
					if ( ! $ignoreLimit && ! empty( $queue_limit ) && $i >= $queue_limit ) {
						break;
					}
				}
			}
		}

		return $emails;
	}

	public static function saveMessage( $mlgrp_id, $type, $msg_file, $msg ) {
		$filename = self::getUploadDir( $mlgrp_id, $type ) . $msg_file;

		return self::saveMessageFile( $filename, $msg );
	}

	public static function loadMessage( $type, $mlgrp_id, $msg_file ) {
		$filename = self::getUploadDir( $mlgrp_id, $type ) . $msg_file;
		if ( ! file_exists( $filename ) ) {
			return null;
		}

		$ret = json_decode( file_get_contents( $filename ), true );
		if ( empty( $ret ) ) {
			return null;
		}
		$ret['type']     = $type;
		$ret['basename'] = basename( $filename );

		return $ret;
	}

	/**
	 * Processes mailing queue.
	 */
	public function processQueue() {
		$mails = $this->loadDataFromFiles( $this->mlgrp_id, false, [ 'errored', 'waiting' ] );
		foreach ( $mails as $file => $data ) {
			if ( 'errored' == $data['type'] ) {
				$retries = isset( $data['retries_count'] ) ? $data['retries_count'] : 0;
				if ( $retries > 16 ) {
					$data['retries_count'] = 0;
					continue;
				}
				$time = isset( $data['time'] ) ? $data['time'] : 0;
				if ( amapress_time() < $time + $retries * 2 * 60 ) {
					continue;
				}
			}
			try {
				self::sendMail( $this->mlgrp_id, $data );
			} catch ( Exception $ex ) {
				@error_log( $ex->getMessage() );
			} finally {
				@unlink( $file );
			}
		}

		exit;
	}

	/**
	 * (Really) send mails (if $_GET['smqProcessQueue'] is set).
	 *
	 * @param array $data mail data
	 *
	 * @return array Success
	 */
	public static function sendMail( $mlgrp_id, $data, $store_errors = true ) {
		if ( ! empty( $data['attachments'] ) ) {
			$data['attachments'] = array_filter( $data['attachments'],
				function ( $v ) {
					if ( is_array( $v ) ) {
						return isset( $v['file'] ) && file_exists( $v['file'] );
					} else {
						return ! empty( $v ) && file_exists( $v );
					}
				}
			);
		}
		require_once 'AmapressSMTPMailingQueueOriginal.php';
		if ( ! isset( $data['to'] ) ) {
			$data['to'] = '';
		}
		if ( ! isset( $data['subject'] ) ) {
			$data['subject'] = '';
		}
		if ( ! isset( $data['message'] ) ) {
			$data['message'] = '';
		}
		if ( ! isset( $data['headers'] ) ) {
			$data['headers'] = [];
		}
		if ( ! isset( $data['attachments'] ) ) {
			$data['attachments'] = [];
		}
		$errors = AmapressSMTPMailingQueueOriginal::wp_mail( $data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'] );
		if ( ! empty( $errors ) ) {
			@error_log( __( 'Email send Error : ', 'amapress' ) . implode( ' ; ', $errors ) );
			if ( $store_errors ) {
				self::storeMail( $mlgrp_id, 'errored', $data['to'],
					$data['subject'], $data['message'], $data['headers'], $data['attachments'],
					null, $errors,
					isset( $data['retries_count'] ) ? intval( $data['retries_count'] ) + 1 : 1,
					isset( $data['ctime'] ) ? $data['ctime'] : 0 );
			}
		} else {
			self::storeMail( $mlgrp_id, 'logged', $data['to'],
				$data['subject'], $data['message'], $data['headers'], $data['attachments'],
				null, null, 0,
				isset( $data['ctime'] ) ? $data['ctime'] : 0 );
		}

		return $errors;
	}

	public static function deleteMessageByGroup( $mlgrp_id, $ml_grp_msg_id, $type ) {
		foreach ( self::loadDataFromFiles( $mlgrp_id, true, [ $type ] ) as $message ) {

			if ( isset( $message['message'] ) && is_array( $message['message'] ) && isset( $message['message']['ml_grp_msg_id'] ) ) {
				if ( $ml_grp_msg_id == $message['message']['ml_grp_msg_id'] ) {
					self::deleteFile( $mlgrp_id, $type, $message['basename'] );
				}
			}
		}
	}

	public static function deleteFile( $mlgrp_id, $type, $msg_file ) {
		$file = self::getUploadDir( $mlgrp_id, $type ) . $msg_file;
		@unlink( $file );
	}

	public static function retrySendMessage( $mlgrp_id, $msg_file ) {
		$file            = self::getUploadDir( $mlgrp_id, 'errored' ) . $msg_file;
		$msg             = json_decode( file_get_contents( $file ), true );
		$msg['type']     = 'errored';
		$msg['basename'] = basename( $file );
		$errors          = self::sendMail( $mlgrp_id, $msg );
		@unlink( $file );

		return empty( $errors );
	}

	public static function deleteAllErroredMessages( $mlgrp_id ) {
		$errorred_dir = self::getUploadDir( $mlgrp_id, 'errored' );

		foreach ( glob( $errorred_dir . '*.json' ) as $filename ) {
			//phpcs:ignore
			@unlink( $filename );
		}
	}

	public static function retrySendAllErroredMessages( $mlgrp_id ) {
		$waiting_dir  = self::getUploadDir( $mlgrp_id, 'waiting' );
		$errorred_dir = self::getUploadDir( $mlgrp_id, 'errored' );

		foreach ( glob( $errorred_dir . '*.json' ) as $filename ) {
			//phpcs:ignore
			@rename( $filename, $waiting_dir . pathinfo( $filename, PATHINFO_BASENAME ) );
		}
	}

	/**
	 * Sets WordPress phpmailer to SMTP and sets all options.
	 *
	 * @param \PHPMailer $phpmailer
	 */
	public function initMailer( $phpmailer ) {
		//fix FROM and Return Path
		$phpmailer->Sender = $phpmailer->From;

		$host = Amapress::getOption( 'mail_queue_smtp_host' );
		if ( empty( $host ) ) {
			return;
		}

		// Set mailer to SMTP
		$phpmailer->isSMTP();

//		$phpmailer->SMTPDebug = 1;

		// Set sender info
		$phpmailer->From     = Amapress::getOption( 'mail_queue_from_email' );
		$phpmailer->FromName = Amapress::getOption( 'mail_queue_from_name' );
		//fix FROM and Return Path
		$phpmailer->Sender = $phpmailer->From;

		// Set encryption type
		$enc  = Amapress::getOption( 'mail_queue_encryption' );
		$port = Amapress::getOption( 'mail_queue_smtp_port' );
		if ( 465 == $port ) {
			$enc = 'ssl';
		} elseif ( 587 == $port ) {
			$enc = 'tls';
		}

		// Set host
		$phpmailer->SMTPSecure = $enc;
		$phpmailer->Host       = $host;
		$phpmailer->Port       = $port ? $port : 25;

		// Timeout
		$timeout            = Amapress::getOption( 'mail_queue_smtp_timeout' );
		$phpmailer->Timeout = $timeout ? $timeout : 30;

		// Set authentication data
		if ( Amapress::getOption( 'mail_queue_smtp_use_authentication' ) ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = Amapress::getOption( 'mail_queue_smtp_auth_username' );
			$phpmailer->Password = Amapress::getOption( 'mail_queue_smtp_auth_password' );
		}
	}
}