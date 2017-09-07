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

		add_action( 'amps_smq_start_queue', [ $this, 'processQueue' ] );

		// Filter
		add_filter( 'cron_schedules', [ $this, 'addWpCronInterval' ] );

		if ( ! wp_next_scheduled( 'amps_smq_start_queue' ) ) {
			$this->refreshWpCron();
		}

		add_action( 'tf_set_value_amapress_mail_queue_interval', [ $this, 'refreshWpCron' ] );
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
		$interval              = ! empty( $mail_queue_interval ) ? intval( $mail_queue_interval ) : 60;
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
		//TODO handle wp_mail hooks, ie content type at this time and not at time of sending out of queue
//		$advancedOptions = get_option('smtp_mailing_queue_advanced');
//		$minRecipients = isset($advancedOptions['min_recipients']) ? $advancedOptions['min_recipients'] : 1;
//
//		if(is_array($to))
//			$to = implode(',', $to);
//
//		if(count(explode(',', $to)) >= $minRecipients)
		$use = Amapress::getOption( 'mail_queue_use_queue' );
		if ( empty( $use ) || ! $use ) {
			$time   = amapress_time();
			$errors = self::sendMail( compact( 'to', 'subject', 'message', 'headers', 'attachments', 'time' ) );

			return empty( $errors );
		} else {
			return self::storeMail( $to, $subject, $message, $headers, $attachments );
		}
//		else {
//		}
	}

	/**
	 * Writes mail data to json file.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param array|string $headers
	 * @param array $attachments
	 * @param string $time
	 *
	 * @return bool
	 */
	public static function storeMail( $to, $subject, $message, $headers = '', $attachments = array(), $time = null, $errors = null, $subdir = null ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';

		$time = $time ?: amapress_time();
		$data = compact( 'to', 'subject', 'message', 'headers', 'attachments', 'time', 'errors' );

		$validEmails   = [];
		$invalidEmails = [];
		foreach ( explode( ',', $to ) as $recipient ) {
			if ( PHPMailer::validateAddress( $recipient ) ) {
				$validEmails[] = $recipient;
			} else {
				$invalidEmails[] = $recipient;
			}
		}

		$fileName = self::getUploadDir( ! empty( $subdir ) ? $subdir : ! empty( $errors ) ) . microtime( true ) . '.json';
		// @todo: not happy with doing the same thing 2x. Should write that to a separate method
		if ( count( $validEmails ) ) {
			$data['to'] = implode( ',', $validEmails );
			$handle     = @fopen( $fileName, "w" );
			if ( ! $handle ) {
				return false;
			}
			fwrite( $handle, json_encode( $data ) );
			fclose( $handle );
		}
		if ( count( $invalidEmails ) ) {
			$data['to'] = implode( ',', $invalidEmails );
			$handle     = @fopen( $fileName, "w" );
			if ( ! $handle ) {
				return false;
			}
			fwrite( $handle, json_encode( $data ) );
			fclose( $handle );
		}

		return true;
	}

	/**
	 * Creates upload dir if it not existing.
	 * Adds htaccess protection to upload dir.
	 *
	 * @param bool $invalid
	 *
	 * @return string upload dir
	 */
	public static function getUploadDir( $invalid = false ) {
		$subfolder = is_string( $invalid ) ? $invalid : ( $invalid ? 'invalid/' : '' );
		$dir       = wp_upload_dir()['basedir'] . '/amapress-smtp-mailing-queue/';
		$created   = wp_mkdir_p( $dir );
		if ( $created ) {
			$handle = @fopen( $dir . '.htaccess', "w" );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
		}

		if ( $invalid ) {
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
	public static function loadDataFromFiles( $ignoreLimit = false, $invalid = false ) {
		$queue_limit = Amapress::getOption( 'mail_queue_limit' );
		$emails      = [];
		$i           = 0;

		if ( 'both' === $invalid || false === $invalid ) {
			foreach ( glob( self::getUploadDir( false ) . '*.json' ) as $filename ) {
				$emails[ $filename ] = json_decode( file_get_contents( $filename ), true );
				$i ++;
				if ( ! $ignoreLimit && ! empty( $queue_limit ) && $i >= $queue_limit ) {
					break;
				}
			}
		}
		if ( 'both' === $invalid || true === $invalid ) {
			foreach ( glob( self::getUploadDir( true ) . '*.json' ) as $filename ) {
				$emails[ $filename ] = json_decode( file_get_contents( $filename ), true );
				$i ++;
				if ( ! $ignoreLimit && ! empty( $queue_limit ) && $i >= $queue_limit ) {
					break;
				}
			}
		}

		return $emails;
	}

	/**
	 * Processes mailing queue.
	 */
	public function processQueue() {
		$mails = $this->loadDataFromFiles( false, 'both' );
		foreach ( $mails as $file => $data ) {
			$this->sendMail( $data );
//			if(!empty($errors)) {
//				self::storeMail($data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'], null, $errors);
//            }
			$this->deleteFile( $file );
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
	public function sendMail( $data ) {
		require_once( 'AmapressSMTPMailingQueueOriginal.php' );
		$errors = AmapressSMTPMailingQueueOriginal::wp_mail( $data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'] );
		if ( ! empty( $errors ) ) {
			self::storeMail( $data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'], null, $errors );
//			self::storeMail($data['to'], $data['subject'], $data['message'], $data['headers'], $data['attachments'], null, $errors, 'error-logs');
		}

		return $errors;
	}

	/**
	 * Deletes file from uploads folder
	 *
	 * @param string $file Absolute path to file
	 */
	public function deleteFile( $file ) {
		unlink( $file );
	}

	/**
	 * Sets WordPress phpmailer to SMTP and sets all options.
	 *
	 * @param \PHPMailer $phpmailer
	 */
	public function initMailer( $phpmailer ) {
//		$options = get_option('smtp_mailing_queue_options');

//		if(!$options)
//			return;

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