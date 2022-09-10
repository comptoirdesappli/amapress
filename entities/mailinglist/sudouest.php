<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Amapress_SudOuest_MailingList extends Amapress_Sympa_MailingList {
	/** @return  Amapress_SudOuest_MailSystem */
	public function getSystem() {
		return parent::getSystem();
	}

	function __construct( $name, $info, Amapress_SudOuest_MailSystem $system ) {
		parent::__construct( $name, $info, $system );
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 */
	public function syncMembers( $config ) {
		$moderators_queries = $config->getModeratorsQueries();
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

		$this->getSystem()->setRemoteUrl( $this->getName(), $this->getRemoteUrl( $config->ID ) );
	}

	public function getRemoteUrl( $id ) {
		return add_query_arg(
			array(
				'action' => 'fetch-mailing-members',
				'id'     => $id,
				'secret' => Amapress::getOption( 'sud-ouest_secret' ),
			),
			admin_url( 'admin-post.php', 'http' )
		);
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
			$inter = array_intersect( $this->getModeratorsEmails(), $user_emails );
			if ( count( $user_emails ) != count( $inter ) || count( $this->getModeratorsEmails() ) != count( $inter ) ) {
				return 'not_sync';
			}
		}
		$remote_url = isset( $this->info['remote_url'] ) ? $this->info['remote_url'] : '';
		if ( ! empty( $remote_url ) ) {
			$new_remote_url = $this->getRemoteUrl( $config->ID );
			if ( $new_remote_url == $remote_url ) {
				return 'sync';
			} else {
				return 'not_sync';
			}
		} else {
			return 'manual';
		}
	}
}

class Amapress_SudOuest_MailSystem extends Amapress_Sympa_MailSystem {
	public function getSystemId() {
		return 'sud-ouest';
	}

	public function getSystemName() {
		return 'SudOuest2.org';
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

			preg_match( '/id\="single_param.include_remote_file.0.url"\s+value="(?<url>[^"]*)"/', $body, $m );
			$list_info['remote_url'] = html_entity_decode( $m['url'] );
		}

		return new Amapress_SudOuest_MailingList( $name, $list_info, $this );
	}

	function __construct( $mailinglist_domain, $login, $pass ) {
		parent::__construct( $mailinglist_domain, $login, $pass,
			'https', $this->getSystemId(),
			false );  //Amapress::toBool( Amapress::getOption( 'sud-ouest_manage_waiting' ) ) );
	}
}

add_filter( 'amapress_get_mailinglist_systems', 'amapress_SudOuest_get_mailinglist_systems' );
function amapress_SudOuest_get_mailinglist_systems( $systems ) {
	$mailinglist_domain = trim( trim( Amapress::getOption( 'sud-ouest_mailing_domain' ) ), '@' );
	$login              = Amapress::getOption( 'sud-ouest_admin_user' );
	$pass               = Amapress::getOption( 'sud-ouest_admin_pass' );
	if ( defined( 'AMAPRESS_SUDOUEST_SYMPA_ADMIN_PASSWORD' ) ) {
		$pass = AMAPRESS_SUDOUEST_SYMPA_ADMIN_PASSWORD;
	}
	if ( ! empty( $mailinglist_domain ) && ! empty( $login ) && ! empty( $pass ) ) {
		$systems[] = new Amapress_SudOuest_MailSystem( $mailinglist_domain, $login, $pass );
	}

	return $systems;
}

add_action( 'admin_post_nopriv_fetch-mailing-members', function () {
	if ( empty( Amapress::getOption( 'sud-ouest_admin_user' ) ) ) {
		wp_die( "No Sud-Ouest Mailing list found" );
	}

	if ( ! isset( $_REQUEST['secret'] ) || $_REQUEST['secret'] != Amapress::getOption( 'sud-ouest_secret' ) ) {
		wp_die( "Sync secret does not match" );
	}

	$ml = new Amapress_MailingListConfiguration( $_REQUEST['id'] );
	if ( ! $ml ) {
		wp_die( "Mailing list {$_REQUEST['id']} cannot be found" );
	}

	$members_queries = $ml->getMembersQueries();
	foreach ( $ml->getRawEmails() as $email ) {
		$escaped_email     = esc_sql( strtolower( $email ) );
		$members_queries[] = "SELECT '{$escaped_email}' as email";
	}

	header( 'Content-type: text/plain' );
	$sql_query = Amapress_MailingList::getSqlQuery( $members_queries, $ml->getExcludeMembersQueries() );
	global $wpdb;
	foreach ( $wpdb->get_col( $sql_query ) as $email ) {
		$normalized_email = strtolower( $email );
		echo "{$normalized_email}\n";
	}
	die();
} );
