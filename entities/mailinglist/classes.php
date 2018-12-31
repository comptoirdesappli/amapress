<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Amapress_MailingListConfiguration extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_mailing';
	const POST_TYPE = 'mailinglist';

	//const CATEGORY = 'amps_amap_event_category';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getName() {
		return $this->getCustom( 'amapress_mailinglist_name' );
	}

	public function getDescription() {
		return $this->getCustom( 'amapress_mailinglist_desc' );
	}

	public function getAddress() {
		$name = $this->getName();

		return explode( ':', $name )[1];
	}

	public function getMembersQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailinglist_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailinglist_other_users' );
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

	public function getModeratorsQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailinglist_moderators_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailinglist_moderators_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		return $ret;
	}

	public function getMailingList() {
		return Amapress_MailingSystems::getMailingListByName( $this->getName() );
	}

	/** @return Amapress_MailingListConfiguration[] */
	public static function getAll() {
		return array_map(
			function ( $p ) {
				return new Amapress_MailingListConfiguration( $p );
			},
			get_posts(
				array(
					'post_type'      => Amapress_MailingListConfiguration::INTERNAL_POST_TYPE,
					'posts_per_page' => - 1,
				)
			)
		);
	}
}


class Amapress_MailingSystems {
	/** @var Amapress_MailingSystem[] */
	private static $systems = null;

	public static function getSystems() {
		if ( self::$systems == null ) {
			self::$systems = apply_filters( 'amapress_get_mailinglist_systems', array() );
		}

		return self::$systems;
	}

	/** @var Amapress_MailingList[] */
	private static $mailinglists = null;

	public static function getMailingLists() {
		if ( self::$mailinglists == null ) {
			self::$mailinglists = array();
			foreach ( self::getSystems() as $s ) {
				foreach ( $s->getMailingLists() as $m ) {
					self::$mailinglists[] = $m;
				}
			}
		}

		return self::$mailinglists;
	}

	/** @return int */
	public function getMailWaitingModerationCount() {
		$sum = 0;
		foreach ( self::getSystems() as $m ) {
			$sum += $m->getMailWaitingModerationCount();
		}

		return $sum;
	}

	/** @return Amapress_MailingList */
	public static function getMailingListByName( $name ) {
		$mls = array_filter( self::getMailingLists(), function ( $m ) use ( $name ) {
			/** @var Amapress_MailingList $m */
			return $m->getFullName() == $name || $m->getId() == $name;
		} );
		if ( empty( $mls ) ) {
			return null;
		}

		return array_shift( $mls );
	}

}

class Amapress_MailingSystem {
	function __construct() {
		$this->mailinglists = null;
	}

	/** @var Amapress_MailingList[] */
	private $mailinglists = null;

	/** @return Amapress_MailingList[] */
	public function getMailingLists() {
		if ( $this->mailinglists == null ) {
			$this->mailinglists = $this->fetchMails();
		}

		return $this->mailinglists;
	}

	/** @return Amapress_MailingList[] */
	protected function fetchMails() {
	}

	/** @return Amapress_MailingList */
	public function getMailingList( $name ) {

	}

	public function getCreationLink() {

	}

	protected $error_message = false;

	public function getErrorMessage() {
		if ( empty( $this->error_message ) || $this->error_message === false ) {
			return '';
		}

		return $this->error_message;
	}

	/** @return int */
	public function getMailWaitingModerationCount() {
		$sum = 0;
		foreach ( self::getMailingLists() as $m ) {
			$sum += $m->getMailWaitingModerationCount();
		}

		return $sum;
	}

	public function handleMessagesModeration() {
		return false;
	}
}

class Amapress_MailingList {
	/** @var  array $info */
	protected $info;
	private $system;

	/** @return Amapress_MailingSystem */
	public function getSystem() {
		return $this->system;
	}

	/**
	 * @param string $name
	 * @param array $info
	 * @param Amapress_MailingSystem $system
	 */
	function __construct( $name, $info, $system ) {
		$this->system = $system;
		$this->info   = wp_parse_args(
			$info,
			array(
				'name'              => $name,
				'desc'              => '',
				'waiting'           => 0,
				'bounce_rate'       => 0,
				'reply_to'          => null,
				'reply_to_options'  => array(),
				'members_count'     => 0,
				'members'           => array(),
				'messages'          => null,
				'moderators'        => array(),
				'moderators_emails' => array(),
				'moderation'        => 'none',
				'moderations'       => array( 'none' => 'Aucune' ),
			)
		);
	}

	public static function getSqlQuery( $queries ) {
		global $wpdb;

		if ( empty( $queries ) || count( $queries ) == 0 ) {
			return "SELECT user_email as email
                    FROM {$wpdb->users} WHERE 1=0";
		}

		$queries = array_reverse( $queries );

		$sql_queries  = array_map( function ( $q ) {
			$args = wp_parse_args( $q,
				array(
					'fields'      => 'ID',
					'count_total' => false,
				)
			);
			$qq   = new WP_User_Query();
			$qq->prepare_query( $args );

			return "SELECT user_email as email $qq->query_from $qq->query_where";
		}, $queries );
		$sql_queries2 = array_map( function ( $q ) {
			global $wpdb;
			$args = wp_parse_args( $q,
				array(
					'fields'      => 'ID',
					'count_total' => false,
				)
			);
			$qq   = new WP_User_Query();
			$qq->prepare_query( $args );

			return "SELECT meta_value as email FROM $wpdb->usermeta WHERE meta_key IN ('email2','email3','email4') AND TRIM(IFNULL(meta_value,'')) <> '' AND user_id IN (SELECT ID $qq->query_from $qq->query_where)";
		}, $queries );

		return implode( ' UNION ', array_merge( $sql_queries, $sql_queries2 ) );
	}

	public function getName() {
		return $this->info['name'];
	}

	/** @return string */
	public function getFullName() {

	}

	/** @return string */
	public function getId() {
		return $this->getFullName();
	}

	public function getDescription() {
		return $this->info['desc'];
	}

	/** @return int */
	public function getMailWaitingModerationCount() {
		return $this->info['waiting'];
	}

	/** @return string */
	public function getConfigurationLink() {

	}

	/** @return string */
	public function getModerationLink() {

	}

	/** @return string */
	public function getMembersLink() {

	}

	public function getMembers() {
		return $this->info['members'];
	}

	public function getMembersCount() {
		return $this->info['members_count'];
	}

	/** @return Amapress_MailingList_Mail[] */
	public function getMailWaitingModeration() {
	}


	public function getModerationModes() {
		return $this->info['moderations'];
	}

	public function getModerationMode() {
		return $this->info['moderation'];
	}

	public function setModerationMode( $value ) {
		$this->info['moderation'] = $value;
	}

	public function setModerators( $value ) {
		if ( empty( $value ) ) {
			$value = array();
		}
		$this->info['moderators']        = array();
		$this->info['moderators_emails'] = array();
		foreach ( $value as $user_id ) {
			$u = amapress_get_user_by_id_or_archived( $user_id );
			if ( $u ) {
				$amapien                         = AmapressUser::getBy( $u );
				$this->info['moderators'][]      = $u->ID;
				$this->info['moderators_emails'] = array_merge( $this->info['moderators_emails'], $amapien->getAllEmails() );
			}
		}
		$this->info['moderators']        = array_unique( $this->info['moderators'] );
		$this->info['moderators_emails'] = array_unique( $this->info['moderators_emails'] );
	}

	public function getModeratorsIds() {
		return $this->info['moderators'];
	}

	public function getModeratorsEmails() {
		return $this->info['moderators_emails'];
	}

	public function getModerationModeName() {
		$modes = $this->getModerationModes();

		return isset( $modes[ $this->getModerationMode() ] ) ? $modes[ $this->getModerationMode() ] : $this->getModerationMode();
	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 */
	public function syncMembers( $config ) {

	}

	/**
	 * @param Amapress_MailingListConfiguration $config
	 *
	 * @return string
	 */
	public function isSync( $config ) {

	}

	public function getModerationControl() {

	}

	public function distributeMail( $msg_id ) {

	}

	public function rejectMailQuiet( $msg_id ) {

	}

	public function rejectMail( $msg_id ) {

	}

	public function getBounceRate() {
		return $this->info['bounce_rate'];
	}

	public function getBouncesLink() {
		return '#';
	}

	public function getReplyTo() {
		return $this->info['reply_to'];
	}

	public function getReplyToOptions() {
		return $this->info['reply_to_options'];
	}

	public function setReplyTo( $value ) {
		$this->info['reply_to'] = $value;
	}
}

class Amapress_MailingList_Mail {
	/** @var  array $info */
	protected $id;
	protected $info;

	function __construct( $id, $info ) {
		$this->info = wp_parse_args(
			$info,
			array(
				'id'      => $id,
				'headers' => array(),
				'content' => '',
			)
		);
		foreach ( $this->info['headers'] as $k => $v ) {
			$this->info['headers'][ strtolower( $k ) ] = $v;
		}
	}

	public function getId() {
		return $this->info['id'];
	}

	public function getHeader( $name, $default = '' ) {
		return ! empty( $this->info['headers'][ $name ] ) ? $this->info['headers'][ $name ] : $default;
	}

	public function getHeaders() {
		return $this->info['headers'];
	}

	public function getContent() {
		return $this->info['content'];
	}
}