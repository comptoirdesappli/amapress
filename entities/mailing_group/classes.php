<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressMailingGroup extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_mlgrp';
	const POST_TYPE = 'mailing_group';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressMailingGroup
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressMailingGroup' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressMailingGroup( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function logError( $message ) {
		error_log( $message );
	}

	public function getName() {
		return $this->getCustom( 'amapress_mailing_group_name' );
	}

	public function getSimpleName() {
		return preg_replace( '/@.+/', '', $this->getName() );
	}

	public function getListId() {
		return str_replace( '@', '.', $this->getName() );
	}

	public function getDescription() {
		return $this->getCustom( 'amapress_mailing_group_desc' );
	}

	public function getAdminMembersLink() {
		return admin_url( 'users.php?amapress_mlgrp_id=' . $this->ID );
	}

	public function getMembersQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailing_group_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailing_group_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		$users = array_map( 'intval', Amapress::get_array( Amapress::getOption( 'mailing_other_users' ) ) );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		if ( $this->getCustom( 'amapress_mailing_group_inc_free', false ) ) {
			$ret = array_merge( $ret, $this->getFreeMembersQueries() );
		}
		if ( $this->getCustom( 'amapress_mailing_group_inc_moderators', false ) ) {
			$ret = array_merge( $ret, $this->getModeratorsQueries() );
		}

		return $ret;
	}

	public function getExcludeMembersQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailing_group_excl_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailing_group_excl_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		return $ret;
	}

	public function getMembersIds() {
		$ids = [];
		foreach ( $this->getMembersQueries() as $user_query ) {
			if ( is_array( $user_query ) ) {
				$user_query['fields'] = 'id';
			} else {
				$user_query .= '&fields=id';
			}
			foreach ( get_users( $user_query ) as $user_id ) {
				$ids[] = intval( $user_id );
			}
		}

		$excl_user_ids = [];
		foreach ( $this->getExcludeMembersQueries() as $user_query ) {
			if ( is_array( $user_query ) ) {
				$user_query['fields'] = 'id';
			} else {
				$user_query .= '&fields=id';
			}
			foreach ( get_users( $user_query ) as $user_id ) {
				$excl_user_ids[] = intval( $user_id );
			}
		}
		$ids = array_diff( $ids, $excl_user_ids );

		return array_unique( $ids );
	}

	public function getModeratorsQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailing_group_moderators_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailing_group_moderators_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		return $ret;
	}

	public function getFreeMembersQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailing_group_free_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailing_group_free_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		return $ret;
	}

	public function getReplyTo() {
		return $this->getCustom( 'amapress_mailing_group_reply_to', 'sender' );
	}

	public function getSubjectPrefix() {
		$subject_prefix = $this->getCustom( 'amapress_mailing_group_subject_pref' );
		if ( ! empty( $subject_prefix ) ) {
			return $subject_prefix;
		}

		return '[' . $this->getSimpleName() . ']';
	}

	public function getModeration() {
		return $this->getCustom( 'amapress_mailing_group_moderation' );
	}

	public function getHost() {
		return $this->getCustom( 'amapress_mailing_group_host' );
	}

	public function getPort() {
		return $this->getCustomAsInt( 'amapress_mailing_group_port', 993 );
	}

	public function getUsername() {
		return $this->getCustom( 'amapress_mailing_group_username' );
	}

	public function getPassword() {
		return $this->getCustom( 'amapress_mailing_group_password' );
	}

	public function getProtocol() {
		return $this->getCustom( 'amapress_mailing_group_protocol', 'imap' );
	}

	public function getEncryption() {
		return $this->getCustom( 'amapress_mailing_group_encryption', 'ssl' );
	}

	public function isSelfSignedSSL() {
		return $this->getCustomAsInt( 'amapress_mailing_group_self_signed' );
	}

	public function getSmtpHost() {
		return $this->getCustom( 'amapress_mailing_group_smtp_host' );
	}

	public function getSmtpMaxMailsPerHour() {
		return $this->getCustom( 'amapress_mailing_group_smtp_max_per_hour' );
	}

	public function getSmtpPort() {
		return $this->getCustomAsInt( 'amapress_mailing_group_smtp_port', 25 );
	}

	public function getSmtpEncryption() {
		$enc  = $this->getCustom( 'amapress_mailing_group_smtp_encryption', '' );
		$port = $this->getSmtpPort();
		if ( 465 == $port ) {
			$enc = 'ssl';
		} elseif ( 587 == $port ) {
			$enc = 'tls';
		}

		return $enc;
	}

	public function UseSmtpAuth() {
		return $this->getCustomAsInt( 'amapress_mailing_group_smtp_use_auth', 0 );
	}

	public function getSmtpTimeout() {
		return $this->getCustomAsInt( 'amapress_mailing_group_smtp_timeout', 30 );
	}

	public function getSmtpUserName() {
		return $this->getCustom( 'amapress_mailing_group_smtp_auth_username' );
	}

	public function getSmtpPassword() {
		return $this->getCustom( 'amapress_mailing_group_smtp_auth_password' );
	}

	public function getIncludeAdhesionRequest() {
		return $this->getCustom( 'amapress_mailing_group_inc_adh_requests' );
	}

	public function distributeMail( $msg_id ) {
		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			wp_die( sprintf( __( 'L\'email %s n\'existe pas/plus.', 'amapress' ), $msg_id ) );
		}

		if ( ! $this->isCurrentUserModerator() ) {
			wp_die( sprintf( __( 'Vous n\'êtes pas modérateur de la liste %s', 'amapress' ), $this->getName() ) );
		}

		$msg['moderator'] = amapress_current_user_id();
		$msg['mod_date']  = amapress_time();
		if ( ! $this->sendMailFromMsgId( 'waiting', $msg_id ) ) {
			wp_die( sprintf( __( 'L\'email %s n\'a pas pu être envoyé.', 'amapress' ), $msg_id ) );
		}
		$this->sendMailByParamName( 'mailinggroup-distrib-sender', $msg, $msg['from'] );
		$this->storeMailData( 'accepted', $msg );

		$this->deleteMessage( $msg_id, 'waiting', false );
	}

	public function rejectMailQuiet( $msg_id ) {
		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			wp_die( sprintf( __( 'L\'email %s n\'existe pas/plus.', 'amapress' ), $msg_id ) );
		}

		if ( ! $this->isCurrentUserModerator() ) {
			wp_die( sprintf( __( 'Vous n\'êtes pas modérateur de la liste %s', 'amapress' ), $this->getName() ) );
		}

		$msg['moderator'] = amapress_current_user_id();
		$msg['mod_date']  = amapress_time();
		$this->storeMailData( 'rejected', $msg );

		$this->deleteMessage( $msg_id, 'waiting', false );
	}


	public function rejectMail( $msg_id ) {
		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			wp_die( sprintf( __( 'L\'email %s n\'existe pas/plus.', 'amapress' ), $msg_id ) );
		}

		if ( ! $this->isCurrentUserModerator() ) {
			wp_die( sprintf( __( 'Vous n\'êtes pas modérateur de la liste %s', 'amapress' ), $this->getName() ) );
		}

		$msg['moderator'] = amapress_current_user_id();
		$msg['mod_date']  = amapress_time();
		$this->storeMailData( 'rejected', $msg );
		$this->sendMailByParamName( 'mailinggroup-reject-sender', $msg, $msg['from'] );
		$this->deleteMessage( $msg_id, 'waiting', false );
	}

	private function isCurrentUserModerator() {
		foreach ( AmapressUser::getBy( amapress_current_user_id() )->getAllEmails() as $mail ) {
			if ( $this->isModerator( $mail ) ) {
				return true;
			}
		}

		return current_user_can( 'manage_options' );
	}

	/** @return array */
	public function getMailWaitingModeration() {
		return $this->loadDataFromFiles( 'waiting' );
	}

	/** @return array */
	public function getMailArchives() {
		$accepted = $this->loadDataFromFiles( 'accepted' );
		$rejected = $this->loadDataFromFiles( 'rejected' );
		foreach ( $accepted as $a ) {
			$a['type'] = 'accepted';
		}
		foreach ( $rejected as $a ) {
			$a['type'] = 'rejected';
		}
		$res = array_merge( $accepted, $rejected );
		usort( $res, function ( $a, $b ) {
			$date_a = isset( $a['date'] ) ? $a['date'] : 0;
			$date_b = isset( $b['date'] ) ? $b['date'] : 0;
			if ( $date_a == $date_b ) {
				return 0;
			}

			return $date_a < $date_b ? - 1 : 1;
		} );

		return $res;
	}

	public function getMailWaitingModerationCount() {
		return count( glob( $this->getUploadDir( 'waiting' ) . '*.json' ) );
	}

	public function getAdhesionRequestEmailsIfActive() {
		$user_emails = [];
		if ( $this->getIncludeAdhesionRequest() ) {
			foreach ( AmapressAdhesionRequest::getAllToConfirm() as $adh_request ) {
				if ( ! empty( $adh_request->getEmail() ) ) {
					$user_emails[] = $adh_request->getEmail();
				}
			}
		}

		return $user_emails;
	}

	public function getMembersCount() {
		return count( $this->getMembersEmails() );
	}

	public function shouldUseSmtp() {
		return ( $this->getMembersCount() > 25 );
	}

	public function testParams() {
		try {
			$mailbox = $this->getMailbox();
			$mailbox->checkMailbox();
			$mailbox->disconnect();

			return true;
		} catch ( Exception $ex ) {
			$proto = strtoupper( $this->getProtocol() );

			return sprintf( __( 'Erreur de connexion %s: %s', 'amapress' ), $proto, $ex->getMessage() );
		}
	}

	public function getKeepSender() {
		return $this->getCustom( 'amapress_mailing_group_keep_sender', true );
	}

	private static $dmarc_cache = [];

	public static function hasRestrictiveDMARC( $email ) {
		preg_match( '/@(.+)$/', trim( $email ), $matches );
		if ( $matches ) {
			$domain = $matches[1];
			if ( isset( self::$dmarc_cache[ $domain ] ) ) {
				return self::$dmarc_cache[ $domain ];
			}
			$txts = dns_get_record( "_dmarc.$domain", DNS_TXT );
			foreach ( $txts as $txt ) {
				if ( preg_match( "/^\s*v\s*=\s*DMARC1\s*;/i", $txt['txt'] ) ) {
					$res                          = preg_match( "/;\s*p\s*=\s*quarantine\s*;/i", $txt['txt'] )
					                                || preg_match( "/;\s*p\s*=\s*reject\s*;/i", $txt['txt'] );
					self::$dmarc_cache[ $domain ] = $res;

					return self::$dmarc_cache[ $domain ];
				}
			}
		}

		return false;
	}

	public function fetchMails() {
		if ( ! extension_loaded( 'imap' ) ) {
			return false;
		}

		try {
			$mailbox = $this->getMailbox();
		} catch ( Exception $ex ) {
			//error_log( __('Erreur IMAP/POP3 (', 'amapress') . $this->getName() . '): ' . $ex->getMessage() );

			return false;
		}

		$unk_action = Amapress::getOption( 'mailinggroup-unk-action', 'moderate' );
		$bl_regex   = Amapress::getOption( 'mailinggroup-bl-regex', '' );

		try {
			// Get all emails (messages)
			// PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php
			foreach ( $mailbox->searchMailbox( 'ALL' ) as $mail_id ) {
				$msg_id         = uniqid( 'amps' );
				$attachment_dir = $this->getUploadDir( "waiting/$msg_id" );
				$mailbox->setAttachmentsDir( $attachment_dir );
				$mail     = $mailbox->getMail( $mail_id );
				$eml_file = $attachment_dir . "/$msg_id.eml";
//				file_put_contents($eml_file, $this->getRawMail( $mailbox, $mail_id ) );
				$mailbox->saveMail( $mail_id, $eml_file );
//				$to           = array_filter( $mail->to, function ( $addr ) {
//					return false === strpos( $addr, $this->getName() );
//				} );
				$to      = $this->getName(); //implode( ', ', $to );
				$content = ! empty( $mail->textHtml ) ? $mail->textHtml : $mail->textPlain;
				if ( empty( $content ) ) {
					$content = __( 'Mail vide', 'amapress' );
				}
				$raw_headers  = $mailbox->getMailHeader( $mail_id );
				$is_from_list = false;
				if ( $raw_headers ) {
					$raw_headers = $raw_headers->headersRaw;
					if ( is_string( $raw_headers ) ) {
						$raw_headers = explode( "\n", str_replace( "\r\n", "\n", $raw_headers ) );
					}
					$headers      = array_filter( $raw_headers, function ( $header ) {
						return preg_match( '/^(?:Importance|Priority):/', $header );
					} );
					$is_from_list = ! empty(
					array_filter( $raw_headers, function ( $header ) {
						return preg_match( '/^(?:List-Id):/', $header ) && false !== strpos( $header, $this->getListId() );
					} )
					);
				} else {
					$headers = [];
				}

				if ( ! $is_from_list ) {
					$cc           = ''; //implode( ', ', $mail->cc );
					$from         = ! empty( $mail->fromName ) ? "\"{$mail->fromName}\" <{$mail->fromAddress}>" : $mail->fromAddress;
					$cleaned_from = '';
					if ( ! $this->getKeepSender() || self::hasRestrictiveDMARC( $mail->fromAddress ) ) {
						$headers[]    = "X-Original-From: $from";
						$cleaned_from = ! empty( $mail->fromName )
							? "\"{$mail->fromName}\" <{$this->getName()}>"
							: "\"{$this->getName()}\" <{$this->getName()}>";
					}
					$date    = $mail->date;
					$subject = $mail->subject;
					$body    = [
						'text'        => $mail->textPlain,
						'html'        => $mail->textHtml,
						'attachments' => [],
					];
					if ( empty( $body['text'] ) && empty( $body['html'] ) ) {
						$body['text'] = __( 'Mail vide', 'amapress' );
						$body['html'] = __( 'Mail vide', 'amapress' );
					}

					foreach ( $mail->getAttachments() as $attachment ) {
						$body['attachments'][] = [
							'id'     => $attachment->contentId,
							'name'   => $attachment->name,
							'inline' => ( 0 == strcasecmp( $attachment->disposition, 'inline' ) ),
							'file'   => $attachment->filePath
						];
					}

					$is_site_member = false !== get_user_by( 'email', $mail->fromAddress );
					if ( ! $is_site_member ) {
						if ( preg_match( '/^(mailer-daemon|postmaster|hostmaster|abuse|junk|sympa|listserv|majordomo|smartlist|mailman)@/i', $mail->fromAddress ) ) {
							global $phpmailer;
							require_once AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueueOriginal.php';
							AmapressSMTPMailingQueueOriginal::EnsurePHPMailerInit();
							$undelivered = '';
							try {
								require_once AMAPRESS__PLUGIN_DIR . 'modules/bounceparser/BounceStatus.php';
								require_once AMAPRESS__PLUGIN_DIR . 'modules/bounceparser/BounceHandler.php';

								$bounce_handler      = new rambomst\PHPBounceHandler\BounceHandler();
								$parsed_bounce_email = $bounce_handler->parseEmail( file_get_contents( $eml_file ) );

								$undelivered = implode( ', ', array_map( function ( $recipient ) {
									$email      = $recipient['recipient'];
									$email_link = $email;
									$user       = get_user_by( 'email', $email );
									if ( $user ) {
										$email_link = Amapress::makeLink( admin_url( sprintf( 'user-edit.php?user_id=%d#email', $user->ID ) ) );
									}

									return sprintf( __( '%s (%s/%s)', 'amapress' ),
										$email_link,
										$recipient['action'],
										$recipient['message']
									);
								}, $parsed_bounce_email ) );
							} catch ( Exception $ex ) {
							}

							amapress_wp_mail( get_option( 'admin_email' ),
								sprintf( __( 'Message non remis à un ou plusieurs destinataires sur la liste %s', 'amapress' ), $this->getName() ),
								wpautop( sprintf( "Bonjour,\n\n Un message n'a pas pu être remis à un ou plusieurs destinataires pour la liste %s:\n------\nDestinataires: %s\n------\nSujet: %s\n%s\n------\n\n%s", $this->getName(), $undelivered, $subject, $phpmailer->html2text( $content ), get_bloginfo( 'name' ) ) ),
								'', [
									[
										'name'   => 'email.eml',
										'inline' => false,
										'file'   => $eml_file
									]
								] );
							$res = true;
						} else if ( 'moderate' == $unk_action && ( empty( $bl_regex ) || ! preg_match( "/$bl_regex/", $mail->fromAddress ) ) ) {
							$res = $this->saveMailForModeration( $msg_id, $date, $cleaned_from, $from, $to, $cc, $subject, $content, $body, $headers, $eml_file, true );
							if ( ! $res ) {
								$this->logError( __( 'Cannot save mail for moderation', 'amapress' ) );
							}
						} else {
							$res = true;
							$this->logError( __( 'Rejected mail from', 'amapress' ) . $from );
						}
					} else {
						if ( $this->isAllowedSender( $mail->fromAddress ) || $this->isAllowedSender( $mail->senderAddress ) ) {
							$this->storeMail( 'accepted', $msg_id, $date, $from, $to, $cc, $subject, $content, $body, $headers,
								[ 'date' => amapress_time(), 'eml_file' => $eml_file, 'clean_from' => $cleaned_from ] );

							$msg = $this->loadMessage( 'accepted', $msg_id );
							if ( ! $this->sendMailByParamName( 'mailinggroup-distrib-sender-auto', $msg, $msg['from'] ) ) {
								$this->logError( 'fetchMails - sendMailByParamName - auto-sender failed' );
							}
							$res = $this->sendMailFromMsgId( 'accepted', $msg_id );
							if ( ! $res ) {
								$this->logError( __( 'Cannot send mail to members', 'amapress' ) );
							}
						} else {
							$res = $this->saveMailForModeration( $msg_id, $date, $cleaned_from, $from, $to, $cc, $subject, $content, $body, $headers, $eml_file, false );
							if ( ! $res ) {
								$this->logError( __( 'Cannot save mail for moderation', 'amapress' ) );
							}
						}
					}
					if ( $res ) {
						$mailbox->deleteMail( $mail_id );
					}
				} else {
					$mailbox->deleteMail( $mail_id );
				}
			}
		} catch ( Exception $ex ) {
			$this->logError( sprintf( __( 'Erreur IMAP/POP3 (%s): %s', 'amapress' ), $this->getName(), $ex->getMessage() ) );

			return false;
		} finally {
			$mailbox->disconnect();
		}

		return true;
	}

	private function storeMailData( $type, $data ) {
		$fileName = $this->getUploadDir( $type ) . $data['id'] . '.json';
		$handle   = @fopen( $fileName, "w" );
		if ( ! $handle ) {
			return false;
		}
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
	}

	private function storeMail( $type, $msg_id, $date, $from, $to, $cc, $subject, $content, $raw_message, $headers = '', $other_meta = [] ) {
		$data       = compact( 'from', 'to', 'cc', 'subject', 'date', 'content', 'raw_message', 'headers' );
		$data['id'] = $msg_id ? $msg_id : uniqid( 'amps' );
		foreach ( $other_meta as $k => $v ) {
			$data[ $k ] = $v;
		}

		return $this->storeMailData( $type, $data );
	}

	private function getUploadDir( $type = 'waiting', $prepare = true ) {
		$dir     = wp_upload_dir()['basedir'] . "/amapress-mailingroups-{$this->getID()}/$type/";
		$created = wp_mkdir_p( $dir );
		if ( $created && $prepare ) {
			$handle = @fopen( $dir . '.htaccess', "w" );
			fwrite( $handle, 'DENY FROM ALL' );
			fclose( $handle );
			$handle = @fopen( $dir . 'index.php', "w" );
			fclose( $handle );
		}

		return $dir;
	}

	private function loadMessageFile( $filename ) {
		if ( ! file_exists( $filename ) ) {
			return null;
		}
		$data         = json_decode( file_get_contents( $filename ), true );
		$data['type'] = basename( dirname( $filename ) );

		return $data;
	}

	private static function delTree( $dir ) {
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			if ( is_dir( "$dir/$file" ) ) {
				self::delTree( "$dir/$file" );
			} else {
				unlink( "$dir/$file" );
			}
		}

		return rmdir( $dir );
	}

	public function loadMessage( $type, $msg_id ) {
		return $this->loadMessageFile( $this->getUploadDir( $type ) . $msg_id . '.json' );
	}

	public function deleteMessage( $msg_id, $type = null, $delete_attachments = true ) {
		foreach ( ! empty( $type ) ? [ $type ] : [ 'waiting', 'accepted', 'rejected' ] as $type ) {
			$dir = $this->getUploadDir( $type, false );
			if ( $delete_attachments ) {
				$waiting_attachment_dir = $this->getUploadDir( "waiting/$msg_id" );
				if ( file_exists( $waiting_attachment_dir ) ) {
					self::delTree( $waiting_attachment_dir );
				}
				$local_attachment_dir = $dir . "/$msg_id";
				if ( file_exists( $local_attachment_dir ) ) {
					self::delTree( $local_attachment_dir );
				}
			}
			$msg_json = $dir . "/$msg_id.json";
			if ( file_exists( $msg_json ) ) {
				@unlink( $msg_json );
			}
		}
	}

	public function loadDataFromFiles( $types = [ 'waiting' ] ) {
		$emails = [];
		if ( ! is_array( $types ) ) {
			$types = [ $types ];
		}

		foreach ( $types as $type ) {
//			if ( 'waiting' == $type || 'errored' == $type || 'logged' == $type ) {
			foreach ( glob( $this->getUploadDir( $type ) . '*.json' ) as $filename ) {
				$msg = $this->loadMessageFile( $filename );
				if ( empty( $msg['id'] ) ) {
					$msg['id'] = pathinfo( $filename )['filename'];
				}
				if ( empty( $msg['from'] ) ) {
					@unlink( $filename );
					continue;
				}
				$emails[ $filename ] = $msg;
			}
//			}
		}

		return $emails;
	}

	private function getEmailsFromQueries( $queries ) {
		global $wpdb;

		$key = 'amps_mlg_q' . md5( serialize( $queries ) );
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = Amapress_MailingList::normalizeEmailsArray(
				$wpdb->get_col( Amapress_MailingList::getSqlQuery( $queries, [] ) )
			);
		}

		return $res;
	}

	public function isAllowedSender( $senderAddress ) {
		$senderAddress = strtolower( $senderAddress );
		switch ( $this->getModeration() ) {
			case 'none':
				return true;
			case 'not_members':
				return $this->isFreeMember( $senderAddress )
				       || $this->isModerator( $senderAddress )
				       || $this->isMember( $senderAddress );
			case 'all':
				return $this->isFreeMember( $senderAddress )
				       || $this->isModerator( $senderAddress );
		}

		return false;
	}

	public function isMember( $senderAddress ) {
		return in_array( $senderAddress, $this->getMembersEmails() );
	}

	private function getMembersEmails() {
		$members_emails   = $this->getEmailsFromQueries( $this->getMembersQueries() );
		$members_emails   = array_merge( $members_emails, $this->getRawEmails() );
		$members_emails   = array_merge( $members_emails, $this->getAdhesionRequestEmailsIfActive() );
		$excl_user_emails = $this->getEmailsFromQueries( $this->getExcludeMembersQueries() );

		return array_unique( array_diff( $members_emails, $excl_user_emails ) );
	}

	public function isFreeMember( $senderAddress ) {
		return in_array( $senderAddress, $this->getEmailsFromQueries( $this->getFreeMembersQueries() ) );
	}

	public function isModerator( $senderAddress ) {
		return in_array( $senderAddress, $this->getEmailsFromQueries( $this->getModeratorsQueries() ) );
	}

	private function sendMailFromMsgId( $type, $msg_id ) {
		$msg = $this->loadMessage( $type, $msg_id );
		if ( ! $msg ) {
			return false;
		}

		return $this->sendMail( $msg['clean_from'], $msg['from'], $msg['to'], $msg['cc'], $msg['subject'], $msg['raw_message'], $msg['headers'] );
	}

	private function getRawEmails() {
		$raw_emails = $this->getCustom( 'amapress_mailing_group_raw_users' );
		if ( ! empty( $raw_emails ) ) {
			$raw_emails = preg_replace( '/\s+/', ',', $raw_emails );
			$raw_emails = explode( ',', $raw_emails );

			return array_filter( $raw_emails, function ( $e ) {
				return ! empty( $e );
			} );
		}

		return [];
	}

	public function isExternalSmtp() {
		return ! empty( $this->getSmtpHost() );
	}

	private function sendMail( $clean_from, $from, $to, $cc, $subject, $body, $headers ) {
		$is_ext_smtp = $this->isExternalSmtp();
		if ( ! $is_ext_smtp && ! empty( $cc ) ) {
			$headers[] = 'Cc: ' . $cc;
		}
		if ( empty( $clean_from ) ) {
			$clean_from = $from;
		}
		$members_emails = $this->getMembersEmails();

		if ( ! $is_ext_smtp ) {
			$headers[] = 'Bcc: ' . implode( ',', array_unique( $members_emails ) );
		}
		$headers   = array_filter( $headers, function ( $h ) {
			return 0 !== strpos( $h, 'From' );
		} );
		$headers[] = 'From: ' . $clean_from;
		$headers[] = "Sender: {$this->getName()}";

		$admin_email = get_option( 'admin_email' );
		$desc        = $this->getDescription();
		if ( empty( $desc ) ) {
			$desc = $this->getSimpleName();
		}
		$to = "\"{$desc}\" <{$this->getName()}>";

		$site_url  = get_bloginfo( 'url' );
		$headers[] = 'Return-Path: ' . $admin_email;
		$headers[] = 'Errors-To: ' . $admin_email;
		$headers[] = 'List-Id: <' . $this->getListId() . '>';
		$headers[] = 'List-Post: <mailto:' . $this->getName() . '>';
		$headers[] = 'List-Owner: <mailto:' . $admin_email . '>';
		$headers[] = 'List-Help: <' . $site_url . '>';
		$headers[] = 'List-Subscribe: <mailto:' . $admin_email . '>';
		$headers[] = 'List-Unsubscribe: <mailto:' . $admin_email . '>';
		$headers[] = 'List-Archive: <' . $site_url . '>';
		$headers[] = 'Archive-At: <' . $site_url . '>';
		$headers[] = 'Precedence: list';
		$headers[] = 'Precedence: bulk';
		$headers[] = 'X-No-Archive: yes';
		$headers[] = 'X-Loop: ' . $this->getName();
		switch ( $this->getReplyTo() ) {
			case 'sender':
				$reply_to  = $from;
				$headers[] = 'Reply-To: ' . $reply_to;
				break;
			case 'list':
				$headers[] = 'Reply-To: ' . $this->getName();
				break;
		}
//		$headers = array_filter( $headers, function ( $h ) {
//			return !preg_match('/^\s*(?:Date|Content-Type|Message-ID):/i', $h);
//		} );

		if ( ! is_array( $body ) ) {
			$body['html'] = $body;
			$body['text'] = '';
		}
		$body['ml_grp_id'] = $this->ID;

		if ( $is_ext_smtp ) {
			$body['ml_grp_msg_id'] = uniqid() . uniqid();

			$res = false;
			foreach ( $members_emails as $members_email ) {
				$local_headers = array_merge( $headers, [
					'Bcc: ' . $members_email
				] );
				$res           = $res | wp_mail( $to, $this->getSubjectPrefix() . ' ' . $subject, $body, $local_headers, $body['attachments'] );
			}
			if ( ! empty( $cc ) ) {
				$headers[] = 'Cc: ' . $cc;

				$res = $res | wp_mail( $to, $this->getSubjectPrefix() . ' ' . $subject, $body, $headers, $body['attachments'] );
			}

			return $res;
		} else {
			return wp_mail( $to, $this->getSubjectPrefix() . ' ' . $subject, $body, $headers, $body['attachments'] );
		}
	}

	public function resendModerationMail( $msg_id ) {
		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			$this->logError( 'resendModerationMail - loadMessage failed' );

			return false;
		}
		if ( ! $this->sendMailByParamName( 'mailinggroup-waiting-mods',
			$msg, $this->getModeratorsEmails(),
			[
				[
					'name'   => 'email.eml',
					'inline' => false,
					'file'   => $msg['eml_file']
				]
			] ) ) {
			$this->logError( 'resendModerationMail - sendMailByParamName - waiting-mods failed' );

			return false;
		}

		return true;
	}

	private function saveMailForModeration( $msg_id, $date, $clean_from, $from, $to, $cc, $subject, $content, $body, $headers, $eml_file, $is_unknown ) {
		if ( ! $this->storeMail( 'waiting', $msg_id, $date, $from, $to, $cc, $subject, $content, $body, $headers,
			[ 'date' => amapress_time(), 'eml_file' => $eml_file, 'clean_from' => $clean_from ] ) ) {
			$this->logError( 'saveMailForModeration - storeMail failed' );

			return false;
		}

		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			$this->logError( 'saveMailForModeration - loadMessage failed' );

			return false;
		}

		if ( Amapress::getOption( 'mailinggroup-send-confirm-unk', false ) || ! $is_unknown ) {
			if ( ! $this->sendMailByParamName( 'mailinggroup-waiting-sender', $msg, $msg['from'] ) ) {
				$this->logError( 'saveMailForModeration - sendMailByParamName - waiting-sender failed' );

				return false;
			}
		}
		if ( ! $this->sendMailByParamName( 'mailinggroup-waiting-mods',
			$msg, $this->getModeratorsEmails(),
			[
				[
					'name'   => 'email.eml',
					'inline' => false,
					'file'   => $msg['eml_file']
				]
			] ) ) {
			$this->logError( 'saveMailForModeration - sendMailByParamName - waiting-mods failed' );

			return false;
		}

		return true;
	}

	public function getModeratorsEmails() {
		return $this->getEmailsFromQueries( $this->getModeratorsQueries() );
	}

	private function sendMailByParamName( $param, $msg, $to, $attachments = [] ) {
		$subject = Amapress::getOption( "{$param}-mail-subject" );
		$content = Amapress::getOption( "{$param}-mail-content" );

		$subject = $this->replaceMailPlaceholders( $subject, $msg );
		$content = $this->replaceMailPlaceholders( $content, $msg );

		if ( is_array( $to ) ) {
			$to = implode( ',', $to );
		}

		return amapress_wp_mail( $to, $subject, $content, '', $attachments );
	}

	private function replaceMailPlaceholders( $content, $msg ) {
		$moderator_id = isset( $msg['moderator'] ) ? intval( $msg['moderator'] ) : amapress_current_user_id();
		$moderator    = AmapressUser::getBy( $moderator_id );
		if ( ! $moderator ) {
			$moderator = AmapressUser::getBy( amapress_current_user_id() );
		}

		$subject = ! empty( $msg['subject'] ) ? $msg['subject'] : '';
		global $phpmailer;
		require_once AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueueOriginal.php';
		AmapressSMTPMailingQueueOriginal::EnsurePHPMailerInit();


		$body    = $phpmailer->html2text( ! empty( $msg['content'] ) ? $msg['content'] : '' );
		$summary = wpautop( __( "\n------\nSujet: $subject\n$body\n------\n", 'amapress' ) );

		if ( empty( $msg['id'] ) ) {
			$msg['id'] = '';
		}

		$placeholders = [
			'liste_nom'              => $this->getName(),
			'nom_liste'              => $this->getName(),
			'moderated_by'           => $moderator ? Amapress::makeLink( 'mailto:' . $moderator->getEmail(), $moderator->getDisplayName() ) : '',
			'moderated_by_email'     => $moderator ? $moderator->getEmail() : '',
			'moderated_by_name'      => $moderator ? $moderator->getDisplayName() : '',
			'msg_subject'            => $subject,
			'msg_summary'            => $summary,
			'sender'                 => esc_html( ! empty( $msg['from'] ) ? $msg['from'] : '' ),
			'msg_waiting_link'       => Amapress::makeLink( admin_url( 'admin.php?page=mailinggroup_moderation&tab=mailgrp-moderate-tab-' . $this->ID ), __( 'Voir', 'amapress' ) ),
			'msg_reject_silent_link' => amapress_get_mailgroup_action_form( __( 'Rejetter sans prévenir', 'amapress' ), 'amapress_mailgroup_reject_quiet', $this->ID, $msg['id'] ),
			'msg_reject_notif_link'  => amapress_get_mailgroup_action_form( __( 'Rejetter', 'amapress' ), 'amapress_mailgroup_reject', $this->ID, $msg['id'] ),
			'msg_distrib_link'       => amapress_get_mailgroup_action_form( __( 'Distribuer', 'amapress' ), 'amapress_mailgroup_distribute', $this->ID, $msg['id'] ),
		];

		foreach ( $placeholders as $k => $v ) {
			$content = str_replace( "%%$k%%", ! empty( $v ) ? $v : '', $content );
		}

		return amapress_replace_mail_placeholders( $content, null, null );
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_recall = true ) {
		$additional_helps = array_merge(
			$additional_helps,
			[
				'liste_nom'              => __( 'Nom de la liste', 'amapress' ),
				'nom_liste'              => __( 'Nom de la liste', 'amapress' ),
				'moderated_by'           => __( 'Mailto du modérateur de l\'email', 'amapress' ),
				'moderated_by_email'     => __( 'Email du modérateur de l\'email', 'amapress' ),
				'moderated_by_name'      => __( 'Nom du modérateur de l\'email', 'amapress' ),
				'msg_subject'            => __( 'Objet de l\'email modéré', 'amapress' ),
				'msg_summary'            => __( 'Objet et contenu de l\'email à modérer', 'amapress' ),
				'sender'                 => __( 'Emetteur de l\'email', 'amapress' ),
				'msg_reject_silent_link' => __( 'Lien de rejet sans notification de l\'email', 'amapress' ),
				'msg_reject_notif_link'  => __( 'Lien de rejet avec notification de l\'email', 'amapress' ),
				'msg_distrib_link'       => __( 'Lien de distribution de l\'email', 'amapress' ),
				'msg_waiting_link'       => __( 'Lien vers les emails en attente de modération', 'amapress' ),
			]
		);

		return Amapress::getPlaceholdersHelpTable( 'mailinggroup-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), __( 'de l\'Email groupé', 'amapress' ),
			$additional_helps, $for_recall ? 'recall' : true );
	}

	public function getMailbox() {
		if ( empty( $this->getHost() )
		     || empty( $this->getPort() )
		     || empty( $this->getProtocol() ) ) {
			throw new Exception( 'Invalid configuration' );
		}

		$encryption = '';
		if ( 'none' != $this->getEncryption() ) {
			$encryption = '/' . $this->getEncryption();
		}
		if ( $this->isSelfSignedSSL() ) {
			$encryption .= '/novalidate-cert';
		} elseif ( defined( 'AMAPRESS_VALIDATE_SSL' ) && AMAPRESS_VALIDATE_SSL == false ) {
			$encryption .= '/novalidate-cert';
		}

		$mailbox = new PhpImap\Mailbox(
			'{' . $this->getHost() . ':' . $this->getPort() . '/' . $this->getProtocol() . $encryption . '}INBOX', // IMAP server and mailbox folder
			$this->getUsername(),
			$this->getPassword()
		);

		return $mailbox;
	}

	public static function getAllWaitingForModerationCount() {
		$count = 0;
		foreach ( AmapressMailingGroup::getAll() as $ml ) {
			$count += $ml->getMailWaitingModerationCount();
		}

		return $count;
	}

	public static function getAllWaitingCount() {
		$count = 0;
		foreach ( AmapressMailingGroup::getAll() as $ml ) {
			$count += amapress_mailing_queue_waiting_mail_list_count( $ml->ID );
		}

		return $count;
	}

	public static function getAllErroredCount() {
		$count = 0;
		foreach ( AmapressMailingGroup::getAll() as $ml ) {
			$count += amapress_mailing_queue_errored_mail_list_count( $ml->ID );
		}

		return $count;
	}


	/** @return AmapressMailingGroup[] */
	public static function getAll() {
		$key = 'amapress_mlgrp_all_list';
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = array_map(
				function ( $p ) {
					return new AmapressMailingGroup( $p );
				},
				get_posts(
					array(
						'post_type'      => AmapressMailingGroup::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
					)
				)
			);
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public function cleanLogs() {
		$mail_group_log_clean_days = Amapress::getOption( 'mail_group_log_clean_days' );
		if ( $mail_group_log_clean_days < 0 ) {
			return;
		}
		$clean_date = Amapress::add_days( Amapress::start_of_day( time() ), - $mail_group_log_clean_days );
		$accepted   = $this->loadDataFromFiles( 'accepted' );
		$rejected   = $this->loadDataFromFiles( 'rejected' );
		foreach (
			array_merge( $accepted, $rejected )
			as $filename => $email
		) {
			if ( ! isset( $email['date'] ) || $email['date'] < $clean_date ) {
				$this->deleteMessage( $email['id'] );
			}
		}
	}

	public function testSMTP() {
		$ml_grp = $this;
		if ( $ml_grp->isExternalSmtp() ) {
			try {
				require_once AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueueOriginal.php';
				$phpmailer = AmapressSMTPMailingQueueOriginal::GetPHPMailer();

				// Set mailer to SMTP
				$phpmailer->isSMTP();

				// Set encryption type
				$phpmailer->SMTPSecure = $ml_grp->getSmtpEncryption();

				// Set host
				$phpmailer->Host = $ml_grp->getSmtpHost();
				$phpmailer->Port = $ml_grp->getSmtpPort();

				// Timeout
				$phpmailer->Timeout = 30;

				// Set authentication data
				if ( $ml_grp->UseSmtpAuth() ) {
					if ( ! empty( $ml_grp->getSmtpUserName() ) ) {
						$phpmailer->SMTPAuth = true;
						$phpmailer->Username = $ml_grp->getSmtpUserName();
						$phpmailer->Password = $ml_grp->getSmtpPassword();
					} elseif ( ! empty( $ml_grp->getUsername() ) ) {
						$phpmailer->SMTPAuth = true;
						$phpmailer->Username = $ml_grp->getUsername();
						$phpmailer->Password = $ml_grp->getPassword();
					}
				}

				if ( $phpmailer->smtpConnect() ) {
					$phpmailer->smtpClose();

					return true;
				} else {
					return false;
				}
			} catch ( Exception $ex ) {
				return $ex->getMessage();
			}
		}

		return true;
	}

	public function downloadEml( $msg_id, $type = 'accepted' ) {
		$msg = $this->loadMessage( $type, $msg_id );
		if ( ! $msg ) {
			wp_die( __( 'Message introuvable', 'amapress' ) );
		}

		Amapress::sendDocumentFile( $msg['eml_file'], $msg_id . '.eml' );
	}

}
