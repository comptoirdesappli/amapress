<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressAdhesionQuantite {
	/** @var  AmapressContrat_quantite */
	private $quantite;
	/** @var  float */
	private $factor;

	/**
	 * AmapressAdhesionQuantite constructor.
	 *
	 * @param AmapressContrat_quantite $quantite
	 * @param float $factor
	 */
	public function __construct( AmapressContrat_quantite $quantite, $factor ) {
		$this->quantite = $quantite;
		$this->factor   = ! empty( $factor ) ? $factor : 1;
	}

	/**
	 * @return AmapressContrat_quantite
	 */
	public function getContratQuantite() {
		return $this->quantite;
	}

	/**
	 * @return float
	 */
	public function getFactor() {
		return $this->factor;
	}

	/**
	 * @return string
	 */
	public function getCode() {
		$quant = $this->getContratQuantite();
		if ( $quant->getContrat_instance() && $quant->getContrat_instance()->isQuantiteVariable() ) {
			if ( $this->getFactor() != 1 ) {
				return $this->getFactor() . ' x ' . $quant->getCode();
			} else {
				return $quant->getCode();
			}
		} else {
			return $quant->getCode();
		}
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		$quant = $this->getContratQuantite();
		if ( $this->getFactor() != 1 ) {
			return $this->getFactor() . ' x ' . $quant->getTitle();
		} else {
			return $quant->getTitle();
		}
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->getContratQuantite()->ID;
	}

	/**
	 * @return float
	 */
	public function getQuantite() {
		return $this->getFactor() * $this->getContratQuantite()->getQuantite();
	}

	/**
	 * @return float
	 */
	public function getPrice() {
		return $this->getFactor() * $this->getContratQuantite()->getPrix_unitaire();
	}

}

class AmapressAdhesion extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_adhesion';
	const POST_TYPE = 'adhesion';

	private static $entities_cache = array();
	const TO_CONFIRM = 'to_confirm';
	const CONFIRMED = 'confirmed';

	/**
	 * @param $post_or_id
	 *
	 * @return AmapressAdhesion
	 */
	public static function getBy( $post_or_id, $no_cache = false ) {
		if ( is_a( $post_or_id, 'WP_Post' ) ) {
			$post_id = $post_or_id->ID;
		} else if ( is_a( $post_or_id, 'AmapressAdhesion' ) ) {
			$post_id = $post_or_id->ID;
		} else {
			$post_id = intval( $post_or_id );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) || $no_cache ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				self::$entities_cache[ $post_id ] = null;
			} else {
				self::$entities_cache[ $post_id ] = new AmapressAdhesion( $post );
			}
		}

		return self::$entities_cache[ $post_id ];
	}

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

	private static $properties = null;

	public static function getProperties() {
		if ( null == self::$properties ) {
			$ret                         = [];
			$ret['contrat_type']         = [
				'desc' => 'Type du contrat (par ex, Légumes)',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getTitle();
				}
			];
			$ret['contrat_titre']        = [
				'desc' => 'Nom du contrat (par ex, Légumes 09/2018-08/2019)',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getTitle();
				}
			];
			$ret['contrat_lien']         = [
				'desc' => 'Lien vers la présentation du contrat',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getPermalink();
				}
			];
			$ret['date_debut']           = [
				'desc' => 'Date début du contrat (par ex, 22/09/2018)',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate_debut() );
				}
			];
			$ret['date_fin']             = [
				'desc' => 'Date fin du contrat (par ex, 22/09/2018)',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate_fin() );
				}
			];
			$ret['date_debut_complete']  = [
				'desc' => 'Date début du contrat (par ex, jeudi 22 septembre 2018)',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'D j M Y', $adh->getDate_debut() );
				}
			];
			$ret['date_fin_complete']    = [
				'desc' => 'Date fin du contrat (par ex, jeudi 22 septembre 2018)',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'D j M Y', $adh->getDate_fin() );
				}
			];
			$ret['tous_referents']       = [
				'desc' => 'Nom des référents du contrat',
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName();
						},
						$adh->getContrat_instance()->getModel()->getProducteur()->getReferentsIds()
					) ) );
				}
			];
			$ret['tous_referents_email'] = [
				'desc' => 'Nom des référents du contrat avec emails',
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName() . '(' . $ref->getEmail() . ')';
						},
						$adh->getContrat_instance()->getModel()->getProducteur()->getReferentsIds()
					) ) );
				}
			];
			$ret['referents']            = [
				'desc' => 'Nom des référents du contrat',
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName();
						},
						$adh->getContrat_instance()->getModel()->getProducteur()->getReferentsIds( $adh->getLieuId() )
					) ) );
				}
			];
			$ret['referents_email']      = [
				'desc' => 'Nom des référents du contrat avec emails',
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName() . '(' . $ref->getEmail() . ')';
						},
						$adh->getContrat_instance()->getModel()->getProducteur()->getReferentsIds( $adh->getLieuId() )
					) ) );
				}
			];
			$ret['adherent']             = [
				'desc' => 'Prénom Nom adhérent',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getDisplayName();
				}
			];
			$ret['adherent.nom']         = [
				'desc' => 'Nom adhérent',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getUser()->last_name;
				}
			];
			$ret['adherent.prenom']      = [
				'desc' => 'Prénom adhérent',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getUser()->first_name;
				}
			];
			$ret['adherent.adresse']     = [
				'desc' => 'Adresse adhérent',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getFormattedAdresse();
				}
			];
			$ret['adherent.tel']         = [
				'desc' => 'Téléphone adhérent',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getTelephone();
				}
			];
			$ret['adherent.email']       = [
				'desc' => 'Email adhérent',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getEmail();
				}
			];
			$ret['producteur.nom'] = [
				'desc' => 'Nom producteur',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getUser()->last_name;
				}
			];
			$ret['producteur.prenom'] = [
				'desc' => 'Prénom producteur',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getUser()->first_name;
				}
			];
			$ret['producteur.ferme'] = [
				'desc' => 'Nom de la ferme producteur',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getNomExploitation();
				}
			];
			$ret['producteur.ferme.adresse'] = [
				'desc' => 'Adresse de la ferme producteur',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getAdresseExploitation();
				}
			];
			$ret['producteur.adresse'] = [
				'desc' => 'Adresse producteur',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getFormattedAdresse();
				}
			];
			$ret['producteur.tel'] = [
				'desc' => 'Téléphone producteur',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getTelephone();
				}
			];
			$ret['producteur.email'] = [
				'desc' => 'Email producteur',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getEmail();
				}
			];
			$ret['lieu']                 = [
				'desc' => 'Lieu de distribution',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getLieu()->getLieuTitle();
				}
			];
			$ret['lieu_heure_debut'] = [
				'desc' => 'Heure de début de distribution',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'H:i', $adh->getLieu()->getHeure_debut() );
				}
			];
			$ret['lieu_heure_fin'] = [
				'desc' => 'Heure de fin de distribution',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'H:i', $adh->getLieu()->getHeure_fin() );
				}
			];
			$ret['contrat_debut']        = [
				'desc' => 'Début du contrat (mois/année)',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'm/Y', $adh->getContrat_instance()->getDate_debut() );
				}
			];
			$ret['contrat_fin']                 = [
				'desc' => 'Fin du contrat (mois/année)',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'm/Y', $adh->getContrat_instance()->getDate_fin() );
				}
			];
			$ret['contrat_debut_annee']         = [
				'desc' => 'Année de début du contrat',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'Y', $adh->getContrat_instance()->getDate_debut() );
				}
			];
			$ret['contrat_fin_annee']           = [
				'desc' => 'Année de fin du contrat',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'Y', $adh->getContrat_instance()->getDate_fin() );
				}
			];
			$ret['nb_paiements']                = [
				'desc' => 'Nombre de chèques choisi',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getPaiements();
				}
			];
			$ret['nb_distributions']            = [
				'desc' => 'Nombre de distributions restantes',
				'func' => function ( AmapressAdhesion $adh ) {
					return count( $adh->getRemainingDates() );
				}
			];
			$ret['dates_distribution_par_mois'] = [
				'desc' => 'Dates de distributions regroupées par mois',
				'func' => function ( AmapressAdhesion $adh ) {
					$dates         = $adh->getRemainingDates();
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
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', from( $adh->getRemainingDates() )->firstOrDefault() );
				}
			];
			$ret['derniere_date']               = [
				'desc' => 'Dernière date de distribution',
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', from( $adh->getRemainingDates() )->lastOrDefault() );
				}
			];
			$ret['dates_distribution']          = [
				'desc' => 'Liste des dates de distribution',
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_map( function ( $d ) {
						return date_i18n( 'd/m/Y', $d );
					}, $adh->getRemainingDates() ) );
				}
			];
			$ret['option_paiements']     = [
				'desc' => 'Option de paiement choisie',
				'func' => function ( AmapressAdhesion $adh ) {
					$o = $adh->getContrat_instance()->getChequeOptionsForTotal( $adh->getPaiements(), $adh->getTotalAmount() );

					return $o['desc'];
				}
			];
			$ret['quantites']            = [
				'desc' => 'Quantité(s) choisie(s)',
				'func' => function ( AmapressAdhesion $adh ) {
					if ( $adh->getContrat_instance()->isPanierVariable() ) {
						return $adh->getPaniersVariablesDescription();
					}

					return $adh->getContrat_quantites_AsString();
				}
			];
			$ret['quantites_prix']       = [
				'desc' => 'Quantité(s) choisie(s) avec prix unitaire',
				'func' => function ( AmapressAdhesion $adh ) {
					if ( $adh->getContrat_instance()->isPanierVariable() ) {
						return $adh->getPaniersVariablesDescription();
					}

					return $adh->getContrat_quantites_AsString( null, true );
				}
			];
			$ret['total']                = [
				'desc' => 'Total du contrat',
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getTotalAmount();
				}
			];
			self::$properties            = $ret;
		}

		return self::$properties;
	}

	public function getContratDocFileName() {
		$model_filename = $this->getContrat_instance()->getContratModelDocFileName();
		$ext            = strpos( $model_filename, '.docx' ) !== false ? '.docx' : '.odt';

		return trailingslashit( Amapress::getContratDir() ) . sanitize_file_name(
				'inscription-' . $this->getContrat_instance()->getModel()->getTitle() . '-' . $this->ID . '-' . $this->getAdherent()->getUser()->last_name . $ext );
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_contrat = false ) {
		$ret = [];

		foreach ( Amapress::getPlaceholdersHelpForProperties( self::getProperties() ) as $prop_name => $prop_desc ) {
			$ret[ $prop_name ] = $prop_desc;
		}
		$ret["quantite"]               = '(Tableau quantité) Libellé quantité';
		$ret["quantite_code"]          = '(Tableau quantité) Code quantité';
		$ret["quantite_nb_distrib"]    = '(Tableau quantité) Nombre de distribution restantes';
		$ret["quantite_sous_total"]    = '(Tableau quantité) Prix pour la quantité choisie';
		$ret["quantite_total"]         = '(Tableau quantité) Prix pour la quuantité choisie x nombre distrib';
		$ret["quantite_nombre"]        = '(Tableau quantité) Facteur quantité choisi';
		$ret["quantite_prix_unitaire"] = '(Tableau quantité) Prix à l\'unité';
		$ret["quantite_description"]   = '(Tableau quantité) Description de la quantité';
		$ret["quantite_unite"]         = '(Tableau quantité) Unité de la quantité';

		return Amapress::getPlaceholdersHelpTable( 'contrat-placeholders', $ret,
			'de l\'inscription', $additional_helps, ! $for_contrat,
			$for_contrat ? '${' : '%%', $for_contrat ? '}' : '%%' );
	}

	public function generateContratDoc() {
		$out_filename   = $this->getContratDocFileName();
		$model_filename = $this->getContrat_instance()->getContratModelDocFileName();
		if ( empty( $model_filename ) ) {
			return '';
		}

		$placeholders = [];
		foreach ( self::getProperties() as $prop_name => $prop_config ) {
			$placeholders[ $prop_name ] = call_user_func( $prop_config['func'], $this );
		}

		$remaining_distrib = count( $this->getRemainingDates() );
		$quants            = $this->getContrat_quantites( null );
//		if ( ! $this->getContrat_instance()->isPanierVariable() ) {
		$i = 1;
		foreach ( $quants as $quant ) {
			$placeholders["quantite#$i"]               = $quant->getTitle();
			$placeholders["quantite_code#$i"]          = $quant->getCode();
			$placeholders["quantite_nb_distrib#$i"]    = $remaining_distrib;
			$placeholders["quantite_sous_total#$i"]    = $quant->getPrice();
			$placeholders["quantite_total#$i"]         = $quant->getPrice() * $remaining_distrib;
			$placeholders["quantite_nombre#$i"]        = $quant->getFactor();
			$placeholders["quantite_prix_unitaire#$i"] = $quant->getContratQuantite()->getPrix_unitaire();
			$placeholders["quantite_description#$i"]   = $quant->getContratQuantite()->getDescription();
			$placeholders["quantite_unite#$i"]         = $quant->getContratQuantite()->getPriceUnitDisplay();
			$i                                         += 1;
		}
//		}

//		amapress_dump($model_filename);
//		\PhpOffice\PhpWord\Settings::setTempDir( Amapress::getAttachmentDir() );

		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor( $model_filename );
		try {
			$templateProcessor->cloneRow( 'quantite', count( $quants ) );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
		}
//		amapress_dump( $placeholders );
//		die();
		foreach ( $placeholders as $k => $v ) {
			$templateProcessor->setValue( $k, $v );
		}

		$templateProcessor->saveAs( $out_filename );

		return $out_filename;
	}

	public function getDate_debut() {
		return $this->getCustomAsDate( 'amapress_adhesion_date_debut' );
	}

	public function hasDate_fin() {
		$fin = $this->getCustom( 'amapress_adhesion_date_fin' );

		return ! empty( $fin );
	}

	public function getDate_fin() {
		$fin = $this->getCustomAsDate( 'amapress_adhesion_date_fin' );
		if ( ! empty( $fin ) ) {
			return $fin;
		}

		if ( $this->getContrat_instance() ) {
			return $this->getContrat_instance()->getDate_fin();
		} else {
			return 0;
		}
	}


	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_adhesion_contrat_instance', 'AmapressContrat_instance' );
	}

	/** @return int */
	public function getContrat_instanceId() {
		return $this->getCustomAsInt( 'amapress_adhesion_contrat_instance' );
	}

	/** @return int */
	public function isNotRenewable() {
		return $this->hasDate_fin();
	}

	public function markNotRenewable() {
		if ( $this->isNotRenewable() ) {
			return;
		}
		$date = Amapress::start_of_day( amapress_time() );
		if ( $this->getContrat_instance()->getDate_fin() < $date ) {
			$date = $this->getContrat_instance()->getDate_fin();
		}
		$this->setCustom( 'amapress_adhesion_date_fin', $date );
		$this->setCustom( 'amapress_adhesion_fin_raison', 'Non renouvellement' );
		amapress_compute_post_slug_and_title( $this->post );
	}

	/**
	 * @return AmapressAdhesionQuantite[]
	 */
	public function getContrat_quantites( $dist_date ) {
		$factors = $this->getCustomAsFloatArray( 'amapress_adhesion_contrat_quantite_factors' );
		$quants  = $this->getCustomAsEntityArray( 'amapress_adhesion_contrat_quantite', 'AmapressContrat_quantite' );

//		amapress_dump($this->getCustom('amapress_adhesion_contrat_quantite'));
		$date_factor = 1;
		if ( $this->getContrat_instanceId() ) {
			$rattrapage = $this->getContrat_instance()->getRattrapage();
			foreach ( $rattrapage as $r ) {
				if ( Amapress::start_of_day( intval( $r['date'] ) ) == Amapress::start_of_day( $dist_date ) ) {
					$date_factor = floatval( $r['quantite'] );
					break;
				}
			}
		}
		$ret = [];
		foreach ( $quants as $quant ) {
			$factor = 1;
			if ( isset( $factors[ $quant->ID ] ) && $factors[ $quant->ID ] > 0 ) {
				$factor = $factors[ $quant->ID ];
			}
			$ret[ $quant->ID ] = new AmapressAdhesionQuantite( $quant, $factor * $date_factor );
		}

		return $ret;
	}

	/** @return array */
	public function getPaniersVariables() {
		return $this->getCustomAsArray( 'amapress_adhesion_panier_variables' );
	}

	public function getVariables_Contrat_quantites( $date ) {
		if ( ! $this->getContrat_instance()->isPanierVariable() ) {
			return array();
		}

		$quants      = array();
		$quant_by_id = AmapressContrats::get_contrat_quantites( $this->getContrat_instanceId() );
		$quant_by_id = array_combine( array_map( function ( $c ) {
			return $c->ID;
		}, $quant_by_id ), $quant_by_id );
		$paniers     = $this->getPaniersVariables();
		if ( isset( $paniers[ $date ] ) ) {
			$panier = $paniers[ $date ];
			foreach ( $panier as $quant_id => $quant ) {
				if ( $quant > 0 ) {
					/** @var AmapressContrat_quantite $contrat_quant */
					$contrat_quant = $quant_by_id[ $quant_id ];
					$quants[]      = array(
						'quantite'         => $quant,
						'contrat_quantite' => $contrat_quant
					);
				}
			}
		}

		return $quants;
	}

	public function getPaniersVariablesDescription() {
		$dates_desc = [];
		foreach ( $this->getPaniersVariables() as $k => $v ) {
			$date         = intval( $k );
			$quant_labels = $this->getContrat_quantites_AsString( $date );
			if ( ! empty( $quant_labels ) ) {
				$dates_desc[] = date_i18n( 'd/m/Y', $date ) . ' : ' . $quant_labels;
			}
		}

		return implode( '<br/>', $dates_desc );
	}

	public function getContrat_quantites_AsString( $date = null, $show_price_unit = false ) {
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$quant_labels = array();
			foreach ( $this->getVariables_Contrat_quantites( $date ) as $q ) {
				/** @var AmapressContrat_quantite $contrat_quantite */
				$contrat_quantite = $q['contrat_quantite'];
				$quantite         = $q['quantite'];
				$quant_labels[]   = esc_html( $contrat_quantite->formatValue( $quantite ) . ' x ' . $contrat_quantite->getTitle() . ( $show_price_unit ? ' à ' . $contrat_quantite->getPrix_unitaire() . '€' : '' ) );
			}

			return implode( ', ', $quant_labels );
		} else {
			$quant_labels = array_map(
				function ( $vv ) use ( $show_price_unit ) {
					/** @var AmapressAdhesionQuantite $vv */
					return $vv->getTitle() . ( $show_price_unit ? ' à ' . $vv->getPrice() . '€' : '' );
				}
				, $this->getContrat_quantites( $date ) );

			return implode( ', ', $quant_labels );
		}
	}

	public function getContrat_quantites_Codes_AsString( $date = null ) {
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			return 'Var.';
		}

		$codes = array_map( function ( $vv ) {
			/** @var AmapressAdhesionQuantite $vv */
			return $vv->getCode();
		}, $this->getContrat_quantites( $date ) );
//		$quants = array_map( function ( $q ) {
//			/** @var AmapressContrat_quantite $q */
//			return $q->getQuantite();
//		}, $this->getContrat_quantites() );
//		$titles = array_map( function ( $q ) {
//			/** @var AmapressContrat_quantite $q */
//			$c = $q->getCode();
//
//			return ! empty( $c ) ? $c : $q->getTitle();
//		}, $this->getContrat_quantites() );
//		if ( count( array_unique( $quants ) ) == count( $this->getContrat_quantites() ) ) {
//			return implode( ',', $quants );
//		} else if ( count( array_unique( $codes ) ) == count( $this->getContrat_quantites() ) ) {
		return implode( ',', $codes );
//		} else {
//			return implode( ',', $titles );
//		}
	}

	public function getContrat_quantites_IDs() {
		return $this->getCustomAsIntArray( 'amapress_adhesion_contrat_quantite' );
	}

	public function getContrat_quantites_Price( $date = null ) {
		$sum = 0;
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$quant_by_id = AmapressContrats::get_contrat_quantites( $this->getContrat_instanceId() );
			$quant_by_id = array_combine( array_map( function ( $c ) {
				return $c->ID;
			}, $quant_by_id ), $quant_by_id );
			$paniers     = $this->getPaniersVariables();
			if ( isset( $paniers[ $date ] ) ) {
				$panier = $paniers[ $date ];
				foreach ( $panier as $quant_id => $quant ) {
					/** @var AmapressContrat_quantite $contrat_quant */
					$contrat_quant = $quant_by_id[ $quant_id ];
					$sum           += $contrat_quant->getPrix_unitaire() * $quant;
				}
			}
		} else {
			foreach ( $this->getContrat_quantites( $date ) as $c ) {
				$sum += $c->getPrice();
			}
		}

		return $sum;
	}

//    public function getContrat_quantites_Quants($date = null)
//    {
//        $sum = 0;
//        foreach ($this->getContrat_quantites() as $c) {
//            $sum += $c->getQuantite();
//        }
//        return $sum;
//    }

	public function getRemainingDates() {
		$dates      = $this->getContrat_instance()->getListe_dates();
		$start_date = Amapress::start_of_day( $this->getDate_debut() );

		return array_filter( $dates, function ( $d ) use ( $start_date ) {
			return Amapress::start_of_day( $d ) >= $start_date;
		} );
	}

	/** @return int */
	public function getPaiements() {
		return $this->getCustom( 'amapress_adhesion_paiements', 0 );
	}

	/** @return AmapressUser */
	public function getAdherent() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent', 'AmapressUser' );
	}

	public function getAdherentId() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent' );
	}

	public function setAdherent( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent', $value );
	}

	public function getAdherent2() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent2', 'AmapressUser' );
	}

	public function getAdherent2Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent2' );
	}

	public function setAdherent2( $value ) {
		update_post_meta( $this->post->ID, 'amapress_adhesion_adherent2', $value );
	}

	public function getAdherent3() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent3', 'AmapressUser' );
	}

	public function getAdherent3Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent3' );
	}

	public function getAdherent4() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent4', 'AmapressUser' );
	}

	public function getAdherent4Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent4' );
	}

	public function setAdherent3( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent3', $value );
	}

	public function setAdherent4( $value ) {
		$this->setCustom( 'amapress_adhesion_adherent4', $value );
	}

	/** @return AmapressLieu_distribution */
	public function getLieu() {
		return $this->getCustomAsEntity( 'amapress_adhesion_lieu', 'AmapressLieu_distribution' );
	}

	public function getLieuId() {
		return $this->getCustomAsInt( 'amapress_adhesion_lieu' );
	}

	public function getMessage() {
		return $this->getCustom( 'amapress_adhesion_message' );
	}

	public function getStatusDisplay() {
		switch ( $this->getCustom( 'amapress_adhesion_status' ) ) {

			case self::TO_CONFIRM:
				return 'A confirmer';
			case self::CONFIRMED:
				return 'Confirmée';
			default:
				return $this->getCustom( 'amapress_adhesion_status' );
		}
	}

	public function getStatus() {
		return $this->getCustom( 'amapress_adhesion_status' );
	}

	public function setStatus( $status ) {
		$this->setCustom( 'amapress_adhesion_status', $status );
		delete_transient( 'amps_adh_to_confirm' );
	}

	/** @return float */
	public function getTotalAmount() {
		if ( ! $this->getContrat_instanceId() ) {
			return 0;
		}
		$dates = $this->getRemainingDates();
//		if ( $this->getContrat_instance()->isPanierVariable() ) {
		$sum = 0;
		foreach ( $dates as $date ) {
			$sum += $this->getContrat_quantites_Price( $date );
		}

		return $sum;
//		} else {
//			return count( $dates ) * $this->getContrat_quantites_Price();
//		}
	}


	private static $paiement_cache = null;

	/** @return  AmapressAdhesion[] */
	public static function getAllActiveByUserId() {
		if ( null === self::$paiement_cache ) {
			global $wpdb;
			$coadhs = array_group_by(
				$wpdb->get_results(
					"SELECT DISTINCT $wpdb->usermeta.meta_value, $wpdb->usermeta.user_id
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3')" ),
				function ( $o ) {
					return intval( $o->user_id );
				} );

			$cache = array();
			foreach ( AmapressContrats::get_active_adhesions() as $adh ) {
				$user_ids = array( $adh->getAdherentId() );
				if ( $adh->getAdherent2Id() ) {
					$user_ids[] = $adh->getAdherent2Id();
				}
				if ( $adh->getAdherent3Id() ) {
					$user_ids[] = $adh->getAdherent3Id();
				}
				if ( $adh->getAdherent4Id() ) {
					$user_ids[] = $adh->getAdherent4Id();
				}
				foreach ( $user_ids as $user_id ) {
					if ( isset( $coadhs[ $user_id ] ) ) {
						foreach ( $coadhs[ $user_id ] as $co ) {
							$user_ids[] = $co->meta_value;
						}
					}
				}
				$user_ids = array_unique( $user_ids );
				foreach ( $user_ids as $user_id ) {
					if ( ! isset( $cache[ $user_id ] ) ) {
						$cache[ $user_id ] = array();
					}
					$cache[ $user_id ][] = $adh;
				}
			}
			self::$paiement_cache = $cache;
		}

		return self::$paiement_cache;
	}

	/**
	 * @return AmapressAdhesion[]
	 */
	public static function getUserActiveAdhesions(
		$user_id = null,
		$contrat_instance_id = null,
		$date = null,
		$ignore_renouv_delta = false,
		$allow_not_logged = false
	) {
		return array_map( function ( $p ) {
			return AmapressAdhesion::getBy( $p );
		}, self::getUserActiveAdhesionIds( $user_id, $contrat_instance_id, $date, $ignore_renouv_delta, $allow_not_logged ) );
	}

	/**
	 * @return int[]
	 */
	public static function getUserActiveAdhesionIds(
		$user_id = null,
		$contrat_instance_id = null,
		$date = null,
		$ignore_renouv_delta = false,
		$allow_not_logged = false,
		$include_futur = true
	) {
		if ( ! $allow_not_logged && ! amapress_is_user_logged_in() ) {
			return [];
		}

		if ( empty( $date ) ) {
			$date = Amapress::end_of_day( amapress_time() );
		}

		if ( $user_id == null ) {
			$user_id = amapress_current_user_id();
		}

		$abo_ids = AmapressContrats::get_active_contrat_instances_ids( $contrat_instance_id, $date, $ignore_renouv_delta, $include_futur );
		$abo_key = implode( '-', $abo_ids );

		$key = "AmapressAdhesion::getUserActiveAdhesions_{$date}_{$user_id}_{$abo_key}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$user_ids = AmapressContrats::get_related_users( $user_id, $allow_not_logged );
			if ( empty( $user_ids ) ) {
				return [];
			}
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'amapress_adhesion_adherent',
					'value'   => $user_ids,
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				),
				array(
					'key'     => 'amapress_adhesion_contrat_instance',
					'value'   => $abo_ids,
					'compare' => 'IN',
					'type'    => 'NUMERIC'
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'amapress_adhesion_date_fin',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'amapress_adhesion_date_fin',
						'value'   => 0,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'amapress_adhesion_date_fin',
						'value'   => Amapress::end_of_day( $date ),
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				)
			);
			if ( ! $include_futur ) {
				$meta_query[] = array(
					'key'     => 'amapress_adhesion_date_debut',
					'value'   => Amapress::end_of_day( $date ),
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
			}
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
				'fields'         => 'ids',
				'meta_query'     => $meta_query
			);
			$res   = get_posts( $query );

			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAmapien_paiement[]
	 */
	public function getAllPaiements() {
		$key = "amapress_get_contrat_paiements_{$this->ID}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'amapress_contrat_paiement_adhesion',
						'value'   => $this->ID,
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				),
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_key'       => 'amapress_contrat_paiement_date'
			);
			$res   = array_map( function ( $p ) {
				return new AmapressAmapien_paiement( $p );
			}, get_posts( $query ) );
			wp_cache_set( $key, $res );
		}

		return $res;
	}

	/**
	 * @return AmapressAdhesionQuantite[]
	 */
	public static function getQuantitesForUser( $user_id = null, $contrat_id = null, $date = null, $ignore_renouv_delta = false ) {
		$key_ids = is_array( $contrat_id ) ? implode( '-', $contrat_id ) : $contrat_id;
		$key     = "amapress_get_user_active_adhesion_quantites_{$user_id}_{$key_ids}_{$date}_{$ignore_renouv_delta}";
		$res     = wp_cache_get( $key );
		if ( false === $res ) {
			/** @var AmapressAdhesion[] $ret */
			$ret = AmapressAdhesion::getUserActiveAdhesions( $user_id, $contrat_id, $date, $ignore_renouv_delta );

			$res = array();
			foreach ( $ret as $adh ) {
//                $adh = AmapressAdhesion::getBy($v);
				foreach ( $adh->getContrat_quantites( $date ) as $q ) {
					$res[ $q->getId() ] = $q;
				}
			}
//            $res = array_unique($res);
			wp_cache_set( $key, $res );
		}

		return $res;
	}


	public function getNextContratInstanceId() {
		$contrat_instance = $this->getContrat_instance();
		if ( ! $contrat_instance ) {
			return 0;
		}

		$all_contrats = AmapressContrats::get_active_contrat_instances();

		return from( $all_contrats )->where( function ( $c ) use ( $contrat_instance ) {
			/** @var AmapressContrat_instance $c */
			return $c->ID != $contrat_instance->ID
			       && $c->getModelId() == $contrat_instance->getModelId()
			       && ( $c->getSubName() == $contrat_instance->getSubName() || ( empty( $c->getSubName() ) && empty( $contrat_instance->getSubName() ) ) )
			       && $c->getDate_debut() > $this->getDate_debut();
		} )->select( function ( $c ) {
			/** @var AmapressContrat_instance $c */
			return $c->ID;
		} )->firstOrDefault( 0 );
	}

	public function canRenew() {
		$new_contrat_id = $this->getNextContratInstanceId();

		return $new_contrat_id
		       && $new_contrat_id != $this->getContrat_instanceId()
		       && ! $this->isNotRenewable();
	}

	public function cloneAdhesion( $as_draft = true ) {
		if ( ! $this->canRenew() ) {
			return null;
		}

		$new_contrat_instance_id = $this->getNextContratInstanceId();

//        $add_weeks = Amapress::datediffInWeeks($this->getContrat_instance()->getDate_debut(), $this->getContrat_instance()->getDate_fin());
		$meta = array();
		foreach ( $this->custom as $k => $v ) {
			$meta[ $k ] = $v;
		}

		$new_contrat = AmapressContrat_instance::getBy( $new_contrat_instance_id );
		$date_debut  = $new_contrat->getDate_debut();

//        $date_debut = Amapress::add_a_week($this->getDate_debut(), $add_weeks);

		$meta['amapress_adhesion_date_debut'] = $date_debut;
		unset( $meta['amapress_adhesion_date_fin'] );
		unset( $meta['amapress_adhesion_fin_raison'] );
		unset( $meta['amapress_adhesion_fin_raison'] );
		unset( $meta['amapress_adhesion_paiements'] );
		unset( $meta['amapress_adhesion_panier_variables'] );
		$meta['amapress_adhesion_contrat_instance'] = $new_contrat_instance_id;

		$quants     = $this->getContrat_quantites( $date_debut );
		$new_quants = AmapressContrats::get_contrat_quantites( $new_contrat_instance_id );

		$old_factors = ! empty( $meta['amapress_adhesion_contrat_quantite_factors'] ) ? $meta['amapress_adhesion_contrat_quantite_factors'] : [];
		unset( $meta['amapress_adhesion_contrat_quantite_factors'] );

		$new_quants_ids     = array();
		$new_quants_factors = array();
		foreach ( $quants as $quant ) {
			foreach ( $new_quants as $new_quant ) {
				if ( $new_quant->getCode() == $quant->getContratQuantite()->getCode()
				     || $new_quant->getTitle() == $quant->getContratQuantite()->getTitle()
				) {
					$new_quants_ids[] = $new_quant->ID;
					if ( isset( $old_factors[ $quant->getId() ] ) ) {
						$new_quants_factors[ $new_quant->ID ] = $old_factors[ $quant->getId() ];
					}
				}
			}
		}
		$meta['amapress_adhesion_contrat_quantite'] = $new_quants_ids;
		if ( ! empty( $new_quants_factors ) ) {
			$meta['amapress_adhesion_contrat_quantite_factors'] = $new_quants_factors;
		}

		$my_post = array(
			'post_title'   => $this->getTitle(),
			'post_type'    => AmapressAdhesion::INTERNAL_POST_TYPE,
			'post_content' => '',
			'post_status'  => $as_draft ? 'draft' : 'publish',
			'meta_input'   => $meta,
		);
		$new_id  = wp_insert_post( $my_post );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return null;
		}

		update_post_meta( $this->ID, 'amapress_adhesion_renewed', 1 );

		return AmapressAdhesion::getBy( $new_id );
	}

	public function preparePaiements() {
		$contrat_instance        = $this->getContrat_instance();
		$contrat_paiements_dates = $contrat_instance->getPaiements_Liste_dates();
		$nb_paiements            = $this->getPaiements();
		$contrat_paiements       = $this->getAllPaiements();
		$all_paiements           = AmapressContrats::get_all_paiements( $contrat_instance->ID );

		$all_paiements_by_dates = array_group_by( $all_paiements,
			function ( AmapressAmapien_paiement $p ) {
				return Amapress::start_of_day( $p->getDate() );
			}
		);
		foreach ( $contrat_paiements_dates as $d ) {
			if ( ! isset( $all_paiements_by_dates[ $d ] ) ) {
				$all_paiements_by_dates[ $d ] = array();
			}
		}
		$dates_by_cheque_count = array_combine(
			array_map( function ( $v, $k ) {
				$unit = count( $v );

				return sprintf( '%05d-%8x', $unit, $k );
			}, array_values( $all_paiements_by_dates ), array_keys( $all_paiements_by_dates ) ),
			array_keys( $all_paiements_by_dates )
		);
		ksort( $dates_by_cheque_count );
		$dates_by_cheque_count = array_values( $dates_by_cheque_count );
//		$all_quants            = array_merge( array( '_all' ),
//			array_map( function ( AmapressContrat_quantite $p ) {
//				$code = $p->getCode();
//
//				return ! empty( $code ) ? $code : $p->getQuantite();
//			}, AmapressContrats::get_contrat_quantites( $contrat_instance->ID ) ) );
//		foreach ( $all_paiements_by_dates as $k => $v ) {
//			$all_paiements_by_dates[ $k ] = array_merge( array( '_all' => $v ),
//				array_group_by( $v, function ( AmapressAmapien_paiement $p ) use ( $k ) {
//					return implode( ',', array_map( function ( $vv ) {
//						/** @var AmapressAdhesionQuantite $vv */
//						$code = $vv->getContratQuantite()->getCode();
//
//						return ! empty( $code ) ? $code : $vv->getContratQuantite()->getTitle();
//					}, $p->getAdhesion()->getContrat_quantites( $k ) ) );
//				} )
//			);
//		}

		if ( count( $contrat_paiements ) < $nb_paiements ) {
			$diff = $nb_paiements - count( $contrat_paiements );
			for ( $i = 0; $i < $diff; $i ++ ) {
				$contrat_paiements[] = null;
			}
		}

		$new_paiement_date = array();
		$def_date          = 0;
		foreach ( $contrat_paiements as $paiement ) {
			if ( ! $paiement ) {
				$new_paiement_date[] = ( isset( $dates_by_cheque_count[ $def_date ] ) ? $dates_by_cheque_count[ $def_date ++ ] : 0 );
			}
		}
		sort( $new_paiement_date );

		$nb_paiements      = count( $contrat_paiements );
		$paiements_options = $contrat_instance->getChequeOptionsForTotal( $nb_paiements, $this->getTotalAmount() );
		$amounts           = [];
		if ( $paiements_options['remain_amount'] > 0 ) {
			for ( $i = 0; $i < $nb_paiements - 1; $i ++ ) {
				$amounts[] = $paiements_options['main_amount'];
			}
			$amounts[] = $paiements_options['remain_amount'];
		} else {
			for ( $i = 0; $i < $nb_paiements; $i ++ ) {
				$amounts[] = $paiements_options['main_amount'];
			}
		}
		$def_date = 0;
		$def_id   = - 1;
		foreach ( $contrat_paiements as $paiement ) {
			$id       = $paiement ? $paiement->ID : $def_id --;
			$numero   = $paiement ? $paiement->getNumero() : '';
			$banque   = $paiement ? $paiement->getBanque() : '';
			$adherent = $paiement ? $paiement->getEmetteur() : $this->getAdherent()->getDisplayName();
			if ( empty( $adherent ) ) {
				$adherent = $this->getAdherent()->getDisplayName();
			}
			$amount      = array_shift( $amounts );
			$status      = $paiement ? $paiement->getStatus() : 'not_received';
			$paiement_dt = $paiement ? Amapress::start_of_day( $paiement->getDate() ) : ( isset( $new_paiement_date[ $def_date ] ) ? $new_paiement_date[ $def_date ++ ] : 0 );

			$my_post = array(
				'post_type'    => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'post_content' => '',
				'post_status'  => 'publish',
				'meta_input'   => array(
					'amapress_contrat_paiement_adhesion'         => $this->ID,
					'amapress_contrat_paiement_contrat_instance' => $contrat_instance->ID,
					'amapress_contrat_paiement_date'             => $paiement_dt,
					'amapress_contrat_paiement_status'           => $status,
					'amapress_contrat_paiement_amount'           => $amount,
					'amapress_contrat_paiement_numero'           => $numero,
					'amapress_contrat_paiement_emetteur'         => $adherent,
					'amapress_contrat_paiement_banque'           => $banque,
				),
			);
			if ( $id < 0 ) {
				wp_insert_post( $my_post );
			} else {
				$my_post['ID'] = $id;
				wp_update_post( $my_post, true );
			}
		}
	}

	public static function getAdhesionToConfirmCount() {
		$current_user_id = amapress_current_user_id();
		$cnt             = get_transient( 'amps_adh_to_confirm' );
		if ( false === $cnt ) {
			$cnt = [];
		}
		if ( ! isset( $cnt[ $current_user_id ] ) ) {
			$cnt[ $current_user_id ] = get_posts_count( 'post_type=amps_adhesion&amapress_date=active&amapress_status=to_confirm' );
			set_transient( 'amps_adh_to_confirm', $cnt );
		}

		return $cnt[ $current_user_id ];
	}
}