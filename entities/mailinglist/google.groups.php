<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Amapress_GoogleGroups_MailingList extends Amapress_MailingList {
	/** @return  Amapress_GoogleGroups_MailSystem */
	public function getSystem() {
		return parent::getSystem();
	}

	function __construct( $name, $info, Amapress_Ouvaton_MailSystem $system ) {
		parent::__construct( $name, $info, $system );
	}

	public function getConfigurationLink() {
		return $this->getSystem()->getMailingListBaseUrl() . "edit_list_request/{$this->getName()}";
	}

	public function getModerationLink() {
		return $this->getSystem()->getMailingListBaseUrl() . "modindex/{$this->getName()}";
	}

	public function setModerationMode( $value ) {
		parent::setModerationMode( $value );

		$this->getSystem()->setModeration( $value, $this->getName() );
	}

	public function setModerators( $value ) {
		parent::setModerators( $value );

		$this->getSystem()->setModerators( $value, $this->getName() );
	}

//
//    public function isSync($queries)
//    {
//        global $wpdb;
//        $sql_query = isset($this->info['query']) ? $this->info['query'] : '';
//        if (!empty($sql_query)) {
//            $new_query = $this->getSqlQuery($queries);
//            if (empty($new_query)) return 'manual';
//            $new_users = $wpdb->get_col($new_query);
//            $old_users = $wpdb->get_col($sql_query);
//            $inter = array_intersect($new_users, $old_users);
//            if (count($inter) == count($old_users) && count($inter) == count($new_users))
//                return 'sync';
//            else
//                return 'not_sync';
//        } else {
//            return 'manual';
//        }
//    }

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
		return array();
	}

	public function getFullName() {
		return $this->getSystem()->getFullName( $this->getName() );
	}

	public function getId() {
		return $this->getSystem()->getId( $this->getName() );
	}
}

class Amapress_GoogleGroups_MailSystem extends Amapress_MailingSystem {
	/** @var  Google_Client $client */
	private static $client;
	private $key;

	public function getMailingListBaseUrl() {
		return "http://{$this->mailinglist_domain}/wws/";
	}

	public function getFullName( $name ) {
		return "$name@googlegroups.com";
	}

	public function getId( $name ) {
		return "ggroups:{$this->getFullName($name)}";
	}

	public function getMailingList( $name ) {
		return null;
	}

	/**
	 *
	 */
	protected function fetchMails() {
		$client = new Google_Client();
		$client->setApplicationName( "Amapress" );
		$client->setDeveloperKey( $this->key );

		$groups = new Google_Service_Directory_Groups( $client );
		//var_dump( $groups->getGroups() );
		//die();
	}

	function __construct( $key ) {
		parent::__construct();
		require_once AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php';

		$this->key = $key;
	}

	public function setSqlDataSource( $sql_query, $list_name ) {
	}

	public function setModeration( $moderation, $list_name ) {
	}

	public function setModerators( $moderators, $list_name ) {
	}

	public function distributeMail( $list_name, $msg_id ) {
	}

	public function rejectMailQuiet( $list_name, $msg_id ) {
	}

	public function rejectMail( $list_name, $msg_id ) {
	}

	/** @return Amapress_MailingList_Mail[] */
	public function getMailWaitingModeration( $name ) {
		return array();
	}
}

//add_filter('amapress_get_mailinglist_systems', 'amapress_ggroups_get_mailinglist_systems');
//function amapress_ggroups_get_mailinglist_systems($systems) {
//    $key = Amapress::getOption('google_map_key');
//    if (empty($key)) return $systems;
//        $systems[] = new Amapress_GoogleGroups_MailSystem($key);
//    return $systems;
//}