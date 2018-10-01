<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressContrat extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat';
	const POST_TYPE = 'contrat';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressContrat
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressContrat' ) ) {
			$post_id = $post_or_id->ID;
			$post    = $post_or_id;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( $no_cache ) {
			unset( self::$entities_cache[ $post_id ] );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressContrat( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_contrat_photo');
//    }

//	public function getPresentation() {
//		return wpautop( $this->getCustom( 'amapress_contrat_presentation' ) );
//	}
//
//	public function getPresentationRaw() {
//		return $this->getCustom( 'amapress_contrat_presentation' );
//	}

	public function getNb_visites() {
		return floatval( $this->getCustom( 'amapress_contrat_nb_visites', 0 ) );
	}

	public function getMax_adherents() {
		return floatval( $this->getCustom( 'amapress_contrat_max_adherents', 0 ) );
	}

	/** @return AmapressProducteur */
	public function getProducteur() {
		return $this->getCustomAsEntity( 'amapress_contrat_producteur', 'AmapressProducteur' );
	}

	/** @return int */
	public function getProducteurId() {
		return $this->getCustomAsInt( 'amapress_contrat_producteur' );
	}
}

class AmapressContrat_instance extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat_inst';
	const POST_TYPE = 'contrat_instance';


	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressContrat_instance
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressContrat_instance' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( $no_cache ) {
			unset( self::$entities_cache[ $post_id ] );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressContrat_instance( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	public function getManage_Cheques() {
		return $this->getCustom( 'amapress_contrat_manage_paiements', 1 );
	}

	/** @return AmapressContrat */
	public function getModel() {
		return $this->getCustomAsEntity( 'amapress_contrat_instance_model', 'AmapressContrat' );
	}

	/** @return string */
	public function getSubName() {
		return $this->getCustom( 'amapress_contrat_instance_name' );
	}

	/** @return int */
	public function getModelId() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_model' );
	}

	public function getMax_adherents() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_max_adherents', 0 );
	}

	public function getDate_ouverture() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_ouverture' );
	}

	public function getDate_cloture() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_cloture' );
	}

	public function getDate_debut() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_debut' );
	}

	public function isQuantiteMultiple() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_quantite_multi', 1 );
	}

	public function isQuantiteVariable() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_quantite_variable' );
	}

	public function isPanierVariable() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_panier_variable' );
	}

	public function getDate_fin() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_date_fin' );
	}

	/** @return AmapressLieu_distribution[] */
	public function getLieux() {
		return $this->getCustomAsEntityArray( 'amapress_contrat_instance_lieux', 'AmapressLieu_distribution' );
	}

	/** @return int[] */
	public function getLieuxIds() {
		return $this->getCustomAsIntArray( 'amapress_contrat_instance_lieux' );
	}

//	public function getType() {
//		return $this->getCustom( 'amapress_contrat_instance_type' );
//	}

	public function getNb_visites() {
		return floatval( $this->getCustom( 'amapress_contrat_instance_nb_visites', 0 ) );
	}

	/** @return string */
//	public function getOnlineContrat() {
//		return wpautop( $this->getCustom( 'amapress_contrat_instance_contrat' ) );
//	}

	/** @return string */
	public function getOnlineContratRaw() {
		return $this->getCustom( 'amapress_contrat_instance_contrat' );
	}

	public function hasOnlineContrat() {
		$contrat_cnt = $this->getOnlineContratRaw();
		if ( empty( $contrat_cnt ) || strlen( wp_strip_all_tags( $contrat_cnt ) ) < 15 ) {
			return false;
		}

		return true;
	}

	public function isPrincipal() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_is_principal', 0 );
	}

	public function canSelfSubscribe() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_self_subscribe', 0 );
	}

	public function isEnded() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_ended', 0 );
	}

	public function getListe_dates() {
		$liste_dates = $this->getCustomAsDateArray( 'amapress_contrat_instance_liste_dates' );
		if ( empty( $liste_dates ) || count( $liste_dates ) == 0 ) {
			$liste_dates = $this->getCustomAsDateArray( 'amapress_contrat_instance_commande_liste_dates' );
		}

		$liste_dates = array_filter( $liste_dates, function ( $d ) {
			return Amapress::start_of_day( $this->getDate_debut() ) <= $d && $d <= Amapress::end_of_day( $this->getDate_fin() );
		} );

		return $liste_dates;
	}

	public function getRattrapage() {
		return $this->getCustomAsArray( 'amapress_contrat_instance_rattrapage' );
	}

	public function getDateFactor( $dist_date, $quantite_id = null ) {
		$date_factor = 1;
		if ( $dist_date && $quantite_id ) {
			$quantite = AmapressContrat_quantite::getBy( $quantite_id );
			if ( $quantite ) {
				if ( ! $quantite->isInDistributionDates( $dist_date ) ) {
					return 0;
				}
			}
		}
		$rattrapage = $this->getRattrapage();
		foreach ( $rattrapage as $r ) {
			if ( Amapress::start_of_day( intval( $r['date'] ) ) == Amapress::start_of_day( $dist_date ) ) {
				$date_factor = floatval( $r['quantite'] );
				break;
			}
		}

		return $date_factor;
	}

	public function getRealDateForDistribution( $date ) {
		$paniers = AmapressPanier::get_delayed_paniers( null, null, $date, [ 'delayed' ] );
		foreach ( $paniers as $p ) {
			if ( $p->getContrat_instanceId() == $this->ID ) {
				return Amapress::start_of_day( $p->getDateSubst() );
			}
		}

		return Amapress::start_of_day( $date );
	}

	public function getPaiements_Liste_dates() {
		return $this->getCustomAsDateArray( 'amapress_contrat_instance_liste_dates_paiements' );
	}

	public function getPossiblePaiements() {
		return $this->getCustomAsIntArray( 'amapress_contrat_instance_paiements' );
	}

	public function getMinChequeAmount() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_min_cheque_amount' );
	}

	public function getContratWordModelId() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_word_model' );
	}

	public function getContratModelDocFileName() {
		return get_attached_file( $this->getContratWordModelId(), true );
	}

	public function getContratPapierWordModelId() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_word_paper_model' );
	}

	public function getContratPapierModelDocFileName() {
		return get_attached_file( $this->getContratPapierWordModelId(), true );
	}

//	public static function getPlaceholdersHelp() {
//		$ret = [];
//
//		foreach ( self::getProperties() as $prop_name => $prop ) {
//			if ( ! isset( $prop['desc'] ) ) {
//				continue;
//			}
//
//			$ret[ $prop_name ] = $prop['desc'];
//		}
//		$ret["quantite"]               = '(Tableau quantité) Libellé quantité';
//		$ret["quantite_code"]          = '(Tableau quantité) Code quantité';
//		$ret["quantite_nb_distrib"]    = '(Tableau quantité) Nombre de distribution restantes';
//		$ret["quantite_sous_total"]    = '(Tableau quantité) Prix pour la quantité choisie';
//		$ret["quantite_total"]         = '(Tableau quantité) Prix pour la quuantité choisie x nombre distrib';
//		$ret["quantite_nombre"]        = '(Tableau quantité) Facteur quantité choisi';
//		$ret["quantite_prix_unitaire"] = '(Tableau quantité) Prix à l\'unité';
//		$ret["quantite_description"]   = '(Tableau quantité) Description de la quantité';
//		$ret["quantite_unite"]         = '(Tableau quantité) Unité de la quantité';
//
//		return '<table id="contrat-placeholders"><thead><tr><th>Placeholder</th><th>Description</th></tr></thead>' .
//		       implode( '', array_map( function ( $pn, $p ) {
//			       return '<tr><td>${' . esc_html( $pn ) . '}</td><td>' . esc_html( $p ) . '</td></tr>';
//		       }, array_keys( $ret ), array_values( $ret ) ) )
//		       . '</table>';
//	}

	public function getMailtoAmapiens() {
		$mails = [];
		foreach (
			get_users( [
				'amapress_contrat' => $this->ID,
			] ) as $user
		) {
			/** @var WP_User $user */
			$amapien = AmapressUser::getBy( $user );
			$mails   = array_merge( $mails, $amapien->getAllEmails() );
		}
		if ( empty( $mails ) ) {
			return '';
		}

		return 'mailto:' . urlencode( implode( ',', $mails ) ) . '&subject=Contrat ' . $this->getTitle();
	}

	public function getSMStoAmapiens() {
		$phones = [];
		foreach (
			get_users( [
				'amapress_contrat' => $this->ID,
			] ) as $user
		) {
			/** @var WP_User $user */
			$amapien = AmapressUser::getBy( $user );
			$phones  = array_merge( $phones, $amapien->getPhoneNumbers( true ) );
		}
		if ( empty( $phones ) ) {
			return '';
		}

		return 'sms:' . urlencode( implode( ',', $phones ) ) . '?body=Contrat ' . $this->getTitle();
	}

	public function getContratDocFileName( $date_first_distrib ) {
		$model_filename = $this->getContratModelDocFileName();
		$ext            = strpos( $model_filename, '.docx' ) !== false ? '.docx' : '.odt';

		return trailingslashit( Amapress::getContratDir() ) . sanitize_file_name(
				'contrat-papier-' . $this->getTitle() . '-' . $this->ID . '-' . date_i18n( 'Y-m-d', $date_first_distrib ) . $ext );
	}

	public static function getProperties( $first_date_distrib = null ) {
		$ret                                = [];
		$ret['contrat_type']                = [
			'desc' => 'Type du contrat (par ex, Légumes)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getTitle();
			}
		];
		$ret['contrat_titre']               = [
			'desc' => 'Nom du contrat (par ex, Légumes 09/2018-08/2019)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getTitle();
			}
		];
		$ret['contrat_sous_titre']          = [
			'desc' => 'Nom complémentaire du contrat (par ex, Semaine A)',
			'func' => function ( AmapressAdhesion $adh ) {
				return $adh->getContrat_instance()->getSubName();
			}
		];
		$ret['contrat_lien']                = [
			'desc' => 'Lien vers la présentation du contrat',
			'func' => function ( AmapressContrat_instance $adh ) {
				return $adh->getModel()->getPermalink();
			}
		];
		$ret['date_debut']                  = [
			'desc' => 'Date début du contrat (par ex, 22/09/2018)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'd/m/Y', $adh->getDate_debut() );
			}
		];
		$ret['date_fin']                    = [
			'desc' => 'Date fin du contrat (par ex, 22/09/2018)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'd/m/Y', $adh->getDate_fin() );
			}
		];
		$ret['date_debut_complete']         = [
			'desc' => 'Date début du contrat (par ex, jeudi 22 septembre 2018)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'l j M Y', $adh->getDate_debut() );
			}
		];
		$ret['date_fin_complete']           = [
			'desc' => 'Date fin du contrat (par ex, jeudi 22 septembre 2018)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'l j M Y', $adh->getDate_fin() );
			}
		];
		$ret['lieux']                       = [
			'desc' => 'Lieux de distribution',
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getTitle();
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieu']                        = [
			'desc' => 'Lieu de distribution',
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getTitle();
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieux']                       = [
			'desc' => 'Lieux de distribution (nom court)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getShortName();
				}, $adh->getLieux() ) );
			}
		];
		$ret['lieu']                        = [
			'desc' => 'Lieu de distribution (nom court)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ' ou ', array_map( function ( AmapressLieu_distribution $l ) {
					return $l->getShortName();
				}, $adh->getLieux() ) );
			}
		];
		$ret['contrat_debut']               = [
			'desc' => 'Début du contrat (mois/année)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'm/Y', $adh->getDate_debut() );
			}
		];
		$ret['contrat_fin']                 = [
			'desc' => 'Fin du contrat (mois/année)',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'm/Y', $adh->getDate_fin() );
			}
		];
		$ret['contrat_debut_annee']         = [
			'desc' => 'Année de début du contrat',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'Y', $adh->getDate_debut() );
			}
		];
		$ret['contrat_fin_annee']           = [
			'desc' => 'Année de fin du contrat',
			'func' => function ( AmapressContrat_instance $adh ) {
				return date_i18n( 'Y', $adh->getDate_fin() );
			}
		];
		$ret['nb_paiements']                = [
			'desc' => 'Nombre de chèques possibles',
			'func' => function ( AmapressContrat_instance $adh ) {
				return implode( ', ', $adh->getPossiblePaiements() );
			}
		];
		$ret['dates_rattrapages']           = [
			'desc' => 'Description des dates de distribution de rattrapage',
			'func' => function ( AmapressContrat_instance $adh ) {
				$rattrapage        = [];
				$double_rattrapage = [];
				$un5_rattrapage    = [];
				$dates_factors     = 0;
				$dates             = $adh->getRemainingDates();
				foreach ( $dates as $d ) {
					$the_factor = $adh->getDateFactor( $d );
					if ( abs( $the_factor - 2 ) < 0.001 ) {
						$double_rattrapage[] = date_i18n( 'd/m/Y', $d );
					} else if ( abs( $the_factor - 1.5 ) < 0.001 ) {
						$un5_rattrapage[] = date_i18n( 'd/m/Y', $d );
					} else if ( abs( $the_factor - 1 ) > 0.001 ) {
						$rattrapage[] = $the_factor . ' distribution le ' . date_i18n( 'd/m/Y', $d );
					}
					$dates_factors += $the_factor;
				}

				if ( ! empty( $double_rattrapage ) ) {
					$rattrapage[] = 'double distribution ' . _n( 'le', 'les', count( $double_rattrapage ) ) . ' ' . implode( ', ', $double_rattrapage );
				}
				if ( ! empty( $un5_rattrapage ) ) {
					$rattrapage[] = '1.5 distribution ' . _n( 'le', 'les', count( $un5_rattrapage ) ) . ' ' . implode( ', ', $un5_rattrapage );
				}

				return implode( ', ', $rattrapage );
			}
		];
		$ret['nb_dates']                    = [
			'desc' => 'Nombre de dates de distributions restantes',
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return count( $adh->getRemainingDates( $first_date_distrib ) );
			}
		];
		$ret['nb_distributions']            = [
			'desc' => 'Nombre de distributions restantes',
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return $adh->getRemainingDatesWithFactors( $first_date_distrib );
			}
		];
		$ret['dates_distribution_par_mois'] = [
			'desc' => 'Dates de distributions regroupées par mois',
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				$dates         = $adh->getRemainingDates( $first_date_distrib );
				$grouped_dates = from( $dates )->groupBy( function ( $d ) {
					return date_i18n( 'F Y', $d );
				} );

				$grouped_dates_array = [];
				foreach ( $grouped_dates as $k => $v ) {
					$grouped_dates_array[] = $k . ' : ' . ( count( $v ) > 1 ? 'les ' : 'le ' ) . implode( ', ', array_map(
							function ( $d ) {
								return date_i18n( 'd', $d );
							}, $v
						) );
				}

				return implode( ' ; ', $grouped_dates_array );
			}
		];
		$ret['premiere_date']               = [
			'desc' => 'Première date de distribution',
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return date_i18n( 'd/m/Y', from( $adh->getRemainingDates( $first_date_distrib ) )->firstOrDefault() );
			}
		];
		$ret['derniere_date']               = [
			'desc' => 'Dernière date de distribution',
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return date_i18n( 'd/m/Y', from( $adh->getRemainingDates( $first_date_distrib ) )->lastOrDefault() );
			}
		];
		$ret['dates_distribution']          = [
			'desc' => 'Liste des dates de distribution',
			'func' => function ( AmapressContrat_instance $adh ) use ( $first_date_distrib ) {
				return implode( ', ', array_map( function ( $d ) {
					return date_i18n( 'd/m/Y', $d );
				}, $adh->getRemainingDates( $first_date_distrib ) ) );
			}
		];

		return $ret;
	}


	public function getRemainingDates( $date = null, $quantite_id = null ) {
		if ( empty( $date ) ) {
			$date = amapress_time();
		}

		$dates      = $this->getListe_dates();
		$start_date = Amapress::start_of_day( $date );

		return array_filter( $dates, function ( $d ) use ( $start_date, $quantite_id ) {
			$factor = $this->getDateFactor( $d, $quantite_id );
			if ( abs( $factor ) < 0.001 ) {
				return false;
			}

			return Amapress::start_of_day( $d ) >= $start_date;
		} );
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_contrat = false, $show_toggler = true ) {
		$ret = [];

		foreach ( Amapress::getPlaceholdersHelpForProperties( self::getProperties() ) as $prop_name => $prop_desc ) {
			$ret[ $prop_name ] = $prop_desc;
		}
		$ret["quantite"]               = '(Tableau quantité) Libellé quantité avec facteur';
		$ret["quantite_simple"]        = '(Tableau quantité) Libellé quantité';
		$ret["quantite_code"]          = '(Tableau quantité) Code quantité';
		$ret["quantite_nb_distrib"]    = '(Tableau quantité) Nombre de distribution restantes';
		$ret["quantite_total"]         = '(Tableau quantité) Prix pour la quuantité x nombre distrib';
		$ret["quantite_prix_unitaire"] = '(Tableau quantité) Prix à l\'unité';
		$ret["quantite_description"]   = '(Tableau quantité) Description de la quantité';
		$ret["quantite_unite"]         = '(Tableau quantité) Unité de la quantité';
		$ret["quantite_paiements"]     = '(Tableau quantité) Possibilités de paiements';

		return Amapress::getPlaceholdersHelpTable( 'contrat_inst-placeholders', $ret,
			'du contrat', $additional_helps, ! $for_contrat,
			$for_contrat ? '${' : '%%', $for_contrat ? '}' : '%%',
			$show_toggler );
	}

	public function generateContratDoc( $date_first_distrib ) {
		$out_filename   = $this->getContratDocFileName( $date_first_distrib );
		$model_filename = $this->getContratPapierModelDocFileName();
		if ( empty( $model_filename ) ) {
			return '';
		}

		$placeholders = [];
		foreach ( self::getProperties( $date_first_distrib ) as $prop_name => $prop_config ) {
			$placeholders[ $prop_name ] = call_user_func( $prop_config['func'], $this );
		}

		$quants = AmapressContrats::get_contrat_quantites( $this->ID );
		$i      = 1;
		foreach ( $quants as $quant ) {
			$remaining_dates                           = count( $this->getRemainingDates( $date_first_distrib, $quant->ID ) );
			$remaining_distrib                         = $this->getRemainingDatesWithFactors( $date_first_distrib, $quant->ID );
			$placeholders["quantite#$i"]               = $quant->getTitle();
			$placeholders["quantite_simple#$i"]        = $quant->getTitle();
			$placeholders["quantite_code#$i"]          = $quant->getCode();
			$placeholders["quantite_nb_dates#$i"]      = $remaining_dates;
			$placeholders["quantite_nb_distrib#$i"]    = $remaining_distrib;
			$placeholders["quantite_total#$i"]         = Amapress::formatPrice( $quant->getPrix_unitaire() * $remaining_distrib );
			$placeholders["quantite_prix_unitaire#$i"] = Amapress::formatPrice( $quant->getPrix_unitaire() );
			$placeholders["quantite_description#$i"]   = $quant->getDescription();
			$placeholders["quantite_unite#$i"]         = $quant->getPriceUnitDisplay();
			$paiements                                 = [];
			foreach ( $this->getPossiblePaiements() as $nb_cheque ) {
				$ch = $this->getChequeOptionsForTotal( $nb_cheque, $quant->getPrix_unitaire() * $remaining_distrib );
				if ( ! isset( $ch['desc'] ) ) {
					continue;
				}
				$paiements[] = $ch['desc'];
			}
			$placeholders["quantite_paiements#$i"] = '[] ' . implode( "\n[] ", $paiements );
			$i                                     += 1;
		}

		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor( $model_filename );
		try {
			$templateProcessor->cloneRow( 'quantite', count( $quants ) );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'quantite_simple', count( $quants ) );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				try {
					$templateProcessor->cloneRow( 'quantite_description', count( $quants ) );
				} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				}
			}
		}

		foreach ( $placeholders as $k => $v ) {
			$templateProcessor->setValue( $k, $v );
		}

		$templateProcessor->saveAs( $out_filename );

		return $out_filename;
	}


	public function getMinEngagement() {
		return $this->getCustomAsInt( 'amapress_contrat_instance_min_engagement' );
	}

	public function getChequeOptionsForTotal( $nb_cheque, $total ) {
//		$last_cheque = $this->getMinChequeAmount();

		if ( $nb_cheque > 1 ) {
			if ( ( $total / $nb_cheque ) * 2 == intval( $total / $nb_cheque * 2 ) ) {
				$last_cheque        = $total / $nb_cheque;
				$cheque_main_amount = $total / $nb_cheque;
			} else {
				$cheque_main_amount = floor( $total / $nb_cheque );
				$last_cheque        = $total - $cheque_main_amount * ( $nb_cheque - 1 );
			}
		} else {
			$last_cheque        = 0;
			$cheque_main_amount = $total;
		}

		$nb = $nb_cheque;
		if ( $cheque_main_amount == $last_cheque ) {
			$last_cheque = 0;
			$option      = sprintf( "$nb chèque(s) de %0.2f €", $cheque_main_amount );
		} else if ( $last_cheque == 0 ) {
			$nb     = 1;
			$option = sprintf( "1 chèque de %0.2f €", $cheque_main_amount );
		} else {
			$nb     = $nb_cheque - 1;
			$option = sprintf( "$nb chèque(s) de %0.2f € et 1 chèque de %0.2f €", $cheque_main_amount, $last_cheque );
		}

		return [
			'desc'          => $option,
			'main_amount'   => $cheque_main_amount,
			'remain_amount' => $last_cheque,
		];
	}

	/** @return AmapressAdhesion[] */
	public function getAdhesionsForUser( $user_id = null, $date = null, $ignore_renouv_delta = false ) {
		return AmapressAdhesion::getUserActiveAdhesions( $user_id, $this->ID, $date, $ignore_renouv_delta );
	}

	/**
	 * @return int[]
	 */
	public static function getContratInstanceIdsForUser( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_contrat_instances_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			$ads = AmapressAdhesion::getUserActiveAdhesions( $user_id, $contrat_id, $date, $ignore_renouv_delta );
			$res = array();
			foreach ( $ads as $ad ) {
				$res[] = $ad->getContrat_instanceId();
			}
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	public function cloneContrat( $as_draft = true, $for_renew = true, $same_period = false ) {
		$this->ensure_init();

		if ( ! $for_renew ) {
			$add_weeks = 0;
		} else if ( $same_period ) {
			$add_weeks = 52;
		} else {
			$add_weeks = Amapress::datediffInWeeks(
				Amapress::start_of_week( $this->getDate_debut() ),
				Amapress::end_of_week( $this->getDate_fin() )
			);
		}
		$meta = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}

		$date_debut = Amapress::add_a_week( $this->getDate_debut(), $add_weeks );
		$date_fin   = Amapress::add_a_week( $this->getDate_fin(), $add_weeks );

		$new_liste_dates                               = array_map(
			function ( $date ) use ( $add_weeks ) {
				return Amapress::add_a_week( $date, $add_weeks );
			}, $this->getListe_dates() );
		$new_liste_dates                               = array_filter(
			$new_liste_dates,
			function ( $date ) use ( $date_debut, $date_fin ) {
				return $date_debut <= $date && $date <= $date_fin;
			} );
		$meta['amapress_contrat_instance_liste_dates'] = implode( ',',
			array_map(
				function ( $date ) {
					return date( 'd/m/Y', $date );
				}, $new_liste_dates )
		);

		$new_liste_dates_paiements                               = array_map(
			function ( $date ) use ( $add_weeks ) {
				return Amapress::add_a_week( $date, $add_weeks );
			}, $this->getPaiements_Liste_dates() );
		$meta['amapress_contrat_instance_liste_dates_paiements'] = implode( ',',
			array_map(
				function ( $date ) {
					return date( 'd/m/Y', $date );
				}, $new_liste_dates_paiements )
		);
		if ( $for_renew ) {
//			unset( $meta['amapress_contrat_instance_liste_dates_paiements'] );
			unset( $meta['amapress_contrat_instance_commande_liste_dates'] );
		}
		$meta['amapress_contrat_instance_date_debut'] = $date_debut;
		$meta['amapress_contrat_instance_date_fin']   = $date_fin;
		unset( $meta['amapress_contrat_instance_ended'] );
		$meta['amapress_contrat_instance_date_ouverture'] = Amapress::add_a_week( $this->getDate_ouverture(), $add_weeks );
		$meta['amapress_contrat_instance_date_cloture']   = Amapress::add_a_week( $this->getDate_cloture(), $add_weeks );

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressContrat_instance::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => $as_draft ? 'draft' : 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		foreach ( AmapressContrats::get_contrat_quantites( $this->ID ) as $quantite ) {
			$quantite->cloneForContrat( $new_id );
		}

		return AmapressContrat_instance::getBy( $new_id );
	}

	public function canRenew() {
		$contrat_instances = AmapressContrats::get_active_contrat_instances();

		return ! from( $contrat_instances )->any( function ( $a ) {
			/** @var AmapressContrat_instance $a */
			return ( $a->getModelId() == $this->getModelId()
			         && ( $a->getSubName() == $this->getSubName() || ( empty( $a->getSubName() ) && empty( $this->getSubName() ) ) )
			         && $a->getDate_debut() > $this->getDate_debut() );
		} );
	}

	public function getRemainingDatesWithFactors( $start_date, $quantite_id = null ) {
		$dates         = $this->getListe_dates();
		$dates         = array_filter( $dates, function ( $d ) use ( $start_date ) {
			return $d >= $start_date;
		} );
		$dates_factors = 0;
		foreach ( $dates as $d ) {
			$dates_factors += $this->getDateFactor( $d, $quantite_id );
		}

		return $dates_factors;
	}

	public function getInscriptionsStats() {
		$stored = $this->getCustom( 'stats' );
		if ( ! empty( $stored ) ) {
			return $stored;
		}

		$inscriptions = AmapressContrats::get_all_adhesions( $this->getID() );

		$lieux = [];
		foreach ( $inscriptions as $inscription ) {
			$k = strval( $inscription->getLieuId() );
			if ( ! isset( $lieux[ $k ] ) ) {
				$lieux[ $k ] = $inscription->getLieu()->getShortName();
			}
		}
		$quantites = AmapressContrats::get_contrat_quantites( $this->getID() );

		$columns   = [];
		$columns[] = array(
			'title' => 'Date',
			'data'  => 'date',
		);
		if ( count( $lieux ) > 1 ) {
			foreach ( $lieux as $lieu_key => $lieu_name ) {
				$lieu_id   = intval( $lieu_key );
				$columns[] = array(
					'title' => $lieu_name . ' - Inscriptions',
					'data'  => 'lieu_' . $lieu_id . '_inscriptions',
				);
				foreach ( $quantites as $quantite ) {
					$columns[] = array(
						'title' => $lieu_name . ' - ' . $quantite->getTitle(),
						'data'  => 'lieu_' . $lieu_id . '_q' . $quantite->getID(),
					);
				}
			}
		}
		$columns[] = array(
			'title' => 'Inscriptions',
			'data'  => 'lieu_all_inscriptions',
		);
		foreach ( $quantites as $quantite ) {
			$columns[] = array(
				'title' => $quantite->getTitle(),
				'data'  => 'lieu_all_q' . $quantite->getID(),
			);
		}

		$lines = [];
		foreach ( $this->getListe_dates() as $date ) {
			$date_inscriptions = array_filter( $inscriptions,
				function ( $inscription ) use ( $date ) {
					/** @var AmapressAdhesion $inscription */
					return Amapress::start_of_day( $inscription->getDate_debut() ) <= Amapress::start_of_day( $date )
					       && Amapress::end_of_day( $date ) <= Amapress::end_of_day( $inscription->getDate_fin() );
				} );
			$line              = [
				'date'                  => date_i18n( 'd/m/Y', $date ),
				'lieu_all_inscriptions' => count( $date_inscriptions )
			];


			if ( count( $lieux ) > 1 ) {
				foreach ( $lieux as $lieu_key => $lieu_name ) {
					$lieu_id                                      = intval( $lieu_key );
					$lieu_inscriptions                            = array_filter( $date_inscriptions,
						function ( $inscription ) use ( $lieu_id ) {
							/** @var AmapressAdhesion $inscription */
							return $inscription->getLieuId() == $lieu_id;
						} );
					$line[ 'lieu_' . $lieu_id . '_inscriptions' ] = count( $lieu_inscriptions );

					foreach ( $quantites as $quantite ) {
						$quantite_sum                                           = from( $lieu_inscriptions )->sum(
							function ( $inscription ) use ( $quantite, $date ) {
								/** @var AmapressAdhesion $inscription */
								foreach ( $inscription->getContrat_quantites( null ) as $q ) {
									if ( $q->getId() == $quantite->ID ) {
										return $q->getFactor();
									}
								}

								return 0;
							} );
						$line[ 'lieu_' . $lieu_id . '_q' . $quantite->getID() ] = $quantite_sum;
					}
				}
			}

			foreach ( $quantites as $quantite ) {
				$quantite_sum                              = from( $date_inscriptions )->sum(
					function ( $inscription ) use ( $quantite, $date ) {
						/** @var AmapressAdhesion $inscription */
						foreach ( $inscription->getContrat_quantites( null ) as $q ) {
							if ( $q->getId() == $quantite->ID ) {
								return $q->getFactor();
							}
						}

						return 0;
					} );
				$line[ 'lieu_all_q' . $quantite->getID() ] = $quantite_sum;
			}

			$lines[] = $line;
		}

		$ret = [
			'columns' => $columns,
			'lines'   => $lines,
		];
		if ( amapress_time() > Amapress::end_of_day( $this->getDate_fin() ) ) {
			$this->setCustom( 'stats', $ret );
		}

		return $ret;
	}
}

class AmapressContrat_quantite extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_contrat_quant';
	const POST_TYPE = 'contrat_quantite';

	private static $entities_cache = array();

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressContrat_quantite
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressContrat_quantite' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressContrat_quantite( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_contrat_quantite_photo', '');
//    }

	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_contrat_quantite_contrat_instance', 'AmapressContrat_instance' );
	}

//    public function getMax_Commandable()
//    {
//        return $this->getCustomAsFloat('amapress_contrat_quantite_max_quantite');
//    }

	public function getPrix_unitaire() {
		return $this->getCustomAsFloat( 'amapress_contrat_quantite_prix_unitaire' );
	}

	public function getQuantite() {
		return $this->getCustomAsFloat( 'amapress_contrat_quantite_quantite' );
	}

	public function getCode() {
		return $this->getCustom( 'amapress_contrat_quantite_code' );
	}

	public function getDescription() {
		return $this->getCustom( 'amapress_contrat_quantite_description' );
	}

	/** @return AmapressProduit[] */
	public function getProduits() {
		return $this->getCustomAsEntityArray( 'amapress_contrat_quantite_produits', 'AmapressProduit' );
	}

	/** @return int[] */
	public function getProduitsIds() {
		return $this->getCustomAsIntArray( 'amapress_contrat_quantite_produits' );
	}

	public function getPriceUnit() {
		return $this->getCustom( 'amapress_contrat_quantite_unit' );
	}

	public function getPriceUnitDisplay() {
		switch ( $this->getPriceUnit() ) {
			case 'kg':
				return 'le kg';
			case 'l':
				return 'le litre';
			case 'unit':
				return 'l\'unité';
			default:
				return $this->getPriceUnit();
		}
	}

	public function getQuantiteConfig() {
		return $this->getCustom( 'amapress_contrat_quantite_quantite_config' );
	}

	public function getAvailFrom() {
		return $this->getCustom( 'amapress_contrat_quantite_avail_from' );
	}

	public function getAvailTo() {
		return $this->getCustom( 'amapress_contrat_quantite_avail_to' );
	}

	public function getQuantiteOptions() {
		$confs = $this->getQuantiteConfig();
		if ( empty( $confs ) ) {
			$confs = '0>10:1';
		}

		$options = array();
		foreach ( explode( ';', $confs ) as $conf ) {
			$m     = array();
			$float = '(?:\d+(?:[,\.]\d+)?)';
			$unit  = '(?:g|kg|l|ml|L)';
			if ( preg_match( "/(?<start>$float)(?<start_unit>$unit)?(?:\\s*(?:\\>|-)\\s*(?<stop>$float)(?<stop_unit>$unit)?(?:\\s*\\:\\s*(?<incr>$float)(?<incr_unit>$unit)?)?)?/", $conf, $m ) !== false ) {
				$start_unit_factor = isset( $m['start_unit'] ) && ( $m['start_unit'] == 'g' || $m['start_unit'] == 'ml' ) ? 1000 : 1;
				$start             = isset( $m['start'] ) ? floatval( str_replace( ',', '.', $m['start'] ) ) : 1;
				$start             = $start / $start_unit_factor;
				$stop_unit_factor  = isset( $m['stop_unit'] ) && ( $m['stop_unit'] == 'g' || $m['stop_unit'] == 'ml' ) ? 1000 : 1;
				$stop              = isset( $m['stop'] ) ? floatval( str_replace( ',', '.', $m['stop'] ) ) : $start;
				$stop              = $stop / $stop_unit_factor;
				$incr_unit_factor  = isset( $m['incr_unit'] ) && ( $m['incr_unit'] == 'g' || $m['incr_unit'] == 'ml' ) ? 1000 : 1;
				$incr              = isset( $m['incr'] ) ? floatval( str_replace( ',', '.', $m['incr'] ) ) : 1;
				$incr              = $incr / $incr_unit_factor;

				for ( $i = $start; $i <= $stop; $i += $incr ) {
					if ( $i > 0 ) {
						$k             = strval( $i );
						$options[ $k ] = $this->formatValue( $i );
					}
				}
			}
		}

		return $options;
	}

	public
	function formatValue(
		$value
	) {
		if ( $this->getPriceUnit() == 'kg' ) {
			if ( $value < 1 ) {
				return sprintf( '%d', (int) ( $value * 1000.0 ) ) . 'g';
			} else {
				return round( $value, 2 ) . 'kg';
			}
		} else if ( $this->getPriceUnit() == 'l' ) {
			if ( $value < 1 ) {
				return sprintf( '%d', (int) ( $value * 1000.0 ) ) . 'ml';
			} else {
				return round( $value, 2 ) . 'L';
			}
		} else {
			if ( abs( $value - 0.25 ) < 0.001 ) {
				return '1/4';
			} else if ( abs( $value - 0.333 ) < 0.001 ) {
				return '2/3';
			} else if ( abs( $value - 0.5 ) < 0.001 ) {
				return '1/2';
			} else if ( abs( $value - 0.666 ) < 0.001 ) {
				return '2/3';
			} else if ( abs( $value - 0.75 ) < 0.001 ) {
				return '3/4';
			} else {
				return round( $value, 2 );
			}
		}
	}

	public
	function getFormattedTitle(
		$factor
	) {
		if ( $factor != 1 ) {
			return "$factor x {$this->getTitle()}";
		} else {
			return $this->getTitle();
		}
	}

	public
	function cloneForContrat(
		$contrat_instance_id
	) {
		$this->ensure_init();

		$meta = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}
		$meta['amapress_contrat_quantite_contrat_instance'] = $contrat_instance_id;

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressContrat_quantite::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		return AmapressContrat_quantite::getBy( $new_id );
	}

	public function getSpecificDistributionDates() {
		return $this->getCustomAsDateArray( 'amapress_contrat_quantite_liste_dates' );
	}

	public function isInDistributionDates( $dist_date ) {
		$quantite_liste_dates = $this->getSpecificDistributionDates();
		if ( empty( $quantite_liste_dates ) ) {
			return true;
		}

		$quantite_liste_dates = array_map( function ( $d ) {
			return Amapress::start_of_day( $d );
		}, $quantite_liste_dates );

		return in_array( Amapress::start_of_day( $dist_date ), $quantite_liste_dates );
	}

	public static function cleanOrphans() {
		global $wpdb;
		$orphans = $wpdb->get_col( "SELECT $wpdb->posts.ID
FROM $wpdb->posts 
INNER JOIN $wpdb->postmeta
ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
WHERE 1=1 
AND ( ( $wpdb->postmeta.meta_key = 'amapress_contrat_quantite_contrat_instance'
AND CAST($wpdb->postmeta.meta_value as SIGNED) NOT IN (
SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = '" . AmapressContrat_instance::INTERNAL_POST_TYPE . "'
) ) )
AND $wpdb->posts.post_type = '" . AmapressContrat_quantite::INTERNAL_POST_TYPE . "'
GROUP BY wp_posts.ID" );

		$wpdb->query( 'START TRANSACTION' );
		foreach ( $orphans as $post_id ) {
			wp_delete_post( $post_id );
		}
		$wpdb->query( 'COMMIT' );

		$count = count( $orphans );
		if ( $count ) {
			return "$count quantités orphelines nettoyées";
		} else {
			return "Aucun quantité orpheline";
		}
	}
}
