<?php

class Amapress_EventBase extends TitanEntity {
	const INTERNAL_POST_TYPE = '';
	const POST_TYPE = '';

	/** @return int */
	public function getStartDateAndHour() {
		return 0;
	}

	/** @return int */
	public function getEndDateAndHour() {
		return 0;
	}

	/**
	 * @param Amapress_EventBase[] $events
	 * @param string $order
	 */
	public static function sort_events( &$events, $order = 'ASC' ) {
		$order_num = ( $order == 'ASC' ? 1 : - 1 );
		usort( $events, function ( Amapress_EventBase $a, Amapress_EventBase $b ) use ( $order_num ) {
			$da = $a->getDefaultSortValue();
			$db = $b->getDefaultSortValue();
			if ( $da < $db ) {
				return $order_num * - 1;
			}
			if ( $da > $db ) {
				return $order_num * 1;
			}

			return 0;
		} );
	}

	/**
	 * @param array $meta_query
	 * @param string $order
	 *
	 * @return Amapress_EventBase[]
	 */
	public static function query_events( $meta_query, $order = 'NONE' ) {
		$k   = md5( serialize( $meta_query ) );
		$key = "AmasEventBase_query_events-{$k}";
		$ret = wp_cache_get( $key );
		if ( false === $ret ) {
			$class = get_called_class();
			$ret   = array_map( function ( $p ) use ( $class ) {
				return new $class( $p );
			},
				get_posts( array(
					'posts_per_page' => - 1,
					'post_type'      => static::INTERNAL_POST_TYPE,
					'meta_query'     => $meta_query,
					'orderby'        => 'none'
				) ) );
			wp_cache_set( $key, $ret );
		}
		if ( $order != 'NONE' ) {
			self::sort_events( $ret, $order );
		}

		return $ret;
	}

	public function isPublicEvent() {
		$pt = static::POST_TYPE;

		return $this->getCustomAsInt( "amapress_{$pt}_public" );
	}

	/** @return Amapress_EventEntry */
	public function get_related_events( $user_id ) {
		return array();
	}

	public function getLieuInformation() {
		return '';
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_recall = true ) {
		return Amapress::getPlaceholdersHelpTable( 'event-placeholders',
			Amapress::getPlaceholdersHelpForProperties( self::getProperties() ), 'de l\'évènement',
			$additional_helps, $for_recall ? 'recall' : true );
	}

	public static function getProperties() {
		return [
			'evenement'                      => [
				'desc' => __( 'Nom de l\'évènement', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return esc_html( $ev->getTitle() );
				}
			],
			'lien-evenement'                 => [
				'desc' => __( 'Lien vers la présentation de l\'évènement', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return Amapress::makeLink( $ev->getPermalink() );
				}
			],
			'lien-evenement-ical'            => [
				'desc' => __( 'Lien ical de l\'évènement', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return add_query_arg( 'events_id', $ev->ID, Amapress_Agenda_ICAL_Export::get_link_href() );
				}
			],
			'lieu-info'                      => [
				'desc' => __( 'Information sur le lieu', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getLieuInformation();
				}
			],
			'horaires-evenement'             => [
				'desc' => __( 'Date et horaires évènement', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return sprintf( __( '%s de %s à %s', 'amapress' ),
						date_i18n( 'D j F Y', $ev->getStartDateAndHour() ),
						date_i18n( 'H:i', $ev->getStartDateAndHour() ),
						date_i18n( 'H:i', $ev->getEndDateAndHour() ) );
				}
			],
			'amapiens-inscrits-liste'        => [
				'desc' => __( 'Amapiens inscrits (en liste à virgules)', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getInscritsList( true );
				}
			],
			'amapiens-creneaux-liste'        => [
				'desc' => __( 'Amapiens inscrits (ordre des créneaux)', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getInscritsList( false );
				}
			],
			'amapiens-creneaux-table'        => [
				'desc' => __( 'Amapiens inscrits (ordre des créneaux)', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getInscritsTable( false );
				}
			],
			'amapiens-creneaux-table-coords' => [
				'desc' => __( 'Amapiens inscrits (ordre des créneaux) avec coordonnées', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getInscritsTable( false, true );
				}
			],
			'amapiens-inscrits-table'        => [
				'desc' => __( 'Amapiens inscrits (ordre alphabétique)', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getInscritsTable( false );
				}
			],
			'amapiens-inscrits-table-coords' => [
				'desc' => __( 'Amapiens inscrits (ordre alphabétique) avec coordonnées', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getInscritsTable( false, true );
				}
			],
			'creneaux-table'                 => [
				'desc' => __( 'Créneaux choisis par les amapiens', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return $ev->getSlotsTable();
				}
			],
			'creneaux-liste'                 => [
				'desc' => __( 'Créneaux choisis par les amapiens', 'amapress' ),
				'func' => function ( Amapress_EventBase $ev ) {
					return implode( ', ', array_map( function ( $s ) {
						return $s['display'];
					}, $ev->getSlotsConf() ) );
				}
			],
		];
	}

	public function getSlotsDescription() {
		$slots = $this->getSlotsConf();
		if ( empty( $slots ) ) {
			return 'pas de créneaux horaires configurés';
		} else {
			return sprintf( __( '%d créneau(x) ; %s', 'amapress' ),
				count( $slots ),
				implode( ', ', array_map( function ( $s ) {
					return $s['display'];
				}, $slots ) ) );
		}
	}

	public function getSlotsConf() {
		$cache_key = static::INTERNAL_POST_TYPE . 'getSlotsConf-' . $this->ID;
		$res       = wp_cache_get( $cache_key );
		if ( false === $res ) {
			$active_slots = $this->getSlots();
			$key          = 'amapress_' . static::POST_TYPE . '_slots_conf';
			$slots_conf   = $this->getCustomAsString( $key, Amapress::getOption( $key ) );
			$res          = [];
			if ( ! empty( $slots_conf ) ) {
				foreach ( explode( '|', $slots_conf ) as $conf ) {
					$m = array();
					//18h00-20h00[10m;2p]|
					if ( preg_match( '/(?:(?<start_h>\d{1,2})\s*h\s*(?<start_m>\d{2})?\s*-\s*(?<end_h>\d{1,2})\s*h\s*(?<end_m>\d{2})?)?(?:\s*\(\s*(?<anon>\()?\s*(?<name>[^\)]+)\s*\)\s*\)?\s*)?(?:\s*\[\s*(?<inter>\d+)\s*m(?:i?n)?\s*(?:\s*[,;]\s*(?<max>\d+)\s*p)?\s*\])?/', $conf, $m ) !== false ) {
						if ( empty( $m['start_h'] ) ) {
							$m['start_h'] = intval( date( 'H', $this->getStartDateAndHour() ) );
							$m['start_m'] = intval( date( 'i', $this->getStartDateAndHour() ) );
						}
						if ( empty( $m['end_h'] ) ) {
							$m['end_h'] = intval( date( 'H', $this->getEndDateAndHour() ) );
							$m['end_m'] = intval( date( 'i', $this->getEndDateAndHour() ) );
						}
						$start_h = intval( ltrim( $m['start_h'], '0' ) );
						$start_m = isset( $m['start_m'] ) ? intval( ltrim( $m['start_m'], '0' ) ) : 0;
						$end_h   = intval( ltrim( $m['end_h'], '0' ) );
						$end_m   = isset( $m['end_m'] ) ? intval( ltrim( $m['end_m'], '0' ) ) : 0;

						$inter = isset( $m['inter'] ) ? intval( $m['inter'] ) : 0;
						$max   = isset( $m['max'] ) ? intval( $m['max'] ) : 0;
						$name  = ! empty( $m['name'] ) ? $m['name'] : '';
						$anon  = ! empty( $m['anon'] );

						$dt_start = new DateTime();
						$dt_start->setTimestamp( $this->getStartDateAndHour() );
						$dt_start->setTime( $start_h, $start_m );
						$dt_end = new DateTime();
						$dt_end->setTimestamp( $this->getStartDateAndHour() );
						$dt_end->setTime( $end_h, $end_m );

						while ( $dt_start < $dt_end ) {
							$inter_start = $dt_start->getTimestamp();
							if ( $inter > 0 ) {
								$dt_start->modify( "+{$inter} minutes" );
							} else {
								$dt_start = $dt_end;
							}
							$inter_end = $dt_start->getTimestamp();
							$key       = strval( $inter_start );
							if ( isset( $res[ $key ] ) ) {
								$key = strval( $inter_start ) . '-' . strval( $inter_end );
							}
							$current_usage = 0;
							foreach ( $active_slots as $s ) {
								$current_usage += ( $s == $key ? 1 : 0 );
							}
							$display_hours = date_i18n( 'H:i', $inter_start ) . '-' . date_i18n( 'H:i', $inter_end );
							$res[ $key ]   = [
								'display'  => sprintf( ! empty( $name ) ? ( $anon ? '%1$s' : '%1$s (%2$s)' ) : '%2$s',
									$name, $display_hours ),
								'date'     => $inter_start,
								'date_end' => $inter_end,
								'max'      => $max,
								'current'  => $current_usage
							];
						}
					}
				}

			}
			wp_cache_set( $cache_key, $res );
		}

		return $res;
	}

	public function isMemberOf( $user_id ) {
		return true;
	}

	public function getMembersIds() {
		return [];
	}

	public function getMailEventType() {
		return static::POST_TYPE;
	}

	public function getSlots() {
		$key = 'amapress_' . static::POST_TYPE . '_slots';

		return $this->getCustomAsArray( $key );
	}

	private function setSlots( $slots ) {
		$key = 'amapress_' . static::POST_TYPE . '_slots';
		$this->setCustom( $key, $slots );
	}

	public function getSlotInfoForUser( $user_id ) {
		if ( empty( $user_id ) ) {
			$user_id = amapress_current_user_id();
		}

		$confs = $this->getSlotsConf();
		$slots = $this->getSlots();
		if ( isset( $slots["u{$user_id}"] ) ) {
			$slot = $slots["u{$user_id}"];

			return isset( $confs[ $slot ] ) ? $confs[ $slot ] : null;
		} else {
			return null;
		}
	}

	public function getUserIdsForSlot( $slot ) {
		$amapien_ids = [];
		foreach ( $this->getSlots() as $k => $v ) {
			if ( $v == $slot ) {
				$amapien_ids[] = intval( substr( $k, 1 ) );
			}
		}

		return $amapien_ids;
	}

	public function getUserIdsWithAnySlot() {
		$amapien_ids = [];
		foreach ( $this->getSlots() as $k => $v ) {
			$amapien_ids[] = intval( substr( $k, 1 ) );
		}

		return $amapien_ids;
	}

	public function getAvailableSlots() {
		return array_filter( $this->getSlotsConf(), function ( $conf ) {
			return $conf['max'] <= 0 || $conf['current'] < $conf['max'];
		} );
	}

	public function getMembersWithSlots( $order_by_slot = true ) {
		$res = [];
		foreach ( $this->getMembersIds() as $user_id ) {
			$amapien   = AmapressUser::getBy( $user_id );
			$slot_info = $this->getSlotInfoForUser( $user_id );
			$res[ sprintf( $order_by_slot ? '%1$08x-%2$s' : '%2$s-%1$08x',
				$slot_info ? $slot_info['date'] : 0,
				strtolower( $amapien->getSortableDisplayName() )
			) ]        = [
				'slot' => $slot_info,
				'user' => $amapien
			];
		}
		ksort( $res );

		return $res;
	}

	public function getWithoutSlotsMemberIds() {
		$res = [];
		foreach ( $this->getMembersIds() as $user_id ) {
			if ( empty( $this->getSlotInfoForUser( $user_id ) ) ) {
				$res[] = $user_id;
			}
		}

		return $res;
	}

	public function getInscritsList( $order_by_slot = true ) {
		return implode( ', ', array_map( function ( $member ) {
			/** @var AmapressUser $amapien */
			$amapien   = $member['user'];
			$slot_info = $member['slot'];
			if ( $slot_info ) {
				return sprintf( __( '%s (%s)', 'amapress' ),
					$amapien->getDisplayName(),
					$slot_info['display'] );
			} else {
				return $amapien->getDisplayName();
			}
		}, $this->getMembersWithSlots( $order_by_slot ) ) );
	}

	public function getInscritsTable( $order_by_slot = true, $inc_coords = false ) {
		$columns   = [];
		$columns[] = array(
			'title' => __( 'Amapien', 'amapress' ),
			'data'  => 'amapien'
		);
		if ( $inc_coords ) {
			$columns[] = array(
				'title' => __( 'Coordonnées', 'amapress' ),
				'data'  => 'coords'
			);
		}
		$columns[] = array(
			'title' => __( 'Inscription', 'amapress' ),
			'data'  => 'slot'
		);
		$data      = [];
		foreach ( $this->getMembersWithSlots( $order_by_slot ) as $member ) {
			/** @var AmapressUser $amapien */
			$amapien   = $member['user'];
			$slot_info = $member['slot'];
			$row       = [
				'amapien' => $amapien->getDisplayName(),
				'slot'    => '',
				'coords'  => '',
			];
			if ( $slot_info ) {
				$row['slot'] = $slot_info['display'];
			}
			if ( $inc_coords ) {
				$row['coords'] = $amapien->getContacts();
			}
			$data[] = $row;
		}
		$dt_options = array(
			'paging'       => false,
			'init_as_html' => true,
			'no_script'    => true,
			'bSort'        => false,
		);
		$id         = 'event-inscrits-' . $this->ID . '-' . uniqid();
		$tbl_style  = "<style>#$id table, #$id th, #$id td { border-collapse: collapse; border: 1pt solid #000; } .odd {background-color: #eee; }</style>";

		return $tbl_style . amapress_get_datatable(
				$id,
				$columns, $data,
				$dt_options );
	}

	public function getSlotsTable() {
		$columns = [
			array(
				'title' => __( 'Créneau', 'amapress' ),
				'data'  => 'slot'
			),
			array(
				'title' => __( 'Inscrits', 'amapress' ),
				'data'  => 'current'
			),
			array(
				'title' => __( 'Maximum', 'amapress' ),
				'data'  => 'max'
			),
		];

		$data = [];
		foreach ( $this->getSlotsConf() as $slot_info ) {
			$data[] = [
				'slot'    => $slot_info['display'],
				'current' => $slot_info['current'],
				'max'     => $slot_info['max'],
			];
		}
		$dt_options = array(
			'paging'       => false,
			'init_as_html' => true,
			'no_script'    => true,
			'bSort'        => false,
		);
		$id         = 'slots-' . $this->ID . '-' . uniqid();
		$tbl_style  = "<style>#$id table, #$id th, #$id td { border-collapse: collapse; border: 1pt solid #000; } .odd {background-color: #eee; }</style>";

		return $tbl_style . amapress_get_datatable(
				$id,
				$columns, $data,
				$dt_options );
	}

	public function manageSlot( $user_id, $slot, $set = true ) {
		if ( ! amapress_is_user_logged_in() ) {
			wp_die( __( 'Vous devez avoir un compte pour effectuer cette opération.', 'amapress' ) );
		}

		if ( empty( $user_id ) ) {
			$user_id = amapress_current_user_id();
		}

		if ( ! amapress_can_access_admin() ) {
			if ( ! $this->isMemberOf( $user_id ) ) {
				wp_die( __( 'Vous n\'en faites pas partie.', 'amapress' ) );
			}
			if ( Amapress::end_of_day( $this->getEndDateAndHour() ) < amapress_time() ) {
				wp_die( __( 'Clos et passé', 'amapress' ) );
			}
		}

		$slot      = strval( $slot );
		$all_slots = $this->getSlotsConf();
		if ( ! isset( $all_slots[ $slot ] ) ) {
			wp_die( __( 'Créneau non déclaré', 'amapress' ) );
		}

		$requested_slot = $all_slots[ $slot ];
		$slots          = $this->getSlots();
		if ( ! $slots ) {
			$slots = array();
		}

		$requested_slot_max = $requested_slot['max'];
		if ( $requested_slot_max ) {
			foreach ( $slots as $s ) {
				if ( $s == $slot ) {
					$requested_slot_max -= 1;
				}
			}
			if ( $requested_slot < 1 ) {
				return 'full';
			}
		}

		if ( $set ) {
			if ( ! amapress_can_access_admin() && ! empty( $slots["u{$user_id}"] ) ) {
				return 'already_in_list';
			} else {
				$slots["u{$user_id}"] = $slot;
				$this->setSlots( $slots );

				$amapien = AmapressUser::getBy( $user_id );
				$cc      = $amapien->getAllEmailsWithCoAdherents();
				$cc      = array_diff( $cc, $amapien->getAllEmails() );

				if ( amapress_current_user_id() == $user_id ) {
					if ( Amapress::getOption( 'inscr-' . static::POST_TYPE . '-slot-send' ) ) {
						amapress_mail_current_user_inscr( $this, $user_id, $this->getMailEventType(),
							function ( $content, $user_id, $post ) use ( $requested_slot ) {
								$content = str_replace( '%%creneau%%',
									$requested_slot['display'], $content );
								$content = str_replace( '%%creneau_date_heure%%',
									date_i18n( 'd/m/Y H:i', $requested_slot['date'] ), $content );

								return $content;
							}, static::POST_TYPE . '-slot', null, $cc
						);
					}
				} else {
					$responsable      = AmapressUser::getBy( amapress_current_user_id() );
					$responsable_html = sprintf( __( '%s (%s)', 'amapress' ),
						Amapress::makeLink( 'mailto:' . $responsable->getEmail(), $responsable->getDisplayName() ),
						$responsable->getContacts() );

					if ( Amapress::getOption( 'inscr-' . static::POST_TYPE . '-admin-slot-send' ) ) {
						amapress_mail_current_user_inscr( $this, $user_id, $this->getMailEventType(),
							function ( $content, $user_id, $post ) use ( $requested_slot, $responsable_html ) {
								$content = str_replace( '%%creneau%%',
									$requested_slot['display'], $content );
								$content = str_replace( '%%creneau_date_heure%%',
									date_i18n( 'd/m/Y H:i', $requested_slot['date'] ), $content );
								$content = str_replace( '%%responsable%%',
									$responsable_html, $content );

								return $content;
							}, static::POST_TYPE . '-admin-slot', null, $cc
						);
					}
				}

				return 'ok';
			}
		} else {
			if ( ! isset( $slots["u{$user_id}"] ) ) {
				return 'not_inscr';
			} else {
//				$slot = $slots["u{$user_id}"];
				unset( $slots["u{$user_id}"] );
				$this->setSlots( $slots );

				return 'ok';
			}
		}
	}

	protected function canSubscribeType( $type ) {
		$before_close_hours = Amapress::getOption( "close-subscribe-{$type}-hours" );
		if ( empty( $before_close_hours ) ) {
			$before_close_hours = 24;
		}

		return ( $this->getStartDateAndHour() - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time();
	}

	protected function canUnsubscribeType( $type ) {
		$before_close_hours = Amapress::getOption( "close-unsubscribe-{$type}-hours" );
		if ( empty( $before_close_hours ) ) {
			$before_close_hours = 24;
		}

		return ( $this->getStartDateAndHour() - HOUR_IN_SECONDS * $before_close_hours ) > amapress_time();
	}

	public function sendNewCommentMailToMembers( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( null == $comment ) {
			return;
		}

		$author  = $comment->comment_author;
		$amapien = AmapressUser::getBy( $comment->user_id );
		if ( $amapien ) {
			$author = $amapien->getDisplayName();
		}

		$member_ids = $this->getMembersIds();
		if ( $this->getPost()->post_author ) {
			$member_ids = array_merge( $member_ids, [ $this->getPost()->post_author ] );
		}
		$member_ids = array_diff( $member_ids, [ amapress_current_user_id() ] );
		if ( empty( $member_ids ) ) {
			return;
		}
		$target_users = amapress_prepare_message_target_bcc( "user:include=" . implode( ',', $member_ids ), __( 'Amapiens', 'amapress' ), 'amapiens' );
		$subject      = Amapress::getOption( 'comment-event-mail-subject' );
		$content      = Amapress::getOption( 'comment-event-mail-content' );
		$commentaire  = sprintf( __( '%s: %s', 'amapress' ), $author, $comment->comment_content );
		$subject      = str_replace( "%%commentaire%%", $commentaire, $subject );
		$content      = str_replace( "%%commentaire%%", $commentaire, $content );
		amapress_send_message(
			$subject,
			$content,
			'', $target_users, $this
		);
	}
}