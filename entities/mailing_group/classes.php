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

		return $ret;
	}

	public function getMembersSMSTo() {
		$phones = [];
		foreach ( $this->getMembersQueries() as $user_query ) {
			foreach ( get_users( $user_query ) as $user ) {
				$amapien = AmapressUser::getBy( $user );
				$phones  = array_merge( $phones, $amapien->getPhoneNumbers( true ) );
			}
		}
		if ( empty( $phones ) ) {
			return '';
		}

		return 'sms:' . implode( ',', $phones );
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
		return $this->getCustom( 'amapress_mailing_group_port' );
	}

	public function getUsername() {
		return $this->getCustom( 'amapress_mailing_group_username' );
	}

	public function getPassword() {
		return $this->getCustom( 'amapress_mailing_group_password' );
	}

	public function getProtocol() {
		return $this->getCustom( 'amapress_mailing_group_protocol' );
	}

	public function getEncryption() {
		return $this->getCustom( 'amapress_mailing_group_encryption' );
	}

	public function isSelfSignedSSL() {
		return $this->getCustomAsInt( 'amapress_mailing_group_self_signed' );
	}

	public function distributeMail( $msg_id ) {
		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			wp_die( "Le message $msg_id n'existe pas/plus." );
		}

		if ( ! $this->isCurrentUserModerator() ) {
			wp_die( "Vous n'êtes pas modérateur de la liste {$this->getName()}" );
		}

		$msg['moderator'] = amapress_current_user_id();
		$msg['mod_date']  = amapress_time();
		if ( ! $this->sendMailFromMsgId( 'waiting', $msg_id ) ) {
			wp_die( "Le message $msg_id n'a pas pu être envoyé." );
		}
		$this->sendMailByParamName( 'mailinggroup-distrib-sender', $msg, $msg['from'] );
		$this->storeMailData( 'accepted', $msg );

		$this->deleteMessage( 'waiting', $msg_id );
	}

	public function rejectMailQuiet( $msg_id ) {
		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			wp_die( "Le message $msg_id n'existe pas/plus." );
		}

		if ( ! $this->isCurrentUserModerator() ) {
			wp_die( "Vous n\'êtes pas modérateur de la liste {$this->getName()}" );
		}

		$msg['moderator'] = amapress_current_user_id();
		$msg['mod_date']  = amapress_time();
		$this->storeMailData( 'rejected', $msg );

		$this->deleteMessage( 'waiting', $msg_id );
	}

	public function rejectMail( $msg_id ) {
		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			wp_die( "Le message $msg_id n'existe pas/plus." );
		}

		if ( ! $this->isCurrentUserModerator() ) {
			wp_die( "Vous n\'êtes pas modérateur de la liste {$this->getName()}" );
		}

		$msg['moderator'] = amapress_current_user_id();
		$msg['mod_date']  = amapress_time();
		$this->storeMailData( 'rejected', $msg );
		$this->sendMailByParamName( 'mailinggroup-reject-sender', $msg, $msg['from'] );
		$this->deleteMessage( 'waiting', $msg_id );
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
		return count( $this->getMailWaitingModeration() );
	}

	public function getMembersCount() {
		$user_ids = [];
		foreach ( $this->getMembersQueries() as $query ) {
			foreach ( get_users( $query ) as $user ) {
				$user_ids[] = $user->ID;
			}
		}

		return count( array_unique( $user_ids ) );
	}

	public function testParams() {
		try {
			$mailbox = $this->getMailbox();
			$mailbox->disconnect();

			return true;
		} catch ( Exception $ex ) {
			return "IMAP connection failed: " . $ex;
		}
	}

	/**
	 * @param PhpImap\Mailbox $mailbox
	 * @param $msgId
	 * @param bool $markAsSeen
	 *
	 * @return mixed
	 */
	public function getRawMail( $mailbox, $msgId, $markAsSeen = true ) {
		$options = ( SE_UID == $mailbox->getImapSearchOption() ) ? FT_UID : 0;
		if ( ! $markAsSeen ) {
			$options |= FT_PEEK;
		}
		$options |= FT_INTERNAL;

		return str_replace( "\r", '', $mailbox->imap( 'fetchbody', [ $msgId, '', $options ] ) );
	}

	public function fetchMails() {
		try {
			$mailbox = $this->getMailbox();
		} catch ( Exception $ex ) {
			error_log( "IMAP connection failed: " . $ex );

			return false;
		}

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
				$to           = array_filter( $mail->to, function ( $addr ) {
					return false === strpos( $addr, $this->getName() );
				} );
				$to           = implode( ', ', $to );
				$content      = ! empty( $mail->textHtml ) ? $mail->textHtml : $mail->textPlain;
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
					$cc   = implode( ', ', $mail->cc );
					$from = ! empty( $mail->fromName ) ? "{$mail->fromName} <{$mail->fromAddress}>" : $mail->fromAddress;
					$date = $mail->date;
//				$mail->importance;
//				$mail->priority;
//				$mail->replyTo;
					$subject = $mail->subject;
					$body    = [
						'text'        => $mail->textPlain,
						'html'        => $mail->textHtml,
						'attachments' => [],
					]; //$this->getRawMailBody( $mailbox, $mail_id );

					foreach ( $mail->getAttachments() as $attachment ) {
						$body['attachments'][] = [
							'id'     => $attachment->contentId,
							'name'   => $attachment->name,
							'inline' => ( 0 == strcasecmp( $attachment->disposition, 'inline' ) ),
							'file'   => $attachment->filePath
						];
					}

					if ( $this->isAllowedSender( $mail->fromAddress ) || $this->isAllowedSender( $mail->senderAddress ) ) {
						$res = $this->sendMail( $from, $to, $cc, $subject, $body, $headers );
						if ( ! $res ) {
							error_log( 'Cannot send mail to members' );
						}
					} else {
						$res = $this->saveMailForModeration( $msg_id, $date, $from, $to, $cc, $subject, $content, $body, $headers, $eml_file );
						if ( ! $res ) {
							error_log( 'Cannot save mail for moderation' );
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
			error_log( "IMAP connection failed: " . $ex );

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
		fwrite( $handle, json_encode( $data ) );
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

	private function getUploadDir( $type = 'waiting' ) {
		$dir     = wp_upload_dir()['basedir'] . "/amapress-mailingroups-{$this->getID()}/$type/";
		$created = wp_mkdir_p( $dir );
		if ( $created ) {
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

	public function loadMessage( $type, $msg_id ) {
		return $this->loadMessageFile( $this->getUploadDir( $type ) . $msg_id . '.json' );
	}

	public function deleteMessage( $type, $msg_id ) {
		@unlink( $this->getUploadDir( $type ) . $msg_id . '.json' );
	}

	public function loadDataFromFiles( $types = [ 'waiting' ] ) {
		$emails = [];
		if ( ! is_array( $types ) ) {
			$types = [ $types ];
		}

		foreach ( $types as $type ) {
//			if ( 'waiting' == $type || 'errored' == $type || 'logged' == $type ) {
			foreach ( glob( $this->getUploadDir( $type ) . '*.json' ) as $filename ) {
				$emails[ $filename ] = $this->loadMessageFile( $filename );
			}
//			}
		}

		return $emails;
	}

	private function getEmailsFromQueries( $queries ) {
		global $wpdb;

		$key = 'amps_mlg_q' . str_replace( ' ', '', var_export( $queries, true ) );
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$res = array_map( function ( $email ) {
				return strtolower( $email );
			}, $wpdb->get_col( Amapress_MailingList::getSqlQuery( $queries ) ) );
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
		return in_array( $senderAddress, $this->getEmailsFromQueries( $this->getMembersQueries() ) );
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

		return $this->sendMail( $msg['from'], $msg['to'], $msg['cc'], $msg['subject'], $msg['raw_message'], $msg['headers'] );
	}

	private function sendMail( $from, $to, $cc, $subject, $body, $headers ) {
		if ( ! empty( $cc ) ) {
			$headers[] = 'Cc: ' . $cc;
		}
		$headers[] = 'Bcc: ' . implode( ',', $this->getEmailsFromQueries( $this->getMembersQueries() ) );
		$headers   = array_filter( $headers, function ( $h ) {
			return false === strpos( $h, 'From' );
		} );
		$headers[] = 'From: ' . $from;
		$headers[] = "Sender: {$this->getName()}";

		$admin_email = get_option( 'admin_email' );
		$desc        = $this->getDescription();
		if ( empty( $desc ) ) {
			$desc = $this->getSimpleName();
		}
		$to = "{$desc} <{$this->getName()}>";

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
				$headers[] = 'Reply-To: ' . $from;
				break;
			case 'list':
				$headers[] = 'Reply-To: ' . $this->getName();
				break;
		}
//		$headers = array_filter( $headers, function ( $h ) {
//			return !preg_match('/^\s*(?:Date|Content-Type|Message-ID):/i', $h);
//		} );

		return wp_mail( $to, $this->getSubjectPrefix() . ' ' . $subject, $body, $headers, $body['attachments'] );
	}

	private function saveMailForModeration( $msg_id, $date, $from, $to, $cc, $subject, $content, $body, $headers, $eml_file ) {
		if ( ! $this->storeMail( 'waiting', $msg_id, $date, $from, $to, $cc, $subject, $content, $body, $headers,
			[ 'date' => amapress_time(), 'eml_file' => $eml_file ] ) ) {
			error_log( 'saveMailForModeration - storeMail failed' );

			return false;
		}

		$msg = $this->loadMessage( 'waiting', $msg_id );
		if ( ! $msg ) {
			error_log( 'saveMailForModeration - loadMessage failed' );

			return false;
		}

		if ( ! $this->sendMailByParamName( 'mailinggroup-waiting-sender', $msg, $msg['from'] ) ) {
			error_log( 'saveMailForModeration - sendMailByParamName - waiting-sender failed' );

			return false;
		}
		if ( ! $this->sendMailByParamName( 'mailinggroup-waiting-mods',
			$msg, $this->getEmailsFromQueries( $this->getModeratorsQueries() ),
			[
				[
					'name'   => 'email.eml',
					'inline' => false,
					'file'   => $msg['eml_file']
				]
			] ) ) {
			error_log( 'saveMailForModeration - sendMailByParamName - waiting-mods failed' );

			return false;
		}

		return true;
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

		$placeholders = [
			'liste_nom'              => $this->getName(),
			'nom_liste'              => $this->getName(),
			'moderated_by'           => $moderator ? Amapress::makeLink( 'mailto:' . $moderator->getEmail(), $moderator->getDisplayName() ) : '',
			'moderated_by_email'     => $moderator ? $moderator->getEmail() : '',
			'moderated_by_name'      => $moderator ? $moderator->getDisplayName() : '',
			'msg_subject'            => $msg['subject'],
			'sender'                 => $msg['from'],
			'msg_reject_silent_link' => amapress_get_mailgroup_action_form( 'Rejetter sans prévenir', 'amapress_mailgroup_reject_quiet', $this->ID, $msg['id'] ),
			'msg_reject_notif_link'  => amapress_get_mailgroup_action_form( 'Rejetter', 'amapress_mailgroup_reject', $this->ID, $msg['id'] ),
			'msg_distrib_link'       => amapress_get_mailgroup_action_form( 'Distribuer', 'amapress_mailgroup_distribute', $this->ID, $msg['id'] ),
		];

		foreach ( $placeholders as $k => $v ) {
			$content = str_replace( "%%$k%%", $v, $content );
		}

		return amapress_replace_mail_placeholders( $content, null, null );
	}

	public static function getPlaceholdersHelp( $additional_helps = [] ) {
		$additional_helps = array_merge(
			$additional_helps,
			[
				'liste_nom'              => 'Nom de la liste',
				'nom_liste'              => 'Nom de la liste',
				'moderated_by'           => 'Mailto du modérateur du message',
				'moderated_by_email'     => 'Email du modérateur du message',
				'moderated_by_name'      => 'Nom du modérateur du message',
				'msg_subject'            => 'Sujet du mail modéré',
				'sender'                 => 'Emetteur du mail',
				'msg_reject_silent_link' => 'Lien de rejet sans notification du message',
				'msg_reject_notif_link'  => 'Lien de rejet avec notification du message',
				'msg_distrib_link'       => 'Lien de distribution du message',
			]
		);

		return Amapress::getPlaceholdersHelpTable( 'mailinggroup-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'de l\'Email groupé', $additional_helps );
	}

	public function getMailbox() {
		if ( empty( $this->getHost() )
		     || empty( $this->getPort() )
		     || empty( $this->getProtocol() ) ) {
			throw new Exception( "Invalid configuration" );
		}

		$encryption = '';
		if ( 'none' != $this->getEncryption() ) {
			$encryption = '/' . $this->getEncryption();
		}
		if ( $this->isSelfSignedSSL() ) {
			$encryption .= '/novalidate-cert';
		}

		$mailbox = new PhpImap\Mailbox(
			'{' . $this->getHost() . ':' . $this->getPort() . '/' . $this->getProtocol() . $encryption . '}INBOX', // IMAP server and mailbox folder
			$this->getUsername(),
			$this->getPassword()
		);

		return $mailbox;
	}


	/** @return AmapressMailingGroup[] */
	public static function getAll() {
		return array_map(
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
	}
}