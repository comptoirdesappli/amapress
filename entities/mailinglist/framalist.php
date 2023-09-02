<?php

class Amapress_Framalist_MailingList extends Amapress_Sympa_MailingList {
	/** @return  Amapress_Framalist_MailSystem */
	public function getSystem() {
		return parent::getSystem();
	}

	function __construct( $name, $info, Amapress_Framalist_MailSystem $system ) {
		parent::__construct( $name, $info, $system );
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 */
	public function syncMembers( $config ) {
		$members_queries = $config->getMembersQueries();

		$sql_query = Amapress_MailingList::getSqlQuery( $members_queries, $config->getExcludeMembersQueries() );
		global $wpdb;
		$all_emails = empty( $sql_query ) ? [] : $wpdb->get_col( $sql_query );
		$all_emails = array_merge( $all_emails, $config->getRawEmails() );

		$query_emails = array_unique( Amapress_MailingList::normalizeEmailsArray( $all_emails ) );
		$sympa_emails = Amapress_MailingList::normalizeEmailsArray( $this->getSympaMembersEmails() );

		$to_add = array_diff( $query_emails, $sympa_emails );
		$to_del = array_diff( $sympa_emails, $query_emails );

		foreach ( $to_del as $email ) {
			$this->getSystem()->removeSympaMember( $this->getName(), $email );
			usleep( 800000 );
		}
		foreach ( $to_add as $email ) {
			$this->getSystem()->addSympaMember( $this->getName(), $email );
			usleep( 800000 );
		}
	}

	public function getSympaMembersEmails() {
		return $this->getSystem()->getSympaMembersEmails( $this->getName() );
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 *
	 * @return string
	 */
	public function isSync( $config ) {
		$members_queries = $config->getMembersQueries();

		$sql_query = Amapress_MailingList::getSqlQuery( $members_queries, $config->getExcludeMembersQueries() );
		global $wpdb;
		$all_emails = empty( $sql_query ) ? [] : $wpdb->get_col( $sql_query );
		$all_emails = array_merge( $all_emails, $config->getRawEmails() );

		$query_emails = array_unique( Amapress_MailingList::normalizeEmailsArray( $all_emails ) );
		$sympa_emails = Amapress_MailingList::normalizeEmailsArray( $this->getSympaMembersEmails() );

		$was_errored = $wpdb->last_error;
		$inter       = array_intersect( $query_emails, $sympa_emails );
		if ( ! $was_errored && count( $inter ) == count( $sympa_emails ) && count( $inter ) == count( $query_emails ) ) {
			return 'sync';
		} else {
			return 'not_sync';
		}
	}
}

class Amapress_Framalist_MailSystem extends Amapress_Sympa_MailSystem {
	public function getSystemId() {
		return 'framalistes';
	}

	public function getSystemName() {
		return 'Framalistes';
	}

	public function addSympaMember( $list_name, $email ) {
		$post_data = array(
			'csrftoken'               => $this->getCSRFToken(),
			'email'                   => $email,
			'list'                    => $list_name,
			'family'                  => '',
			'previous_action'         => '',
			'action'                  => 'add',
			'response_action_confirm' => __( 'Confirmer', 'amapress' ),
		);
		self::$client->post( '', [
			'form_params' => $post_data
		] );
		$resp = self::$client->post( '', [
			'form_params' => $post_data
		] );

		return 200 == $resp->getStatusCode();
	}

	public function removeSympaMember( $list_name, $email ) {
		$post_data = array(
			'csrftoken'               => $this->getCSRFToken(),
			'email'                   => $email,
			'list'                    => $list_name,
			'quiet'                   => 1,
			'family'                  => '',
			'previous_action'         => '',
			'action'                  => 'del',
			'response_action_confirm' => __( 'Confirmer', 'amapress' ),
		);
		self::$client->post( '', [
			'form_params' => $post_data,
		] );
		$resp = self::$client->post( '', [
			'form_params' => $post_data,
		] );

		return 200 == $resp->getStatusCode();
	}

	public function getSympaMembersEmails( $list_name ) {
		$resp = self::$client->get( "review/$list_name", [ 'query' => "size=500" ] );
		$ret  = [];
		if ( 200 == $resp->getStatusCode() ) {
			$body = $resp->getBody();
			preg_match_all( '/\<a\s+href\="\/sympa\/editsubscriber\/[^"]+"\>(?<email>[^\<]+)\<\/a\>/', $body, $ms, PREG_SET_ORDER );
			if ( ! empty( $ms ) ) {
				foreach ( $ms as $m ) {
					$ret[] = trim( $m['email'] );
				}
			}
		}

		return $ret;
	}

	public function getMailingList( $name ) {
		$list_info = array();
		$resp      = self::$client->get( "edit_list_request/$name/description" );
		if ( 200 == $resp->getStatusCode() ) {
			$body = $resp->getBody();
			preg_match( '/id\="(?:single_)?param.subject.name"\s+value="(?<desc>[^"]+)"/', $body, $m );
			if ( ! empty( $m['desc'] ) ) {
				$list_info['desc'] = ( $m['desc'] );
			} else {
				$list_info['desc'] = '';
			}
			preg_match( '/Emails?\s*\((?<waiting>\d+)\)/', $body, $m );
			$list_info['waiting'] = isset( $m['waiting'] ) ? intval( $m['waiting'] ) : 0;
			preg_match( '/Abonnés\s*:\s*(?:\<span\>)?(?<members_count>\d+)/', $body, $m );
			$list_info['members_count'] = isset( $m['members_count'] ) ? intval( $m['members_count'] ) : 0;
			preg_match( '/Taux\s*d\'erreurs\s*:\s*(?:\<span\>)?(?<bounce_rate>\d+(?:,\d+)?)/', $body, $m );
			$list_info['bounce_rate'] = isset( $m['bounce_rate'] ) ? $m['bounce_rate'] : 0;
			preg_match_all( '/id\="(?:single_)?param.(?:owner|moderator).\d+.email"\s+value="(?<mod>[^"]+)"/', $body, $m, PREG_SET_ORDER );
			$list_info['moderators']        = array();
			$list_info['moderators_emails'] = array();
			foreach ( $m as $mm ) {
				$list_info['moderators_found'] = true;
				$email                         = html_entity_decode( $mm['mod'] );
				$u                             = get_user_by( 'email', $email );
				if ( $u ) {
					$list_info['moderators'][]        = $u->ID;
					$list_info['moderators_emails'][] = $email;
				}
			}
			$list_info['moderators']        = array_unique( $list_info['moderators'] );
			$list_info['moderators_emails'] = array_unique( $list_info['moderators_emails'] );
		}
		$resp = self::$client->get( "edit_list_request/$name/sending" );
		if ( 200 == $resp->getStatusCode() ) {
			$body                          = $resp->getBody();
			$reply_to_options              = array();
			$list_info['reply_to']         = self::parseSelect( $body, 'single_param.reply_to_header.value', $reply_to_options );
			$list_info['reply_to_options'] = $reply_to_options;
			$moderations                   = array();
			$list_info['moderation']       = self::parseSelect( $body, 'single_param.send.name', $moderations );
			$list_info['moderations']      = $moderations;
		}

		return new Amapress_Framalist_MailingList( $name, $list_info, $this );
	}

	function __construct( $mailinglist_domain, $login, $pass ) {
		parent::__construct( $mailinglist_domain, $login, $pass,
			'https', $this->getSystemId(),
			false ); //Amapress::toBool( Amapress::getOption( 'framalistes_manage_waiting' ) ) );
	}

	public function getMailingListBaseUrl() {
		return "{$this->protocol}://{$this->mailinglist_domain}/sympa/";
	}

	public function getListsUri() {
		return 'my';
	}
}

add_filter( 'amapress_get_mailinglist_systems', 'amapress_framalistes_get_mailinglist_systems' );
function amapress_framalistes_get_mailinglist_systems( $systems ) {
	$login = Amapress::getOption( 'framalistes_admin_user' );
	$pass  = Amapress::getOption( 'framalistes_admin_pass' );
	if ( defined( 'AMAPRESS_FRAMALISTE_ADMIN_PASSWORD' ) ) {
		$pass = AMAPRESS_FRAMALISTE_ADMIN_PASSWORD;
	}
	$enabled = Amapress::getOption( 'framalistes_enable' );
	if ( $enabled && ! empty( $login ) && ! empty( $pass ) ) {
		$systems[] = new Amapress_Framalist_MailSystem( 'framalistes.org', $login, $pass );
	}

	return $systems;
}