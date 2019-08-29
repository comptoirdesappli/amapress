<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressSMTPMailingQueue {
	public function __construct() {
		$this->init();
	}

	/**
	 * Adds hooks, actions and filters for plugin.
	 */
	protected function init() {
		// Actions
		add_action( 'phpmailer_init', [ $this, 'initMailer' ] );

//		if(isset($_GET['smqProcessQueue'])) {
//			add_action('init', function() {
//				$this->processQueue();
//			});
//		}

		add_action( 'init', function () {
			load_plugin_textdomain( 'smtp-mailing-queue', false, 'smtp-mailing-queue/languages/' );
		} );

		if ( ! defined( 'FREE_PAGES_PERSO' ) ) {
			add_action( 'amps_smq_start_queue', [ $this, 'processQueue' ] );

			// Filter
			add_filter( 'cron_schedules', [ $this, 'addWpCronInterval' ] );

			if ( ! wp_next_scheduled( 'amps_smq_start_queue' ) ) {
				$this->refreshWpCron();
			}

			if ( ! wp_next_scheduled( 'amps_smq_clean_log' ) ) {
				wp_schedule_event( time(), 'daily', 'amps_smq_clean_log' );
			}
			add_action( 'amps_smq_clean_log', [ $this, 'cleanLogs' ] );

			add_action( 'tf_set_value_amapress_mail_queue_interval', [ $this, 'refreshWpCron' ] );
		}
//		add_action('tf_set_value_amapress_mail_queue_limit', [$this, 'refreshWpCron']);
//        $this->refreshWpCron();
	}

	/**
	 * (Re)sets wp_cron, e.g. on activation and interval update.
	 */
	public function refreshWpCron() {
		if ( wp_next_scheduled( 'amps_smq_start_queue' ) ) {
			wp_clear_scheduled_hook( 'amps_smq_start_queue' );
		}
		wp_schedule_event( time(), 'amps_smq', 'amps_smq_start_queue' );
	}

	/**
	 * Adds custom interval based on interval settings to wp_cron.
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function addWpCronInterval( $schedules ) {
//		$interval = get_option('smtp_mailing_queue_advanced')['wpcron_interval'];
		$mail_queue_interval   = Amapress::getOption( 'mail_queue_interval' );
		$interval              = ! empty( $mail_queue_interval ) ? intval( $mail_queue_interval ) : 30;
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
	 * @param string $message
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
				return false !== stripos( $h, 'Content-Type' );
			} );
		if ( empty( $ct ) && 'text/html' == apply_filters( 'wp_mail_content_type', 'text/plain' ) ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		$use = Amapress::getOption( 'mail_queue_use_queue' );
		if ( defined( 'FREE_PAGES_PERSO' ) ) {
			$use = false;
		}
		if ( empty( $use ) || ! $use || apply_filters( 'amapress_mail_queue_bypass', false ) ) {
			$retries = apply_filters( 'amapress_mail_queue_retries', 1 );
			do {
				$time    = amapress_time();
				$errors  = self::sendMail( compact( 'to', 'subject', 'message', 'headers', 'attachments', 'time' ) );
				$retries -= 1;
			} while ( $retries > 0 && ! empty( $errors ) );

			return empty( $errors );
		} else {
			return self::storeMail( 'waiting', $to, $subject, $message, $headers, $attachments );
		}
//		else {
//		}
	}

	public function cleanLogs() {
		$mail_queue_log_clean_days = Amapress::getOption( 'mail_queue_log_clean_days' );
		if ( $mail_queue_log_clean_days < 0 ) {
			return;
		}
		$clean_date = Amapress::add_days( Amapress::start_of_day( time() ), - $mail_queue_log_clean_days );
		foreach (
			AmapressSMTPMailingQueue::loadDataFromFiles( true, 'logged' )
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
	public static function storeMail( $type, $to, $subject, $message, $headers = '', $attachments = array(), $time = null, $errors = null, $retries_count = 0 ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';

		$time = $time ?: amapress_time();
		$data = compact( 'to', 'subject', 'message', 'headers', 'attachments', 'time', 'errors', 'retries_count' );

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
			if ( PHPMailer::validateAddress( $email ) ) {
				$validEmails[] = $recipient;
			} else {
				$invalidEmails[] = $recipient;
			}
		}

		$fileName   = self::getUploadDir( $type ) . microtime( true ) . '.json';
		$data['to'] = implode( ',', $validEmails );
		if ( ! empty( $invalidEmails ) ) {
			$data['invalid_to'] = implode( ',', $invalidEmails );
		}
		$handle = @fopen( $fileName, "w" );
		if ( ! $handle ) {
			return false;
		}
		fwrite( $handle, json_encode( $data ) );
		fclose( $handle );

		return true;
	}

	/**
	 * Creates upload dir if it not existing.
	 * Adds htaccess protection to upload dir.
	 *
	 * @param string $type
	 *
	 * @return string upload dir
	 */
	public static function getUploadDir( $type = 'waiting' ) {
		$subfolder = '';
		switch ( $type ) {
//			case 'waiting':
//				break;
			case 'errored':
				$subfolder = 'invalid/';
				break;
			case 'logged':
				$subfolder = 'logs/';
				break;
		}
		$dir     = wp_upload_dir()['basedir'] . '/amapress-smtp-mailing-queue/';
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
	public static function loadDataFromFiles( $ignoreLimit = false, $types = [ 'waiting' ] ) {
		$queue_limit = Amapress::getOption( 'mail_queue_limit' );
		$emails      = [];
		$i           = 0;
		if ( ! is_array( $types ) ) {
			$types = [ $types ];
		}

		foreach ( $types as $type ) {
			if ( 'waiting' == $type || 'errored' == $type || 'logged' == $type ) {
				foreach ( glob( self::getUploadDir( $type ) . '*.json' ) as $filename ) {
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
//		if ( 'both' === $invalid || true === $invalid ) {
//			foreach ( glob( self::getUploadDir( true ) . '*.json' ) as $filename ) {
//				$emails[ $filename ] = json_decode( file_get_contents( $filename ), true );
//				$i ++;
//				if ( ! $ignoreLimit && ! empty( $queue_limit ) && $i >= $queue_limit ) {
//					break;
//				}
//			}
//		}

		return $emails;
	}

	/**
	 * Processes mailing queue.
	 */
	public function processQueue() {
		$mails = $this->loadDataFromFiles( false, [ 'errored', 'waiting' ] );
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
			self::sendMail( $data );
			@unlink( $file );
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
	public static function sendMail( $data ) {
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
		require_once( 'AmapressSMTPMailingQueueOriginal.php' );
		$errors = AmapressSMTPMailingQueueOriginal::wp_mail( $data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'] );
		if ( ! empty( $errors ) ) {
			@error_log( 'Mail send Error : ' . implode( ' ; ', $errors ) );
			self::storeMail( 'errored', $data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'], null, $errors, isset( $data['retries_count'] ) ? intval( $data['retries_count'] ) + 1 : 1 );
		} else {
			self::storeMail( 'logged', $data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'] );
		}

		return $errors;
	}

	public static function deleteFile( $type, $msg_file ) {
		$file = self::getUploadDir( $type ) . $msg_file;
		@unlink( $file );
	}

	public static function retrySendMessage( $msg_file ) {
		$file            = self::getUploadDir( 'errored' ) . $msg_file;
		$msg             = json_decode( file_get_contents( $file ), true );
		$msg['type']     = 'errored';
		$msg['basename'] = basename( $file );
		$errors          = self::sendMail( $msg );
		@unlink( $file );

		return empty( $errors );
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
		$phpmailer->SMTPSecure = Amapress::getOption( 'mail_queue_encryption' );

		$port = Amapress::getOption( 'mail_queue_smtp_port' );
		// Set host
		$phpmailer->Host = $host;
		$phpmailer->Port = $port ? $port : 25;

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