<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Amapress_Ouvaton_MailingList extends Amapress_Sympa_MailingList {
	/** @return  Amapress_Ouvaton_MailSystem */
	public function getSystem() {
		return parent::getSystem();
	}

	function __construct( $name, $info, Amapress_Ouvaton_MailSystem $system ) {
		parent::__construct( $name, $info, $system );
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 */
	public function syncMembers( $config ) {
		$moderators_queries = $config->getModeratorsQueries();
		$members_queries    = $config->getMembersQueries();

		if ( ! empty( $moderators_queries ) ) {
			$user_ids = array();
			foreach ( $moderators_queries as $q ) {
				$users = get_users( $q );
				if ( ! empty( $users ) ) {
					$user_ids = array_merge( $user_ids, array_map( function ( $u ) {
						return $u->ID;
					}, $users ) );
				}
			}
			$this->setModerators( $user_ids );
		}

		foreach ( $config->getRawEmails() as $email ) {
			$escaped_email     = esc_sql( strtolower( $email ) );
			$members_queries[] = "SELECT '{$escaped_email}' as email";
		}

		if ( empty( $members_queries ) ) {
			return;
		}

		$new_query = Amapress_MailingList::getSqlQuery( $members_queries, $config->getExcludeMembersQueries() );
		$this->getSystem()->setSqlDataSource( $new_query, $this->getName() );
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 *
	 * @return string
	 */
	public function isSync( $config ) {
		$moderators_queries = $config->getModeratorsQueries();

		if ( $this->handleModerators() && ! empty( $moderators_queries ) ) {
			$user_emails = array();
			foreach ( $moderators_queries as $q ) {
				$users = get_users( $q );
				if ( ! empty( $users ) ) {
					foreach ( $users as $user ) {
						$amapien     = AmapressUser::getBy( $user );
						$user_emails = array_merge( $user_emails, $amapien->getAllEmails() );
					}
					$user_emails = array_unique( $user_emails );
				}
			}
			$inter = array_intersect( Amapress_MailingList::normalizeEmailsArray( $this->getModeratorsEmails() ), Amapress_MailingList::normalizeEmailsArray( $user_emails ) );
			if ( count( $user_emails ) != count( $inter ) || count( $this->getModeratorsEmails() ) != count( $inter ) ) {
				return 'not_sync';
			}
		}

		$members_queries = $config->getMembersQueries();

		foreach ( $config->getRawEmails() as $email ) {
			$escaped_email     = esc_sql( strtolower( $email ) );
			$members_queries[] = "SELECT '{$escaped_email}' as email";
		}

		global $wpdb;
		$sql_query = isset( $this->info['query'] ) ? $this->info['query'] : '';
		if ( ! empty( $sql_query ) ) {
			$new_query = Amapress_MailingList::getSqlQuery( $members_queries, $config->getExcludeMembersQueries() );
			if ( empty( $new_query ) ) {
				return 'manual';
			}
			$new_query = trim( preg_replace( '/\s+/', ' ', $new_query ) );
			$sql_query = trim( preg_replace( '/\s+/', ' ', $sql_query ) );

			//TODO : dont know why there is {xxxx} in "like wp_capabilities"
			$new_query = trim( preg_replace( '/\{[0-9A-Fa-f]+\}/', '', $new_query ) );
			$sql_query = trim( preg_replace( '/\{[0-9A-Fa-f]+\}/', '', $sql_query ) );

			$new_users   = array_unique( Amapress_MailingList::normalizeEmailsArray( $wpdb->get_col( $new_query ) ) );
			$was_errored = $wpdb->last_error;
			$old_users   = array_unique( Amapress_MailingList::normalizeEmailsArray( $wpdb->get_col( $sql_query ) ) );
			$was_errored = $was_errored || $wpdb->last_error;
			$inter       = array_intersect( $new_users, $old_users );
			if ( ! $was_errored && count( $inter ) == count( $old_users ) && count( $inter ) == count( $new_users ) ) {
				return 'sync';
			} else {
				return 'not_sync';
			}
		} else {
			return 'manual';
		}
	}
}

class Amapress_Ouvaton_MailSystem extends Amapress_Sympa_MailSystem {
	public function getSystemId() {
		return 'ouvaton';
	}

	public function getSystemName() {
		return 'Ouvaton';
	}

	public function getMailingList( $name ) {
		$list_info = array();
		$resp      = self::$client->get( "edit_list_request/$name/description" );
		if ( 200 == $resp->getStatusCode() ) {
			$body = $resp->getBody();
			preg_match( '/id\="single_param.subject.name"\s+value="(?<desc>[^"]+)"/', $body, $m );
			$list_info['desc'] = ( $m['desc'] );
			preg_match( '/Emails?\s*\((?<waiting>\d+)\)/', $body, $m );
			$list_info['waiting'] = isset( $m['waiting'] ) ? intval( $m['waiting'] ) : 0;
			preg_match( '/Abonnés\s*:\s*(?:\<span\>)?(?<members_count>\d+)/', $body, $m );
			$list_info['members_count'] = isset( $m['members_count'] ) ? intval( $m['members_count'] ) : 0;
			preg_match( '/Taux\s*d\'erreurs\s*:\s*(?:\<span\>)?(?<bounce_rate>\d+(?:,\d+)?)/', $body, $m );
			$list_info['bounce_rate'] = isset( $m['bounce_rate'] ) ? $m['bounce_rate'] : 0;
			preg_match_all( '/id\="single_param.(?:owner|moderator).\d+.email"\s+value="(?<mod>[^"]+)"/', $body, $m, PREG_SET_ORDER );
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
		$resp = self::$client->get( "edit_list_request/$name/data_source" );
		if ( 200 == $resp->getStatusCode() ) {
			$body = $resp->getBody();

			$source_modes              = array();
			$list_info['data_source']  = self::parseSelect( $body, 'single_param.user_data_source', $source_modes );
			$list_info['data_sources'] = $source_modes;

			if ( preg_match( '/id\="single_param.include_sql_query.0.sql_query"\s+value="(?<mode>[^"]*)"/', $body, $m ) ) {
				$list_info['query'] = html_entity_decode( $m['mode'] );
			} elseif ( preg_match( '/id\="param.include_sql_query.0.sql_query"\s+value="(?<mode>[^"]*)"/', $body, $m ) ) {
				$list_info['query'] = html_entity_decode( $m['mode'] );
			}
		}

		return new Amapress_Ouvaton_MailingList( $name, $list_info, $this );
	}

	function __construct( $mailinglist_domain, $login, $pass ) {
		parent::__construct( $mailinglist_domain, $login, $pass,
			'http', $this->getSystemId(),
			false ); // Amapress::toBool( Amapress::getOption( 'ouvaton_manage_waiting' ) ) );
	}
}

add_filter( 'amapress_get_mailinglist_systems', 'amapress_ouvaton_get_mailinglist_systems' );
function amapress_ouvaton_get_mailinglist_systems( $systems ) {
	$mailinglist_domain = trim( trim( Amapress::getOption( 'ouvaton_mailing_domain' ) ), '@' );
	$login              = Amapress::getOption( 'ouvaton_admin_user' );
	$pass               = Amapress::getOption( 'ouvaton_admin_pass' );
	if ( defined( 'AMAPRESS_OUVATON_SYMPA_ADMIN_PASSWORD' ) ) {
		$pass = AMAPRESS_OUVATON_SYMPA_ADMIN_PASSWORD;
	}
	if ( ! empty( $mailinglist_domain ) && ! empty( $login ) && ! empty( $pass ) ) {
		$systems[] = new Amapress_Ouvaton_MailSystem( $mailinglist_domain, $login, $pass );
	}

	return $systems;
}