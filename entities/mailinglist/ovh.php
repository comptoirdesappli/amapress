<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Amapress_OVH_MailingList extends Amapress_MailingList {
	public static $OVH_SYNC_WAIT = 800000;

	public function getFullName() {
		return $this->getSystem()->getFullName( $this->getName() );
	}

	/** @return  Amapress_OVH_MailSystem */
	public function getSystem() {
		return parent::getSystem();
	}

	function __construct( $name, $info, Amapress_OVH_MailSystem $system ) {
		parent::__construct( $name, $info, $system );
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 */
	public function syncMembers( $config ) {
		global $wpdb;
		$moderators_queries = $config->getModeratorsQueries();
		if ( ! empty( $moderators_queries ) ) {
			$sql_query = Amapress_MailingList::getSqlQuery( $moderators_queries, [] );
			if ( empty( $sql_query ) ) {
				return;
			}
			$sympa_emails = Amapress_MailingList::normalizeEmailsArray( $this->getSystem()->getMLModeratorsEmails( $this->getName() ) );
			$query_emails = array_unique( Amapress_MailingList::normalizeEmailsArray( $wpdb->get_col( $sql_query ) ) );

			$to_add = array_diff( $query_emails, $sympa_emails );
			$to_del = array_diff( $sympa_emails, $query_emails );

			foreach ( $to_del as $email ) {
				$this->getSystem()->removeMLModerator( $this->getName(), $email );
				usleep( self::$OVH_SYNC_WAIT );
			}
			foreach ( $to_add as $email ) {
				$this->getSystem()->addMLModerator( $this->getName(), $email );
				usleep( self::$OVH_SYNC_WAIT );
			}
		}

		$members_queries = $config->getMembersQueries();

		$sql_query = Amapress_MailingList::getSqlQuery( $members_queries, $config->getExcludeMembersQueries() );
		global $wpdb;
		$all_emails = empty( $sql_query ) ? [] : $wpdb->get_col( $sql_query );
		$all_emails = array_merge( $all_emails, $config->getRawEmails() );

		$query_emails = array_unique( Amapress_MailingList::normalizeEmailsArray( $all_emails ) );
		$sympa_emails = Amapress_MailingList::normalizeEmailsArray( $this->getSystem()->getMLMembersEmails( $this->getName() ) );

		$to_add = array_diff( $query_emails, $sympa_emails );
		$to_del = array_diff( $sympa_emails, $query_emails );

		foreach ( $to_del as $email ) {
			$this->getSystem()->removeMLMember( $this->getName(), $email );
			usleep( self::$OVH_SYNC_WAIT );
		}
		foreach ( $to_add as $email ) {
			$this->getSystem()->addMLMember( $this->getName(), $email );
			usleep( self::$OVH_SYNC_WAIT );
		}
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 *
	 * @return string
	 */
	public function isSync( $config ) {
		$moderators_queries = $config->getModeratorsQueries();
		global $wpdb;
		if ( $this->handleModerators() && ! empty( $moderators_queries ) ) {
			$sql_query    = Amapress_MailingList::getSqlQuery( $moderators_queries, [] );
			$sympa_emails = Amapress_MailingList::normalizeEmailsArray( $this->getSystem()->getMLModeratorsEmails( $this->getName() ) );
			$query_emails = array_unique( Amapress_MailingList::normalizeEmailsArray( $wpdb->get_col( $sql_query ) ) );
			$was_errored  = $wpdb->last_error;
			$inter        = array_intersect( $query_emails, $sympa_emails );
			if ( ! ( empty( $was_errored ) && count( $inter ) == count( $sympa_emails ) && count( $inter ) == count( $query_emails ) ) ) {
				return 'not_sync';
			}
		}

		$members_queries = $config->getMembersQueries();

		$sql_query = Amapress_MailingList::getSqlQuery( $members_queries, $config->getExcludeMembersQueries() );
		global $wpdb;
		$all_emails = empty( $sql_query ) ? [] : $wpdb->get_col( $sql_query );
		$all_emails = array_merge( $all_emails, $config->getRawEmails() );

		$query_emails = array_unique( Amapress_MailingList::normalizeEmailsArray( $all_emails ) );
		$sympa_emails = Amapress_MailingList::normalizeEmailsArray( $this->getSystem()->getMLMembersEmails( $this->getName() ) );

		$was_errored = $wpdb->last_error;
		$inter       = array_intersect( $query_emails, $sympa_emails );
		if ( empty( $was_errored ) && count( $inter ) == count( $sympa_emails ) && count( $inter ) == count( $query_emails ) ) {
			return 'sync';
		} else {
			return 'not_sync';
		}
	}

	public function handleModerationSetting() {
		return false;
	}

	public function getModerationModeName() {
		return '';
	}

	public function getModerationMode() {
		return null;
	}

	/** @return string */
	public function getConfigurationLink() {
		return $this->getSystem()->getConfigurationLink();
	}

	/** @return string */
	public function getModerationLink() {
		return $this->getSystem()->getModerationLink( $this->getName() );
	}

	/** @return string */
	public function getModeratorsLink() {
		return $this->getSystem()->getModeratorsLink( $this->getName() );
	}

	/** @return string */
	public function getMembersLink() {
		return $this->getSystem()->getMembersLink( $this->getName() );
	}
}

class Amapress_OVH_MailSystem extends Amapress_MailingSystem {
	public function getSystemId() {
		return 'ovh';
	}

	public function getSystemName() {
		return 'OVH Mailinglists';
	}

	protected function fetchMails() {
		if ( ! $this->isConnected() ) {
			return [];
		}
		$names = $this->ovh->get( "/email/domain/{$this->mailing_domain}/mailingList" );
		$ret   = [];
		foreach ( $names as $name ) {
			$ret[] = $this->getMailingList( $name );
		}

		return $ret;
	}

	public function addMLModerator( $list_name, $email ) {
		return $this->ovh->post( "/email/domain/{$this->mailing_domain}/mailingList/{$list_name}/moderator", array(
			'email' => $email,
		) );
	}

	public function removeMLModerator( $list_name, $email ) {
		return $this->ovh->delete( "/email/domain/{$this->mailing_domain}/mailingList/{$list_name}/moderator/{$email}" );
	}

	public function getMLModeratorsEmails( $list_name ) {
		return $this->ovh->get( "/email/domain/{$this->mailing_domain}/mailingList/{$list_name}/moderator" );
	}

	public function addMLMember( $list_name, $email ) {
		return $this->ovh->post( "/email/domain/{$this->mailing_domain}/mailingList/{$list_name}/subscriber", array(
			'email' => $email,
		) );
	}

	public function removeMLMember( $list_name, $email ) {
		return $this->ovh->delete( "/email/domain/{$this->mailing_domain}/mailingList/{$list_name}/subscriber/{$email}" );
	}

	public function getMLMembersEmails( $list_name ) {
		return $this->ovh->get( "/email/domain/{$this->mailing_domain}/mailingList/{$list_name}/subscriber" );
	}

	public function getFullName( $name ) {
		return "$name@{$this->mailing_domain}";
	}

	/** @return string */
	public function getConfigurationLink() {
		return "https://www.ovh.com/manager/web/#/email_domain/{$this->mailing_domain}/mailing-list";
	}

	/** @return string */
	public function getModerationLink( $list_name ) {
		return "https://www.ovh.com/manager/web/#/email_domain/{$this->mailing_domain}/mailing-list";
	}

	/** @return string */
	public function getModeratorsLink( $list_name ) {
		return "https://www.ovh.com/manager/web/#/email_domain/{$this->mailing_domain}/mailing-list";
	}

	/** @return string */
	public function getMembersLink( $list_name ) {
		return "https://www.ovh.com/manager/web/#/email_domain/{$this->mailing_domain}/mailing-list";
	}

	public function getMailingList( $name ) {
		$result = $this->ovh->get( "/email/domain/{$this->mailing_domain}/mailingList/{$name}" );

		$list_info                      = array();
		$list_info['desc']              = $name;
		$list_info['members_count']     = isset( $result->nbSubscribers ) ? intval( $result->nbSubscribers ) : 0;
		$list_info['moderators']        = array();
		$list_info['moderators_emails'] = array();
		foreach ( $this->ovh->get( "/email/domain/{$this->mailing_domain}/mailingList/{$name}/moderator" ) as $email ) {
			$list_info['moderators_found'] = true;
			$email                         = html_entity_decode( $email );
			$u                             = get_user_by( 'email', $email );
			if ( $u ) {
				$list_info['moderators'][]        = $u->ID;
				$list_info['moderators_emails'][] = $email;
			}
		}
		$list_info['moderators']        = array_unique( $list_info['moderators'] );
		$list_info['moderators_emails'] = array_unique( $list_info['moderators_emails'] );

		return new Amapress_OVH_MailingList( $name, $list_info, $this );
	}

	private $ovh_application_key;
	private $ovh_application_secret;
	private $ovh_endpoint;
	private $mailing_domain;
	private $ovh_consumer_key;
	private $ovh;
	private $is_connected;

	public function isConnected() {
		return $this->is_connected;
	}

	function __construct( $mailing_domain, $ovh_application_key, $ovh_application_secret, $ovh_consumer_key, $ovh_endpoint ) {
		parent::__construct();

		$this->ovh_application_key    = $ovh_application_key;
		$this->ovh_application_secret = $ovh_application_secret;
		$this->ovh_consumer_key       = $ovh_consumer_key;
		$this->ovh_endpoint           = $ovh_endpoint;
		$this->mailing_domain         = $mailing_domain;

		$this->is_connected = false;
		require_once AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php';
		try {
			$this->ovh = new \Ovh\Api(
				$this->ovh_application_key,
				$this->ovh_application_secret,
				$this->ovh_endpoint,
				$this->ovh_consumer_key );

			$this->is_connected = is_array( $this->ovh->get( '/me' ) );
		} catch ( Exception $ex ) {
			$this->error_message = $ex->getMessage();
		}
	}
}

add_filter( 'amapress_get_mailinglist_systems', 'amapress_ovh_get_mailinglist_systems' );
function amapress_ovh_get_mailinglist_systems( $systems ) {
	$ovh_mailingdomain   = Amapress::getOption( 'ovh_mailing_domain' );
	$ovh_application_key = Amapress::getOption( 'ovh_application_key' );
	if ( defined( 'AMAPRESS_MAILING_OVH_APPLICATION_KEY' ) ) {
		$ovh_application_key = AMAPRESS_MAILING_OVH_APPLICATION_KEY;
	}
	$ovh_application_secret = Amapress::getOption( 'ovh_application_secret' );
	if ( defined( 'AMAPRESS_MAILING_OVH_APPLICATION_SECRET' ) ) {
		$ovh_application_secret = AMAPRESS_MAILING_OVH_APPLICATION_SECRET;
	}
	$ovh_consumer_key = Amapress::getOption( 'ovh_consumer_key' );
	if ( defined( 'AMAPRESS_MAILING_OVH_CONSUMER_KEY' ) ) {
		$ovh_consumer_key = AMAPRESS_MAILING_OVH_CONSUMER_KEY;
	}
	$ovh_endpoint = Amapress::getOption( 'ovh_endpoint' );
	if ( ! empty( $ovh_application_key ) && ! empty( $ovh_application_secret ) && ! empty( $ovh_consumer_key ) ) {
		$systems[] = new Amapress_OVH_MailSystem( $ovh_mailingdomain,
			$ovh_application_key, $ovh_application_secret, $ovh_consumer_key,
			$ovh_endpoint );
	}

	return $systems;
}