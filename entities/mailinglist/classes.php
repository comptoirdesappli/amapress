<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Amapress_MailingListConfiguration extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_mailing';
	const POST_TYPE = 'mailinglist';

	//const CATEGORY = 'amps_amap_event_category';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return Amapress_MailingListConfiguration
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'Amapress_MailingListConfiguration' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new Amapress_MailingListConfiguration( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getName() {
		return $this->getCustomAsString( 'amapress_mailinglist_name' );
	}

	public function getDescription() {
		return $this->getCustomAsString( 'amapress_mailinglist_desc' );
	}

	public function getAddress() {
		$name = $this->getName();

		return explode( ':', $name )[1];
	}

	public function getAdminMembersLink() {
		return admin_url( 'users.php?amapress_mllst_id=' . $this->ID );
	}

	public function getMembersQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailinglist_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailinglist_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		$users = array_map( 'intval', Amapress::get_array( Amapress::getOption( 'mailing_other_users' ) ) );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		return $ret;
	}

	public function getRawEmails() {
		$raw_emails = $this->getCustomAsString( 'amapress_mailinglist_raw_users' );
		if ( ! empty( $raw_emails ) ) {
			$raw_emails = preg_replace( '/\s+/', ',', $raw_emails );
			$raw_emails = explode( ',', $raw_emails );

			return array_filter( $raw_emails, function ( $e ) {
				return ! empty( $e );
			} );
		}

		return [];
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

	public function getExcludeMembersQueries() {
		$ret   = $this->getCustomAsArray( 'amapress_mailinglist_excl_queries' );
		$users = $this->getCustomAsIntArray( 'amapress_mailinglist_excl_other_users' );
		if ( ! empty( $users ) && count( $users ) > 0 ) {
			$ret[] = array( 'include' => $users );
		}

		return $ret;
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

abstract class Amapress_MailingSystem {
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

	public function getSystemId() {
		return '';
	}

	public function getSystemName() {
		return '';
	}
}

abstract class Amapress_MailingList {
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
				'moderators_found'  => false,
				'moderation'        => 'none',
				'moderations'       => array( 'none' => __( 'Aucune', 'amapress' ) ),
			)
		);
	}

	public static function getSqlUnionQuery( $queries ) {
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

	public static function normalizeEmailsArray( $emails ) {
		$ret = array_map( function ( $email ) {
			return strtolower( $email );
		}, $emails );
		$ret = array_filter( $ret, function ( $email ) {
			return false === strpos( $email, '@nomail.org' )
			       && false !== strpos( $email, '@' );
		} );

		return $ret;
	}

	public static function getSqlQuery( $queries, $exclude_queries ) {
		if ( empty( $queries ) ) {
			global $wpdb;

			return "SELECT user_email as email
                    FROM {$wpdb->users} WHERE 1=0";
		}

		$include_sql_query = self::getSqlUnionQuery( $queries );
		if ( empty( $exclude_queries ) ) {
			return $include_sql_query;
		}

		$exclude_sql_query = self::getSqlUnionQuery( $exclude_queries );

		return "SELECT email FROM ($include_sql_query) as inc WHERE inc.email NOT IN ($exclude_sql_query)";
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
	public function getModeratorsLink() {

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

	public function handleModerators() {
		return $this->info['moderators_found'];
	}

	public function handleModerationSetting() {
		return true;
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

abstract class Amapress_Sympa_MailingList extends Amapress_MailingList {
	/** @return  Amapress_Sympa_MailSystem */
	public function getSystem() {
		return parent::getSystem();
	}

	function __construct( $name, $info, Amapress_Sympa_MailSystem $system ) {
		parent::__construct( $name, $info, $system );
	}

	public function getConfigurationLink() {
		return $this->getSystem()->getMailingListBaseUrl() . "edit_list_request/{$this->getName()}";
	}

	public function getMembersLink() {
		return $this->getSystem()->getMailingListBaseUrl() . "review/{$this->getName()}";
	}

	public function getModeratorsLink() {
		return $this->getSystem()->getMailingListBaseUrl() . "review/{$this->getName()}/editor";
	}

	public function getBouncesLink() {
		return $this->getSystem()->getMailingListBaseUrl() . "reviewbouncing/{$this->getName()}";
	}


	public function getModerationLink() {
		return $this->getSystem()->getMailingListBaseUrl() . "modindex/{$this->getName()}";
	}

	public function setModerationMode( $value ) {
		parent::setModerationMode( $value );

		$this->getSystem()->setModeration( $value, $this->getName() );
	}

	public function setReplyTo( $value ) {
		parent::setReplyTo( $value );

		$this->getSystem()->setReplyTo( $value, $this->getName() );
	}

	public function setModerators( $value ) {
		$this->getSystem()->setModerators( $value, $this->getModeratorsEmails(), $this->getName() );
		parent::setModerators( $value );
	}

	public function getModerationControl() {

	}

	public function distributeMail( $msg_id ) {
		return $this->getSystem()->distributeMail( $this->getName(), $msg_id );
	}

	public function rejectMailQuiet( $msg_id ) {
		return $this->getSystem()->rejectMailQuiet( $this->getName(), $msg_id );
	}

	public function rejectMail( $msg_id ) {
		return $this->getSystem()->rejectMail( $this->getName(), $msg_id );
	}

	/** @return Amapress_MailingList_Mail[] */
	public function getMailWaitingModeration() {
		if ( ! isset( $this->info['messages'] ) ) {
			if ( $this->getMailWaitingModerationCount() > 0 ) {
				$this->info['messages'] = $this->getSystem()->getMailWaitingModeration( $this->getName() );
			} else {
				$this->info['messages'] = array();
			}
		}

		return $this->info['messages'];
	}

	public function getFullName() {
		return $this->getSystem()->getFullName( $this->getName() );
	}

	public function getId() {
		return $this->getSystem()->getId( $this->getName() );
	}
}

abstract class Amapress_Sympa_MailSystem extends Amapress_MailingSystem {
	public function handleMessagesModeration() {
		return true;
	}

	public static function parseSelect( $html_content, $select_name, &$options ) {
		$options = array();
		preg_match( '%\<select\s+name\="' . $select_name . '".+?\<\/select\>%s', $html_content, $m );
		if ( empty( $m[0] ) ) {
			return null;
		}
		$options_matches = array();
		preg_match_all( '%\<option\s+value\="(?<value>[^"]+)"(?<selected>\s+selected\="selected")?[^\>]*\>(?<text>[^\<]+)\<\/option\>%s', $m[0], $options_matches, PREG_SET_ORDER );
		$selected = null;
		foreach ( $options_matches as $opt ) {
			if ( ! empty( $opt['selected'] ) ) {
				$selected = html_entity_decode( $opt['value'] );
			}
			$options[ html_entity_decode( $opt['value'] ) ] = html_entity_decode( $opt['text'] );
		}

		return $selected;
	}

	/** @var  \GuzzleHttp\Client $client */
	protected static $client;
	protected $mailinglist_domain;
	protected $protocol;
	protected $system_id;
	protected $is_connected = false;
	protected $csrftoken = null;
	protected $manage_waiting = false;

	public function getMailingListBaseUrl() {
		return "{$this->protocol}://{$this->mailinglist_domain}/wws/";
	}

	public function getCreationLink() {
		return $this->getMailingListBaseUrl() . 'create_list_request';
	}

	public function getFullName( $name ) {
		return "$name@{$this->mailinglist_domain}";
	}

	public function getId( $name ) {
		return "{$this->system_id}:{$this->getFullName($name)}";
	}

	public function getListsUri() {
		return 'lists';
	}

	protected function fetchMails() {
		if ( $this->error_message !== false ) {
			return array();
		}

		$ret = array();

		$resp = self::$client->get( $this->getListsUri() );
		if ( 200 == $resp->getStatusCode() ) {
			$body  = $resp->getBody();
			$lists = array();
			preg_match_all( '%\<li\s+class\="listenum"\>.+?\<\/li\>%s', $body, $lists, PREG_SET_ORDER );
			foreach ( $lists as $list ) {
				if ( preg_match( '/href\="\/(?:sympa|wws)\/admin\/(?<name>[^"]+)"/', $list[0], $m ) ) {
					$ret[] = $this->getMailingList( $m['name'] );
				}
				if ( preg_match( '/href\="\/(?:sympa|wws)\/+info\/(?<name>[^"]+)"/', $list[0], $m ) ) {
					$ret[] = $this->getMailingList( $m['name'] );
				}
			}
			if ( empty( $ret ) ) {
				preg_match_all( '/href\="\/(?:sympa)\/review\/(?<name>[^"]+)"/', $body, $lists, PREG_SET_ORDER );
				foreach ( $lists as $list ) {
					$ret[] = $this->getMailingList( $list['name'] );
				}
			}
		}

		return $ret;
	}

	function __construct( $mailinglist_domain, $login, $pass, $protocol, $system_id, $manage_waiting ) {
		parent::__construct();
		require_once AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php';

		$this->mailinglist_domain = $mailinglist_domain;
		$this->protocol           = $protocol;
		$this->system_id          = $system_id;
		$this->manage_waiting     = $manage_waiting;

		$this->ensureConnected( $login, $pass );
	}

	public function isConnected() {
		return $this->is_connected;
	}

	public function getCSRFToken() {
		return $this->csrftoken;
	}

	protected function ensureConnected( $login, $pass ) {
		if ( self::$client == null ) {
			$cookies = new \GuzzleHttp\Cookie\CookieJar();
			//$jar->add
			self::$client = new \GuzzleHttp\Client(
				array(
					'base_uri' => $this->getMailingListBaseUrl(),
					'cookies'  => $cookies,
					'verify'   => ! defined( 'AMAPRESS_VALIDATE_SSL' ) || AMAPRESS_VALIDATE_SSL,
				) );
			$form_params  = array(
				'action'       => 'login',
				'action_login' => __( 'Login', 'amapress' ),
				'email'        => $login,
				'passwd'       => $pass,
			);

			$resp = self::$client->get( '' );
			$body = $resp->getBody();
			if ( preg_match( '/type="hidden" name="csrftoken" value="(?<csrftoken>[^"]+)"/', $body, $m ) ) {
				$this->csrftoken = $m['csrftoken'];
				$form_params     = array_merge( $form_params,
					array(
						'csrftoken'       => $this->getCSRFToken(),
						'previous_action' => 'home',
						'previous_list'   => '',
						'only_passwd'     => '',
						'referer'         => '',
						'failure_referer' => '',
						'list'            => '',
						'nomenu'          => '',
						'submit'          => 'submit',
					) );
			}

			$resp               = self::$client->post( '',
				[
					'form_params' => $form_params
				]
			);
			$this->is_connected = false;
			if ( 200 == $resp->getStatusCode() ) {
				$this->error_message = false;
				$body                = $resp->getBody();
				if ( preg_match( '/type="hidden" name="action" value="logout"/', $body, $m ) ) {
					$this->is_connected = true;
				}
			} else {
				$this->error_message = $resp->getReasonPhrase();
			}
		}
	}

	public function setSqlDataSource( $sql_query, $list_name ) {
		$resp = self::$client->get( "edit_list_request/$list_name/data_source" );
		$body = $resp->getBody();
		preg_match( '/type="hidden" name="serial" value="(?<serial>\d+)"/', $body, $m );

		$post_data = array(
			'serial'                                           => $m['serial'],
			'single_param.user_data_source'                    => 'include2',
			'single_param.include_sql_query.0.name'            => DB_USER,
			'single_param.include_sql_query.0.db_type'         => 'mysql',
			'single_param.include_sql_query.0.host'            => DB_HOST,
			'single_param.include_sql_query.0.db_name'         => DB_NAME,
			'single_param.include_sql_query.0.db_host'         => '',
			'single_param.include_sql_query.0.db_user'         => '',
			'single_param.include_sql_query.0.db_passwd'       => '',
			'single_param.include_sql_query.0.db_port'         => '',
			'single_param.include_sql_query.0.connect_options' => '',
			'single_param.include_sql_query.0.db_env'          => '',
			'single_param.include_sql_query.0.user'            => DB_USER,
			'single_param.include_sql_query.0.passwd'          => DB_PASSWORD,
			'single_param.include_sql_query.0.sql_query'       => preg_replace( '/\s/', ' ', $sql_query ),
			'list'                                             => $list_name,
			'group'                                            => 'data_source',
			'action'                                           => 'edit_list',
			'action_edit_list'                                 => __( 'Mise à jour', 'amapress' ),
		);
		$resp      = self::$client->post( '', [
			'form_params' => $post_data
		] );
		$body      = $resp->getBody();

		return strpos( $body, __( 'La configuration de la liste a été mise à jour', 'amapress' ) ) !== false;
	}

	public function setRemoteUrl( $list_name, $remote_url ) {
		$resp = self::$client->get( "edit_list_request/$list_name/data_source" );
		$body = $resp->getBody();
		preg_match( '/type="hidden" name="serial" value="(?<serial>\d+)"/', $body, $m );

//        var_dump($sql_query);
		$post_data = array(
			'serial'                                  => $m['serial'],
			'single_param.include_remote_file.0.name' => 'Amapress',
			'single_param.include_remote_file.0.url'  => $remote_url,
			'list'                                    => $list_name,
			'group'                                   => 'data_source',
			'action'                                  => 'edit_list',
			'action_edit_list'                        => 'Mise à jour',
		);
		$resp      = self::$client->post( '', [
			'form_params' => $post_data
		] );
		$body      = $resp->getBody();

		return strpos( $body, __( 'La configuration de la liste a été mise à jour', 'amapress' ) ) !== false;
	}

	public function setModeration( $moderation, $list_name ) {
		$resp = self::$client->get( "edit_list_request/$list_name/sending" );
		$body = $resp->getBody();
		preg_match( '/type="hidden" name="serial" value="(?<serial>\d+)"/', $body, $m );

		$post_data = array(
			'serial'                 => $m['serial'],
			'single_param.send.name' => $moderation,
			'list'                   => $list_name,
			'group'                  => 'sending',
			'action'                 => 'edit_list',
			'action_edit_list'       => __( 'Mise à jour', 'amapress' ),
		);
		$resp      = self::$client->post( '', [
			'form_params' => $post_data
		] );
		$body      = $resp->getBody();

		return strpos( $body, __( 'La configuration de la liste a été mise à jour', 'amapress' ) ) !== false;
	}

	public function setReplyTo( $reply_to, $list_name ) {
		$resp = self::$client->get( "edit_list_request/$list_name/sending" );
		$body = $resp->getBody();
		preg_match( '/type="hidden" name="serial" value="(?<serial>\d+)"/', $body, $m );

		$post_data = array(
			'serial'                             => $m['serial'],
			'single_param.reply_to_header.value' => $reply_to,
			'list'                               => $list_name,
			'group'                              => 'sending',
			'action'                             => 'edit_list',
			'action_edit_list'                   => __( 'Mise à jour', 'amapress' ),
		);
		$resp      = self::$client->post( '', [
			'form_params' => $post_data
		] );
		$body      = $resp->getBody();

		return strpos( $body, __( 'La configuration de la liste a été mise à jour', 'amapress' ) ) !== false;
	}

	public function setModerators( $new_moderators, $old_moderators_emails, $list_name ) {
		$resp = self::$client->get( "edit_list_request/$list_name/description" );
		$body = $resp->getBody();
		preg_match( '/type="hidden" name="serial" value="(?<serial>\d+)"/', $body, $m );

		$post_data = array(
			'serial'           => $m['serial'],
			'list'             => $list_name,
			'group'            => 'description',
			'action'           => 'edit_list',
			'action_edit_list' => __( 'Mise à jour', 'amapress' ),
		);

		$j = 0;
		for ( $i = 0; $i < count( $new_moderators ); $i ++ ) {
			$mail = amapress_get_user_by_id_or_archived( $new_moderators[ $i ] );
			if ( $mail ) {
				$amapien = AmapressUser::getBy( $mail );
				foreach ( $amapien->getAllEmails() as $email ) {
					$post_data["single_param.owner.$j.email"]     = $email;
					$post_data["single_param.owner.$j.gecos"]     = '';
					$post_data["single_param.owner.$j.info"]      = '';
					$post_data["single_param.owner.$j.profile"]   = 'privileged';
					$post_data["single_param.owner.$j.reception"] = 'mail';
					$j ++;
				}
			}
		}
		while ( $j < count( $old_moderators_emails ) ) {
			$post_data["single_param.owner.$j.email"]     = '';
			$post_data["single_param.owner.$j.gecos"]     = '';
			$post_data["single_param.owner.$j.info"]      = '';
			$post_data["single_param.owner.$j.profile"]   = 'privileged';
			$post_data["single_param.owner.$j.reception"] = 'mail';
			$j ++;
		}

//        var_dump($sql_query);

		$resp = self::$client->post( '', [
			'form_params' => $post_data
		] );
		$body = $resp->getBody();

		return strpos( $body, __( 'La configuration de la liste a été mise à jour', 'amapress' ) ) !== false;
	}

	public function distributeMail( $list_name, $msg_id ) {
		$resp = self::$client->post( '', [
			'form_params' => array(
				'list'              => $list_name,
				'id'                => $msg_id,
				'action_distribute' => __( 'Distribuer', 'amapress' ),
			)
		] );

		return 200 == $resp->getStatusCode();
	}

	public function rejectMailQuiet( $list_name, $msg_id ) {
		$resp = self::$client->post( '', [
			'form_params' =>
				array(
					'list'                => $list_name,
					'id'                  => $msg_id,
					'action_reject.quiet' => __( 'Rejeter sans prévenir l\'auteur', 'amapress' ),
				)
		] );

		return 200 == $resp->getStatusCode();
	}

	public function rejectMail( $list_name, $msg_id ) {
		$resp = self::$client->post( '', [
			'form_params' =>
				array(
					'list'          => $list_name,
					'id'            => $msg_id,
					'action_reject' => __( 'Rejeter', 'amapress' ),
				)
		] );

		return 200 == $resp->getStatusCode();
	}

	/** @return Amapress_MailingList_Mail[] */
	public function getMailWaitingModeration( $name ) {
		if ( ! $this->manage_waiting ) {
			return [];
		}

		$resp = self::$client->get( "modindex/$name" );
		if ( 200 == $resp->getStatusCode() ) {
			$body = $resp->getBody();

			$message_matches = array();
			preg_match_all( '%href\="\/(?:sympa|wws)\/viewmod\/' . $name . '\/(?<msg_id>[^"]+)"%s', $body, $message_matches, PREG_SET_ORDER );

			$messages = array();
			foreach ( $message_matches as $msg ) {
				$msg_id = $msg['msg_id'];
				$resp   = self::$client->get( "viewmod/$name/$msg_id" );
				if ( 200 == $resp->getStatusCode() ) {
					$body = $resp->getBody();

					$headers_matches = array();
					preg_match_all( '%\<li\>\<strong>(?<id>[^\<]+)\<\/strong\>:\s*(?<content>[^\<]+)\<\/li\>%s', $body, $headers_matches, PREG_SET_ORDER );

					$headers = array();
					foreach ( $headers_matches as $h ) {
						$headers[ $h['id'] ] = $h['content'];
					}

					preg_match( '/\<\!--X-Body-of-Message--\>(?<body>.+?)\<\!--X-Body-of-Message-End--\>/s', $body, $content );
					$messages[] = new Amapress_MailingList_Mail( $msg_id,
						array(
							'id'      => $msg_id,
							'headers' => $headers,
							'content' => html_entity_decode( $content['body'] ),
						) );
				}
			}

			return $messages;
		}

		return array();
	}
}
