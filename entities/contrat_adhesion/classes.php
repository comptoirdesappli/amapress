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
		$this->factor   = $factor;
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
		//if ( $quant->getContrat_instance() && $quant->getContrat_instance()->isQuantiteVariable() ) {
		if ( abs( $this->getFactor() ) < 0.001 ) {
			return __( 'Aucun', 'amapress' );
		} else if ( $this->getFactor() != 1 ) {
			return $this->getFactor() . ' x ' . $quant->getCode();
		} else {
			return $quant->getCode();
		}
//		} else {
//			return $quant->getCode();
//		}
	}

	public function getGroupName() {
		return $this->getContratQuantite()->getGroupName();
	}

	public function getTitleWithoutGroup() {
		return $this->getContratQuantite()->getTitleWithoutGroup();
	}

	/**
	 * @return string
	 */
	public function getTitleWithFactor( $as_html = false ) {
		return $this->getContratQuantite()->getFormattedTitle( $this->getFactor(), $as_html );
	}

	public function getTitleWithoutFactor() {
		return $this->getContratQuantite()->getTitle();
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

	public function getGroupMultiple() {
		return $this->getContratQuantite()->getGroupMultiple();
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
		if ( $no_cache ) {
			unset( self::$entities_cache[ $post_id ] );
		}
		if ( ! isset( self::$entities_cache[ $post_id ] ) ) {
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
			$ret                                     = [];
			$ret['inscription_admin_link']           = [
				'desc' => __( 'Lien vers l\'inscription (Tableau de Bord>Gestion Contrat>Inscriptions)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return Amapress::makeLink( $adh->getAdminEditLink(), $adh->getAdherent()->getDisplayName() );
				}
			];
			$ret['contrat_type']                     = [
				'desc' => __( 'Type du contrat (par ex, Légumes)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModelTitle();
				}
			];
			$ret['contrat_titre_complet']            = [
				'desc' => __( 'Nom du contrat (par ex, Légumes 09/2018-08/2019 - Semaine A)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( ! empty( $adh->getContrat_instance()->getSubName() ) ) {
						return $adh->getContrat_instance()->getTitle() . ' - ' . $adh->getContrat_instance()->getSubName();
					} else {
						return $adh->getContrat_instance()->getTitle();
					}
				}
			];
			$ret['contrat_titre']                    = [
				'desc' => __( 'Nom du contrat (par ex, Légumes 09/2018-08/2019)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getTitle();
				}
			];
			$ret['contrat_sous_titre'] = [
				'desc' => __( 'Nom complémentaire du contrat (par ex, Semaine A)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getSubName();
				}
			];
			$ret['contrat_lien'] = [
				'desc' => __( 'Lien vers la présentation du contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getPermalink();
				}
			];
			$ret['date_debut'] = [
				'desc' => __( 'Date début du contrat (par ex, 22/09/2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate_debut() );
				}
			];
			$ret['date_fin'] = [
				'desc' => __( 'Date fin du contrat (par ex, 22/09/2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', $adh->getDate_fin() );
				}
			];
			$ret['date_debut_lettre'] = [
				'desc' => __( 'Date début du contrat (par ex, 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'j F Y', $adh->getDate_debut() );
				}
			];
			$ret['date_fin_lettre'] = [
				'desc' => __( 'Date fin du contrat (par ex, 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'j F Y', $adh->getDate_fin() );
				}
			];
			$ret['date_debut_complete'] = [
				'desc' => __( 'Date début du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'l j F Y', $adh->getDate_debut() );
				}
			];
			$ret['date_fin_complete'] = [
				'desc' => __( 'Date fin du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'l j F Y', $adh->getDate_fin() );
				}
			];
			$ret['date_ouverture'] = [
				'desc' => __( 'Date d\'ouverture du contrat (par ex, 22/09/2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', $adh->getContrat_instance()->getDate_ouverture() );
				}
			];
			$ret['date_cloture'] = [
				'desc' => __( 'Date de clôture du contrat (par ex, 22/09/2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', $adh->getContrat_instance()->getDate_cloture() );
				}
			];
			$ret['date_ouverture_lettre'] = [
				'desc' => __( 'Date d\'ouverture du contrat (par ex, 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'j F Y', $adh->getContrat_instance()->getDate_ouverture() );
				}
			];
			$ret['date_cloture_lettre'] = [
				'desc' => __( 'Date de clôture du contrat (par ex, 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'j F Y', $adh->getContrat_instance()->getDate_cloture() );
				}
			];
			$ret['date_ouverture_complete'] = [
				'desc' => __( 'Date d\'ouverture du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'l j F Y', $adh->getContrat_instance()->getDate_ouverture() );
				}
			];
			$ret['date_cloture_complete'] = [
				'desc' => __( 'Date de clôture du contrat (par ex, jeudi 22 septembre 2018)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'l j F Y', $adh->getContrat_instance()->getDate_cloture() );
				}
			];
			$ret['mention_speciale'] = [
				'desc' => __( 'Champ Mention spéciale du contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getSpecialMention();
				}
			];
			$ret['tous_referents'] = [
				'desc' => __( 'Nom des référents du contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName();
						},
						$adh->getContrat_instance()->getModel()->getReferentsIds()
					) ) );
				}
			];
			$ret['tous_referents_email']             = [
				'desc' => __( 'Nom des référents du contrat avec emails', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName() . '(' . $ref->getEmail() . ')';
						},
						$adh->getContrat_instance()->getModel()->getReferentsIds()
					) ) );
				}
			];
			$ret['tous_referents_contacts']          = [
				'desc' => __( 'Nom des référents du contrat avec contacts', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName() . ' (' . $ref->getEmail() . '/' . $ref->getTelTo( 'both', false, false, '/' ) . ')';
						},
						$adh->getContrat_instance()->getModel()->getReferentsIds()
					) ) );
				}
			];
			$ret['message'] = [
				'desc' => __( 'Message aux référents lors de l\'inscription', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getMessage();
				}
			];
			$ret['message_producteur'] = [
				'desc' => __( 'Message de commande au producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getProducteurMessage();
				}
			];
			$ret['referents'] = [
				'desc' => __( 'Nom des référents du contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName();
						},
						$adh->getContrat_instance()->getModel()->getReferentsIds( $adh->getLieuId() )
					) ) );
				}
			];
			$ret['referents_email']                  = [
				'desc' => __( 'Nom des référents du contrat avec emails', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName() . '(' . $ref->getEmail() . ')';
						},
						$adh->getContrat_instance()->getModel()->getReferentsIds( $adh->getLieuId() )
					) ) );
				}
			];
			$ret['referents_contacts']               = [
				'desc' => __( 'Nom des référents du contrat avec contacts', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_unique( array_map(
						function ( $ref_id ) {
							$ref = AmapressUser::getBy( $ref_id );
							if ( empty( $ref ) ) {
								return '';
							}

							return $ref->getDisplayName() . ' (' . $ref->getEmail() . '/' . $ref->getTelTo( 'both', false, false, '/' ) . ')';
						},
						$adh->getContrat_instance()->getModel()->getReferentsIds( $adh->getLieuId() )
					) ) );
				}
			];
			$ret['adherent']                         = [
				'desc' => __( 'Prénom Nom adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getDisplayName();
				}
			];
			$ret['adherent.type']                    = [
				'desc' => __( 'Type d\'adhérent (Principal, Co-adhérent...)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getAdherentTypeDisplay();
				}
			];
			$ret['adherent.pseudo']                  = [
				'desc' => __( 'Pseudo adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getUser()->nickname;
				}
			];
			$ret['adherent.nom_public']              = [
				'desc' => __( 'Nom public adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getUser()->display_name;
				}
			];
			$ret['adherent.nom']                     = [
				'desc' => __( 'Nom adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getUser()->last_name;
				}
			];
			$ret['adherent.prenom']                  = [
				'desc' => __( 'Prénom adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getUser()->first_name;
				}
			];
			$ret['adherent.adresse'] = [
				'desc' => __( 'Adresse adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getFormattedAdresse();
				}
			];
			$ret['adherent.code_postal'] = [
				'desc' => __( 'Code postal adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getCode_postal();
				}
			];
			$ret['adherent.ville'] = [
				'desc' => __( 'Ville adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getVille();
				}
			];
			$ret['adherent.rue'] = [
				'desc' => __( 'Rue (adresse) adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getAdresse();
				}
			];
			$ret['adherent.tel'] = [
				'desc' => __( 'Téléphone adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getTelephone();
				}
			];
			$ret['adherent.email'] = [
				'desc' => __( 'Email adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getEmail();
				}
			];
			$ret['cofoyers.noms'] = [
				'desc' => __( 'Liste des membres du foyer (Prénom, Nom)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getCoAdherentsList( false, false, true, null, false );

				}
			];
			$ret['cofoyers.contacts'] = [
				'desc' => __( 'Liste des membres du foyer (Prénom, Nom, Emails, Tel)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getCoAdherentsList( true, false, true, null, false );
				}
			];
			$ret['coadherents.noms'] = [
				'desc' => __( 'Liste des co-adhérents (non membres du foyer) (Prénom, Nom)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getCoAdherentsList( false, false, false, $adh->getContrat_instanceId() );

				}
			];
			$ret['coadherents.contacts'] = [
				'desc' => __( 'Liste des co-adhérents (non membres du foyer) (Prénom, Nom, Emails, Tel)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getCoAdherentsList( true, false, false, $adh->getContrat_instanceId() );
				}
			];
			$ret['touscoadherents.noms'] = [
				'desc' => __( 'Liste de tous les co-adhérents/membres du foyer (Prénom, Nom)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getCoAdherentsList( false );

				}
			];
			$ret['touscoadherents.contacts'] = [
				'desc' => __( 'Liste de tous les co-adhérents/membres du foyer (Prénom, Nom, Emails, Tel)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getAdherent()->getCoAdherentsList( true );
				}
			];
			$ret['coadherent'] = [
				'desc' => __( 'Prénom Nom co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getDisplayName();
				}
			];
			$ret['coadherent.pseudo']                = [
				'desc' => __( 'Pseudo co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getUser()->nickname;
				}
			];
			$ret['coadherent.nom_public']            = [
				'desc' => __( 'Nom public co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getUser()->display_name;
				}
			];
			$ret['coadherent.nom']                   = [
				'desc' => __( 'Nom co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getUser()->last_name;
				}
			];
			$ret['coadherent.prenom']                = [
				'desc' => __( 'Prénom co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getUser()->first_name;
				}
			];
			$ret['coadherent.adresse']               = [
				'desc' => __( 'Adresse co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getFormattedAdresse();
				}
			];
			$ret['coadherent.tel']                   = [
				'desc' => __( 'Téléphone co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getTelephone();
				}
			];
			$ret['coadherent.email']                 = [
				'desc' => __( 'Email co-adhérent', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$coadh = $adh->getAdherent2();
					if ( ! $coadh ) {
						$coadh = $adh->getAdherent()->getFirstCoAdherent();
						if ( ! $coadh ) {
							return '';
						}
					}

					return $coadh->getEmail();
				}
			];
			$ret['producteur.pseudo']                = [
				'desc' => __( 'Pseudo producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getUser()->nickname;
				}
			];
			$ret['producteur.nom_public']            = [
				'desc' => __( 'Nom public producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getUser()->display_name;
				}
			];
			$ret['producteur.nom']                   = [
				'desc' => __( 'Nom producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getUser()->last_name;
				}
			];
			$ret['producteur.prenom']                = [
				'desc' => __( 'Prénom producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getUser()->first_name;
				}
			];
			$ret['producteur.ferme']                 = [
				'desc' => __( 'Nom de la ferme producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getNomExploitation();
				}
			];
			$ret['producteur.ferme.adresse']         = [
				'desc' => __( 'Adresse de la ferme producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getModel()->getProducteur()->getAdresseExploitation();
				}
			];
			$ret['producteur.adresse']               = [
				'desc' => __( 'Adresse complète producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getFormattedAdresse();
				}
			];
			$ret['producteur.rue']                   = [
				'desc' => __( 'Adresse producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getAdresse();
				}
			];
			$ret['producteur.code_postal']           = [
				'desc' => __( 'Code postal producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getCode_postal();
				}
			];
			$ret['producteur.ville']                 = [
				'desc' => __( 'Ville producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getVille();
				}
			];
			$ret['producteur.tel']                   = [
				'desc' => __( 'Téléphone producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getTelephone();
				}
			];
			$ret['producteur.email']                 = [
				'desc' => __( 'Email producteur', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( empty( $adh->getContrat_instance() )
					     || empty( $adh->getContrat_instance()->getModel() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur() )
					     || empty( $adh->getContrat_instance()->getModel()->getProducteur()->getUser() ) ) {
						return '';
					}

					return $adh->getContrat_instance()->getModel()->getProducteur()->getUser()->getEmail();
				}
			];
			$ret['lieu']                             = [
				'desc' => __( 'Lieu de distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getLieu()->getLieuTitle();
				}
			];
			$ret['lieu_court']                       = [
				'desc' => __( 'Lieu de distribution (nom court)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getLieu()->getShortName();
				}
			];
			$ret['lieu_heure_debut']                 = [
				'desc' => __( 'Heure de début de distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'H:i', $adh->getLieu()->getHeure_debut() );
				}
			];
			$ret['lieu_heure_fin']                   = [
				'desc' => __( 'Heure de fin de distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'H:i', $adh->getLieu()->getHeure_fin() );
				}
			];
			$ret['lieu_adresse']                     = [
				'desc' => __( 'Adresse du lieu de distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getLieu()->getFormattedAdresse();
				}
			];
			$ret['contrat_debut']                    = [
				'desc' => __( 'Début du contrat (mois/année)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'm/Y', $adh->getContrat_instance()->getDate_debut() );
				}
			];
			$ret['contrat_fin']                      = [
				'desc' => __( 'Fin du contrat (mois/année)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'm/Y', $adh->getContrat_instance()->getDate_fin() );
				}
			];
			$ret['contrat_debut_annee']              = [
				'desc' => __( 'Année de début du contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'Y', $adh->getContrat_instance()->getDate_debut() );
				}
			];
			$ret['contrat_fin_annee']                = [
				'desc' => __( 'Année de fin du contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'Y', $adh->getContrat_instance()->getDate_fin() );
				}
			];
			$ret['nb_paiements']                     = [
				'desc' => __( 'Nombre de règlements/chèques choisi', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( ! $adh->getContrat_instance()->getManage_Cheques() ) {
						return 'en direct';
					}

					return $adh->getPaiements();
				}
			];
			$ret['nb_distributions']                 = [
				'desc' => __( 'Nombre de distributions restantes', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getRemainingDatesWithFactors();
				}
			];
			$ret['nb_dates']                         = [
				'desc' => __( 'Nombre de dates de distributions restantes', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return count( $adh->getRemainingDates() );
				}
			];
			$ret['dates_rattrapages']                = [
				'desc' => __( 'Description des dates de distribution de rattrapage', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', $adh->getFormattedRattrapages() );
				}
			];
			$ret['dates_rattrapages_list']           = [
				'desc' => __( 'Listes html des dates de distribution de rattrapage', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( '<br />', array_map( function ( $s ) {
						return '* ' . $s;
					}, $adh->getFormattedRattrapages() ) );
				}
			];
			$ret['dates_distribution_par_mois']      = [
				'desc' => __( 'Dates de distributions regroupées par mois', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ' ; ', $adh->getFormattedDatesDistribMois() );
				}
			];
			$ret['dates_distribution_par_mois_list'] = [
				'desc' => __( 'Liste html des dates de distributions regroupées par mois', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( '<br />', array_map( function ( $s ) {
						return '* ' . $s;
					}, $adh->getFormattedDatesDistribMois() ) );
				}
			];
			$ret['premiere_date']                    = [
				'desc' => __( 'Première date de distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', from( $adh->getRemainingDates() )->firstOrDefault() );
				}
			];
			$ret['derniere_date']                    = [
				'desc' => __( 'Dernière date de distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return date_i18n( 'd/m/Y', from( $adh->getRemainingDates() )->lastOrDefault() );
				}
			];
			$ret['dates_distribution']               = [
				'desc' => __( 'Liste des dates de distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_map( function ( $d ) {
						return date_i18n( 'd/m/Y', $d );
					}, $adh->getRemainingDates() ) );
				}
			];
			$ret['option_paiements']                 = [
				'desc' => __( 'Option de paiement choisie', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
//					if ( ! $adh->getContrat_instance()->getManage_Cheques() ) {
//						return wp_strip_all_tags( html_entity_decode( wp_unslash( $adh->getContrat_instance()->getPaiementsMention() ) ) );
//					}
					if ( 'esp' == $adh->getMainPaiementType() ) {
						return __( 'En espèces', 'amapress' );
					}
					if ( 'stp' == $adh->getMainPaiementType() ) {
						return __( 'Paiement en ligne', 'amapress' );
					}
					if ( 'vir' == $adh->getMainPaiementType() ) {
						return __( 'Par virement', 'amapress' );
					}
					if ( 'mon' == $adh->getMainPaiementType() ) {
						return __( 'En monnaie locale', 'amapress' );
					}
					if ( 'dlv' == $adh->getMainPaiementType() || abs( $adh->getTotalAmount() ) < 0.001 ) {
						return 'A la livraison';
					}

					return $adh->getChequeOptions();
				}
			];
			$ret['paiements_ordre'] = [
				'desc' => __( 'Ordre à indiquer sur les chèques', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return wp_unslash( $adh->getContrat_instance()->getPaiementsOrdre() );
				}
			];
			$ret['paiements_mention'] = [
				'desc' => __( 'Mention pour les paiements', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return wp_strip_all_tags( html_entity_decode( wp_unslash( $adh->getContrat_instance()->getPaiementsMention() ) ) );
				}
			];
			$ret['quantites'] = [
				'desc' => __( 'Quantité(s) choisie(s)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( $adh->getContrat_instance()->isPanierVariable() ) {
						return $adh->getPaniersVariablesDescription();
					}

					return $adh->getContrat_quantites_AsString();
				}
			];
			$ret['quantites_prix'] = [
				'desc' => __( 'Quantité(s) choisie(s) avec prix unitaire', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( $adh->getContrat_instance()->isPanierVariable() ) {
						return $adh->getPaniersVariablesDescription( true );
					}

					return $adh->getContrat_quantites_AsString( null, true );
				}
			];
			$ret['quantites_prix_unitaire'] = [
				'desc' => __( 'Prix unitaire des quantité(s) choisie(s)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return implode( ', ', array_map( function ( $q ) {
						return Amapress::formatPrice( $q->getContratQuantite()->getPrix_unitaire() );
					}, $adh->getContrat_quantites( null ) ) );
				}
			];
			$ret['total'] = [
				'desc' => __( 'Total du contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( $adh->getTotalAmount() > 0 ) {
						return Amapress::formatPrice( $adh->getTotalAmount() );
					} else {
						return '-paiement à la livraison-';
					}
				}
			];
			$ret['total_sans_don'] = [
				'desc' => __( 'Total du contrat (sans Don par distribution)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( $adh->getTotalAmount( false ) > 0 ) {
						return Amapress::formatPrice( $adh->getTotalAmount( false ) );
					} else {
						return '-paiement à la livraison-';
					}
				}
			];
			$ret['total_avec_don'] = [
				'desc' => __( 'Total du contrat (avec Don par distribution)', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					if ( $adh->getTotalAmount( true ) > 0 ) {
						return Amapress::formatPrice( $adh->getTotalAmount( true ) );
					} else {
						return '-paiement à la livraison-';
					}
				}
			];
			$ret['don_distribution'] = [
				'desc' => __( 'Montant du "Don par distribution" pour chaque distribution', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return Amapress::formatPrice( $adh->getDon_Distribution() );
				}
			];
			$ret['don_distribution_nom'] = [
				'desc' => __( 'Libellé/Nom du "Don par distribution"', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getDon_DistributionLabel();
				}
			];
			$ret['don_distribution_desc'] = [
				'desc' => __( 'Description du "Don par distribution"', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getContrat_instance()->getDon_DistributionDescription();
				}
			];
			$ret['don_total'] = [
				'desc' => __( 'Total du "Don par distribution" pour ce contrat', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return Amapress::formatPrice( $adh->getTotalDon() );
				}
			];
			$ret['produits_paiements_livraison'] = [
				'desc' => __( 'Produits payables à la livraison', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getQuantite_pay_at_delivery();
				}
			];
			$ret['id'] = [
				'desc' => __( 'ID/Réference de l\'inscription', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					return $adh->getID();
				}
			];
			$ret['adhesion_montant'] = [
				'desc' => __( 'Montant de l\'adhésion de l\'amapien à l\'AMAP', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$adh_pmt = AmapressAdhesion_paiement::getForUser( $adh->getAdherentId(), $adh->getDate_debut(), false );
					if ( empty( $adh_pmt ) ) {
						return '';
					}

					return Amapress::formatPrice( $adh_pmt->getAmount() );
				}
			];
			$ret['adhesion_debut'] = [
				'desc' => __( 'Date de début de l\'adhésion de l\'amapien à l\'AMAP', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$adh_pmt = AmapressAdhesion_paiement::getForUser( $adh->getAdherentId(), $adh->getDate_debut(), false );
					if ( empty( $adh_pmt ) || empty( $adh_pmt->getPeriod() ) ) {
						return '';
					}

					return date_i18n( 'd/m/Y', $adh_pmt->getPeriod()->getDate_debut() );
				}
			];
			$ret['adhesion_fin'] = [
				'desc' => __( 'Date de fin de l\'adhésion de l\'amapien à l\'AMAP', 'amapress' ),
				'func' => function ( AmapressAdhesion $adh ) {
					$adh_pmt = AmapressAdhesion_paiement::getForUser( $adh->getAdherentId(), $adh->getDate_debut(), false );
					if ( empty( $adh_pmt ) || empty( $adh_pmt->getPeriod() ) ) {
						return '';
					}

					return date_i18n( 'd/m/Y', $adh_pmt->getPeriod()->getDate_fin() );
				}
			];
			self::$properties = $ret;
		}

		return self::$properties;
	}

	public function getContratDocFileName() {
		$model_filename = $this->getContrat_instance()->getContratModelDocFileName();
		$ext            = strpos( $model_filename, '.docx' ) !== false ? '.docx' : '.odt';

		return trailingslashit( Amapress::getContratDir() ) . sanitize_file_name(
				__( 'inscription-', 'amapress' ) . $this->getContrat_instance()->getModelTitle() . '-' . $this->ID . '-' . $this->getAdherent()->getUser()->last_name . $ext );
	}

	public static function getPlaceholders() {
		$ret = [];

		foreach ( amapress_replace_mail_placeholders_help( '', false, false ) as $k => $v ) {
			$ret[ $k ] = $v;
		}
		foreach ( Amapress::getPlaceholdersHelpForProperties( self::getProperties() ) as $prop_name => $prop_desc ) {
			$ret[ $prop_name ] = $prop_desc;
		}
		$ret["quantite"]                         = '(Tableau quantité, contrat simple) Libellé quantité avec facteur';
		$ret["quantite_simple"]                  = '(Tableau quantité, contrat simple) Libellé quantité';
		$ret["quantite_groupe"]                  = '(Tableau quantité, contrat simple) Groupe du libellé quantité';
		$ret["quantite_sans_groupe"]             = '(Tableau quantité, contrat simple) Libellé quantité sans groupe';
		$ret["quantite_code"]                    = '(Tableau quantité, contrat simple) Code quantité';
		$ret["quantite_nb_dates"]                = '(Tableau quantité, contrat simple) Nombre de dates de distribution restantes';
		$ret["quantite_nb_distrib"]              = '(Tableau quantité, contrat simple) Nombre de distribution restantes (rattrapages inclus)';
		$ret["quantite_dates_distrib"]           = '(Tableau quantité, contrat simple) Distribution restantes avec rattrapages';
		$ret["quantite_dates"]                   = '(Tableau quantité, contrat simple) Dates de distribution restantes';
		$ret["quantite_sous_total"]              = '(Tableau quantité, contrat simple) Prix pour la quantité choisie';
		$ret["quantite_total"]                   = '(Tableau quantité) Prix pour la quuantité choisie x nombre distrib';
		$ret["quantite_nombre"]                  = '(Tableau quantité, contrat simple) Facteur quantité choisi';
		$ret["quantite_prix_unitaire"]           = '(Tableau quantité, contrat simple) Prix à l\'unité';
		$ret["quantite_description"]             = '(Tableau quantité) Description de la quantité ; pour les paniers modulables : quantités livrées à la date donnée';
		$ret["quantite_description_no_price"]    = '(Tableau quantité, paniers modulables) : quantités livrées sans les prix à la date donnée';
		$ret["quantite_description_br"]          = '(Tableau quantité, paniers modulables) : quantités livrées à la date donnée avec retour à la ligne entre chaque';
		$ret["quantite_description_br_no_price"] = '(Tableau quantité, paniers modulables) : quantités livrées sans les prix à la date donnée avec retour à la ligne entre chaque';
		$ret["quantite_unite"]                   = '(Tableau quantité, contrat simple) Unité de la quantité';
		$ret["quantite_date"]                    = '(Tableau quantité, paniers modulables) : date de livraison';

		$ret["quantite_details_date"]          = '(Tableau quantité détails) pour les paniers modulables : Date de distribution';
		$ret["quantite_details_total"]         = '(Tableau quantité détails) pour les paniers modulables : Prix pour la quuantité choisie';
		$ret["quantite_details_nombre"]        = '(Tableau quantité détails) pour les paniers modulables : Facteur quantité choisi';
		$ret["quantite_details_prix_unitaire"] = '(Tableau quantité détails) pour les paniers modulables : Prix à l\'unité';
		$ret["quantite_details_unite"]         = '(Tableau quantité détails) pour les paniers modulables : Unité de la quantité';
		$ret["quantite_details_groupe"]        = '(Tableau quantité détails) pour les paniers modulables : Groupe de la quantité';
		$ret["quantite_details_description"]   = '(Tableau quantité détails) pour les paniers modulables : Description de la quantité';

		$ret['groupe_nom']                              = '(Tableau groupe quantité) nom du groupe de quantité (entre [] dans le libellé de chaque quantité)';
		$ret['groupe_date']                             = '(Tableau groupe quantité) pour les paniers modulables : date de livraison';
		$ret["groupe_quantite_description"]             = '(Tableau groupe quantité) pour les paniers modulables : quantités livrées à la date donnée';
		$ret["groupe_quantite_description_no_price"]    = '(Tableau groupe quantité) pour les paniers modulables : quantités livrées sans les prix à la date donnée';
		$ret["groupe_quantite_description_br"]          = '(Tableau groupe quantité) pour les paniers modulables : quantités livrées à la date donnée avec retour à la ligne entre chaque';
		$ret["groupe_quantite_description_br_no_price"] = '(Tableau groupe quantité) pour les paniers modulables : quantités livrées sans les prix à la date donnée avec retour à la ligne entre chaque';
		$ret["groupe_total"]                            = '(Tableau groupe quantité) total pour le groupe';
		$ret["groupe_nombre"]                           = '(Tableau groupe quantité) total facteur quantité choisi';

		$ret['paiement_type']     = '(Tableau paiement) Type de paiement (Chèque, espèces, virement...)';
		$ret['paiement_numero']   = '(Tableau paiement) Numéro du chèque';
		$ret['paiement_emetteur'] = '(Tableau paiement) Nom de l\'adhérent émetteur';
		$ret['paiement_banque']   = '(Tableau paiement) Banque du chèque';
		$ret['paiement_montant']  = '(Tableau paiement) Montant du paiement';
		$ret['paiement_date']     = '(Tableau paiement) Date d\'encaissement du paiement';
		$ret['paiement_status']   = '(Tableau paiement) Etat du paiement';

		$ret['paiement_x_type']     = '(où x varie de 1 à 12 suivant le nombre de paiements) Type de paiement (Chèque, espèces, virement...)';
		$ret['paiement_x_numero']   = '(où x varie de 1 à 12 suivant le nombre de paiements) Numéro du chèque';
		$ret['paiement_x_emetteur'] = '(où x varie de 1 à 12 suivant le nombre de paiements) Nom de l\'adhérent émetteur';
		$ret['paiement_x_banque']   = '(où x varie de 1 à 12 suivant le nombre de paiements) Banque du chèque';
		$ret['paiement_x_montant']  = '(où x varie de 1 à 12 suivant le nombre de paiements) Montant du paiement';
		$ret['paiement_x_date']     = '(où x varie de 1 à 12 suivant le nombre de paiements) Date d\'encaissement du paiement';
		$ret['paiement_x_status']   = '(où x varie de 1 à 12 suivant le nombre de paiements) Etat du paiement';

		return $ret;
	}

	public static function getPlaceholdersHelp( $additional_helps = [], $for_contrat = false, $show_toggler = true ) {
		$ret = self::getPlaceholders();

		return Amapress::getPlaceholdersHelpTable( 'contrat-placeholders', $ret,
			'de l\'inscription', $additional_helps, ! $for_contrat,
			$for_contrat ? '${' : '%%', $for_contrat ? '}' : '%%',
			$show_toggler );
	}

	public function generateContratDoc( $editable, $check_only = false ) {
		$out_filename   = $this->getContratDocFileName();
		$model_filename = $this->getContrat_instance()->getContratModelDocFileName();
		if ( ! $check_only && empty( $model_filename ) ) {
			return '';
		}

		$placeholders = [];
		foreach ( amapress_replace_mail_placeholders_help( '', false, false ) as $k => $v ) {
			$prop_name                  = $k;
			$placeholders[ $prop_name ] = amapress_replace_mail_placeholders( "%%$prop_name%%", null );
		}
		foreach ( self::getProperties() as $prop_name => $prop_config ) {
			$placeholders[ $prop_name ] = call_user_func( $prop_config['func'], $this );
		}

		$details_lines_count = 0;
		$lines_count         = 0;
		$group_lines_count   = 0;
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$i  = 1;
			$ii = 1;
			foreach ( $this->getPaniersVariables() as $date => $panier ) {
				$date_price  = 0;
				$date_quants = $this->getVariables_Contrat_quantites( $date );
				foreach ( $date_quants as $date_quant ) {
					/** @var AmapressContrat_quantite $quant */
					$quant      = $date_quant['contrat_quantite'];
					$date_price += ( $date_quant['quantite'] * $quant->getPrix_unitaire() );
				}
				$placeholders["quantite_date#$i"] = date_i18n( 'd/m/Y', $date );
				if ( abs( $date_price ) < 0.001 ) {
					$placeholders["quantite_total#$i"] = 'A la livraison';
				} else {
					$placeholders["quantite_total#$i"] = Amapress::formatPrice( $date_price );
				}
				$placeholders["quantite_description#$i"]             = $this->getContrat_quantites_AsString( $date, true );
				$placeholders["quantite_description_no_price#$i"]    = $this->getContrat_quantites_AsString( $date );
				$placeholders["quantite_description_br#$i"]          = '* ' . $this->getContrat_quantites_AsString( $date, true, '<br />* ' );
				$placeholders["quantite_description_br_no_price#$i"] = '* ' . $this->getContrat_quantites_AsString( $date, false, '<br />* ' );

				$i           += 1;
				$lines_count += 1;

				foreach ( $date_quants as $date_quant ) {
					/** @var AmapressContrat_quantite $quant */
					$quant  = $date_quant['contrat_quantite'];
					$factor = $date_quant['quantite'];

					$placeholders["quantite_details_date#$ii"] = date_i18n( 'd/m/Y', $date );
					if ( abs( $quant->getPrix_unitaire() ) < 0.001 ) {
						$placeholders["quantite_details_total#$ii"] = 'A la livraison';
					} else {
						$placeholders["quantite_details_total#$ii"] = Amapress::formatPrice( $quant->getPrix_unitaire() * $factor );
					}
					$placeholders["quantite_details_nombre#$ii"]        = $quant->formatValue( $factor );
					$placeholders["quantite_details_prix_unitaire#$ii"] = $quant->getPrix_unitaireDisplay();
					$placeholders["quantite_details_unite#$ii"]         = $quant->getPriceUnitDisplay();
					$placeholders["quantite_details_description#$ii"]   = $quant->getTitle();
					$placeholders["quantite_details_groupe#$ii"]        = $quant->getGroupName();

					$ii                  += 1;
					$details_lines_count += 1;
				}
			}

			$ii = 1;
			foreach ( $this->getPaniersVariables() as $date => $panier ) {
				$date_quants = $this->getContrat_quantites( $date );
				$grp_names   = [];
				$grp_totals  = [];
				$grp_sums    = [];
				foreach ( $date_quants as $quant ) {
					$grp_name = $quant->getGroupName();
					if ( ! in_array( $grp_name, $grp_names ) ) {
						$grp_names[] = $grp_name;
					}
					if ( ! isset( $grp_sums[ $grp_name ] ) ) {
						$grp_sums[ $grp_name ] = 0;
					}
					if ( ! isset( $grp_totals[ $grp_name ] ) ) {
						$grp_totals[ $grp_name ] = 0;
					}

					$grp_sums[ $grp_name ]   += $quant->getFactor() / (float) $quant->getGroupMultiple();
					$grp_totals[ $grp_name ] += $quant->getPrice();
				}
				foreach ( $grp_names as $grp_name ) {
					$placeholders["groupe_nom#$ii"]                              = $grp_name;
					$placeholders["groupe_date#$ii"]                             = date_i18n( 'd/m/Y', $date );
					$placeholders["groupe_quantite_description#$ii"]             = $this->getContrat_quantites_AsString( $date, true, ', ', false, $grp_name );
					$placeholders["groupe_quantite_description_no_price#$ii"]    = $this->getContrat_quantites_AsString( $date, false, ', ', false, $grp_name );
					$placeholders["groupe_quantite_description_br#$ii"]          = '* ' . $this->getContrat_quantites_AsString( $date, true, '<br/>* ', false, $grp_name );
					$placeholders["groupe_quantite_description_br_no_price#$ii"] = '* ' . $this->getContrat_quantites_AsString( $date, false, '<br/>* ', false, $grp_name );
					if ( abs( $grp_totals[ $grp_name ] ) < 0.001 ) {
						$placeholders["groupe_total#$ii"] = 'A la livraison';
					} else {
						$placeholders["groupe_total#$ii"] = Amapress::formatPrice( $grp_totals[ $grp_name ] );
					}
					$placeholders["groupe_nombre#$ii"] = round( $grp_sums[ $grp_name ], 2 );

					$ii                += 1;
					$group_lines_count += 1;
				}
			}
		} else {
			$quants = $this->getContrat_quantites( null );
			$i      = 1;
			foreach ( $quants as $quant ) {
				$remaining_dates                           = $this->getRemainingDates( $quant->getId() );
				$remaining_dates_count                     = count( $remaining_dates );
				$remaining_distrib                         = $this->getRemainingDatesWithFactors( $quant->getId(), true );
				$remaining_distrib_sum                     = $this->getRemainingDatesWithFactors( $quant->getId() );
				$placeholders["quantite#$i"]               = $quant->getTitleWithFactor();
				$placeholders["quantite_simple#$i"]        = $quant->getTitleWithoutFactor();
				$placeholders["quantite_groupe#$i"]        = $quant->getGroupName();
				$placeholders["quantite_sans_groupe#$i"]   = $quant->getTitleWithoutGroup();
				$placeholders["quantite_code#$i"]          = $quant->getCode();
				$placeholders["quantite_nb_dates#$i"]      = $remaining_dates_count;
				$placeholders["quantite_nb_distrib#$i"]    = $remaining_distrib_sum;
				$placeholders["quantite_dates_distrib#$i"] = implode( ', ', array_map( function ( $d, $f ) {
					if ( abs( $f - 1.0 ) < 0.001 ) {
						return date_i18n( 'd/m/Y', $d );
					} else if ( abs( $f - 2.0 ) < 0.001 ) {
						return date_i18n( 'd/m/Y', $d ) . '(double)';
					} else {
						return date_i18n( 'd/m/Y', $d ) . '(' . $f . ')';
					}
				}, array_keys( $remaining_distrib ), array_values( $remaining_dates ) ) );
				$placeholders["quantite_dates#$i"]         = implode( ', ', array_map( function ( $d ) {
					return date_i18n( 'd/m/Y', $d );
				}, $remaining_dates ) );
				if ( abs( $quant->getPrice() ) < 0.001 ) {
					$placeholders["quantite_sous_total#$i"] = 'A la livraison';
					$placeholders["quantite_total#$i"]      = 'A la livraison';
				} else {
					$placeholders["quantite_sous_total#$i"] = Amapress::formatPrice( $quant->getPrice() );
					$placeholders["quantite_total#$i"]      = Amapress::formatPrice( $quant->getPrice() * $remaining_distrib_sum );
				}
				$placeholders["quantite_nombre#$i"]        = $quant->getFactor();
				$placeholders["quantite_prix_unitaire#$i"] = $quant->getContratQuantite()->getPrix_unitaireDisplay();
				$placeholders["quantite_description#$i"]   = $quant->getContratQuantite()->getDescription();
				$placeholders["quantite_unite#$i"]         = $quant->getContratQuantite()->getPriceUnitDisplay();
				$i                                         += 1;
				$lines_count                               += 1;
			}

			$ii         = 1;
			$grp_names  = [];
			$grp_totals = [];
			$grp_sums   = [];
			foreach ( $quants as $quant ) {
				$grp_name = $quant->getGroupName();
				if ( ! in_array( $grp_name, $grp_names ) ) {
					$grp_names[] = $grp_name;
				}
				if ( ! isset( $grp_sums[ $grp_name ] ) ) {
					$grp_sums[ $grp_name ] = 0;
				}
				if ( ! isset( $grp_totals[ $grp_name ] ) ) {
					$grp_totals[ $grp_name ] = 0;
				}

				$grp_sums[ $grp_name ]   += $quant->getFactor();
				$grp_totals[ $grp_name ] += $quant->getPrice();
			}
			foreach ( $grp_names as $grp_name ) {
				$placeholders["groupe_nom#$ii"]                              = $grp_name;
				$placeholders["groupe_quantite_description#$ii"]             = $this->getContrat_quantites_AsString( null, true, ', ', false, $grp_name );
				$placeholders["groupe_quantite_description_no_price#$ii"]    = $this->getContrat_quantites_AsString( null, false, ', ', false, $grp_name );
				$placeholders["groupe_quantite_description_br#$ii"]          = '* ' . $this->getContrat_quantites_AsString( null, true, '<br/>* ', false, $grp_name );
				$placeholders["groupe_quantite_description_br_no_price#$ii"] = '* ' . $this->getContrat_quantites_AsString( null, false, '<br/>* ', false, $grp_name );
				if ( abs( $grp_totals[ $grp_name ] ) < 0.001 ) {
					$placeholders["groupe_total#$ii"] = 'A la livraison';
				} else {
					$placeholders["groupe_total#$ii"] = Amapress::formatPrice( $grp_totals[ $grp_name ] );
				}
				$placeholders["groupe_nombre#$ii"] = $grp_sums[ $grp_name ];

				$ii                += 1;
				$group_lines_count += 1;
			}
		}


		if ( 'dlv' == $this->getMainPaiementType() || abs( $this->getTotalAmount() ) < 0.001 ) {
			$paiements = $this->getContrat_instance()->isPanierVariable() ? array_keys( $this->getPaniersVariables() ) : $this->getRemainingDates();
			$i         = 1;
			foreach ( $paiements as $paiement_date ) {
				$placeholders["paiement_type#$i"]     = '';
				$placeholders["paiement_numero#$i"]   = '';
				$placeholders["paiement_emetteur#$i"] = '';
				$placeholders["paiement_banque#$i"]   = '';
				$placeholders["paiement_montant#$i"]  = '';
				$placeholders["paiement_date#$i"]     = date_i18n( 'd/m/Y', $paiement_date );
				$placeholders["paiement_status#$i"]   = '';

				$placeholders["paiement_{$i}_type"]     = $placeholders["paiement_type#$i"];
				$placeholders["paiement_{$i}_numero"]   = $placeholders["paiement_numero#$i"];
				$placeholders["paiement_{$i}_emetteur"] = $placeholders["paiement_emetteur#$i"];
				$placeholders["paiement_{$i}_banque"]   = $placeholders["paiement_banque#$i"];
				$placeholders["paiement_{$i}_montant"]  = $placeholders["paiement_montant#$i"];
				$placeholders["paiement_{$i}_date"]     = $placeholders["paiement_date#$i"];
				$placeholders["paiement_{$i}_status"]   = $placeholders["paiement_status#$i"];

				$i += 1;
			}
		} else {
			$paiements = $this->getAllPaiements();
			$i         = 1;
			foreach ( $paiements as $paiement ) {
				$placeholders["paiement_type#$i"]     = $paiement->getTypeFormatted();
				$placeholders["paiement_numero#$i"]   = $paiement->getNumero();
				$placeholders["paiement_emetteur#$i"] = $paiement->getEmetteur();
				$placeholders["paiement_banque#$i"]   = $paiement->getBanque();
				$placeholders["paiement_montant#$i"]  = Amapress::formatPrice( $paiement->getAmount() );
				$placeholders["paiement_date#$i"]     = date_i18n( 'd/m/Y', $paiement->getDate() );
				$placeholders["paiement_status#$i"]   = $paiement->getStatusDisplay();

				$placeholders["paiement_{$i}_type"]     = $placeholders["paiement_type#$i"];
				$placeholders["paiement_{$i}_numero"]   = $placeholders["paiement_numero#$i"];
				$placeholders["paiement_{$i}_emetteur"] = $placeholders["paiement_emetteur#$i"];
				$placeholders["paiement_{$i}_banque"]   = $placeholders["paiement_banque#$i"];
				$placeholders["paiement_{$i}_montant"]  = $placeholders["paiement_montant#$i"];
				$placeholders["paiement_{$i}_date"]     = $placeholders["paiement_date#$i"];
				$placeholders["paiement_{$i}_status"]   = $placeholders["paiement_status#$i"];

				$i += 1;
			}
		}

		if ( $check_only ) {
			return $placeholders;
		}

		\PhpOffice\PhpWord\Settings::setTempDir( Amapress::getTempDir() );
		$templateProcessor = new Phptemplate_withnewline( $model_filename );

		try {
			$templateProcessor->cloneRow( 'quantite_date', $lines_count );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'quantite', $lines_count );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				try {
					$templateProcessor->cloneRow( 'quantite_simple', $lines_count );
				} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					try {
						$templateProcessor->cloneRow( 'quantite_description', $lines_count );
					} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					}
				}
			}
		}

		try {
			$templateProcessor->cloneRow( 'quantite_details_date', $details_lines_count );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'quantite_details_nombre', $details_lines_count );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				try {
					$templateProcessor->cloneRow( 'quantite_description', $details_lines_count );
				} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				}
			}
		}

		$lines_count = count( $paiements );
		try {
			$templateProcessor->cloneRow( 'paiement_montant', $lines_count );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'paiement_numero', $lines_count );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
				try {
					$templateProcessor->cloneRow( 'paiement_emetteur', $lines_count );
				} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					try {
						$templateProcessor->cloneRow( 'paiement_banque', $lines_count );
					} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
					}
				}
			}
		}

		try {
			$templateProcessor->cloneRow( 'groupe_date', $group_lines_count );
		} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			try {
				$templateProcessor->cloneRow( 'groupe_nom', $group_lines_count );
			} catch ( \PhpOffice\PhpWord\Exception\Exception $ex ) {
			}
		}

		foreach ( $placeholders as $k => $v ) {
			$templateProcessor->setValue( $k, $v );
		}

		$templateProcessor->saveAs( $out_filename );

		if ( ! $editable ) {
			$out_filename = Amapress::convertToPDF( $out_filename );
		}

		return $out_filename;
	}

	/** @return int[][] */
	public static function getAllRelatedAdhesions() {
		$key = 'amapress_getAllRelatedAdhesions';
		$ret = wp_cache_get( $key );
		if ( false === $ret ) {
			$ret = [];
			foreach (
				get_posts(
					array(
						'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'key'     => 'amapress_adhesion_related',
								'compare' => 'EXISTS',
							),
						),
					)
				) as $adh_post
			) {
				$adh = AmapressAdhesion::getBy( $adh_post );
				if ( ! $adh->getRelatedAdhesion() ) {
					continue;
				}
				if ( empty( $ret[ $adh->ID ] ) ) {
					$ret[ $adh->ID ] = [];
				}
				$rel_id = $adh->getRelatedAdhesionId();
				if ( empty( $ret[ $rel_id ] ) ) {
					$ret[ $rel_id ] = [];
				}
				if ( ! in_array( $rel_id, $ret[ $adh->ID ] ) ) {
					$ret[ $adh->ID ][] = $rel_id;
				}
				if ( ! in_array( $adh->ID, $ret[ $rel_id ] ) ) {
					$ret[ $rel_id ][] = $adh->ID;
				}
			}
			wp_cache_set( $key, $ret );
		}

		return $ret;
	}

	public function getDate_debut() {
		return $this->getCustomAsDate( 'amapress_adhesion_date_debut' );
	}

	public function hasDate_fin() {
		$fin = $this->getCustom( 'amapress_adhesion_date_fin' );

		return ! empty( $fin );
	}

	public function hasBeforeEndDate_fin() {
		if ( ! $this->getContrat_instance() ) {
			return false;
		}

		return Amapress::start_of_week( $this->getDate_fin() ) < Amapress::start_of_week( $this->getContrat_instance()->getDate_fin() );
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

	public function getFormattedDatesDistribMois( $quantite_id = null ) {
		$dates         = $this->getRemainingDates( $quantite_id );
		$grouped_dates = from( $dates )->groupBy( function ( $d ) {
			return date_i18n( 'F Y', $d );
		} );

		$grouped_dates_array = [];
		foreach ( $grouped_dates as $k => $v ) {
			$grouped_dates_array[] = $k . ' : ' . _n( 'le ', 'les ', count( $v ) ) . implode( ', ', array_map(
					function ( $d ) {
						return date_i18n( 'd', $d );
					}, $v
				) );
		}

		return $grouped_dates_array;
	}

	public function getFormattedRattrapages( $quantite_id = null ) {
		$dates = $this->getRemainingDates( $quantite_id );

		return $this->getContrat_instance()->getFormattedRattrapages( $dates );
	}

	/** @return AmapressContrat_instance */
	public function getContrat_instance() {
		return $this->getCustomAsEntity( 'amapress_adhesion_contrat_instance', 'AmapressContrat_instance' );
	}

	/** @return int */
	public function getContrat_instanceId() {
		return $this->getCustomAsInt( 'amapress_adhesion_contrat_instance' );
	}

	public function getModelId() {
		$contrat = $this->getContrat_instance();
		if ( $contrat ) {
			return $contrat->getModelId();
		}

		return 0;
	}

	/** @return int */
	public function isNotRenewable() {
		return $this->hasBeforeEndDate_fin();
	}

	public function markClosed() {
		if ( $this->hasDate_fin() ) {
			return;
		}
		$date = $this->getContrat_instance()->getDate_fin();
		$this->setCustom( 'amapress_adhesion_date_fin', $date );
		amapress_compute_post_slug_and_title( $this->post );
	}

	/**
	 * @return AmapressAdhesionQuantite[]
	 */
	public function getContrat_quantites( $dist_date ) {
		$dist_date = Amapress::start_of_day( $dist_date );

		if ( $this->getContrat_instance() && $this->getContrat_instance()->isPanierVariable() ) {
			$quants = $this->getVariables_Contrat_quantites( $dist_date );
			$ret    = [];
			foreach ( $quants as $quant ) {
				$ret[ $quant['contrat_quantite']->ID ] = new AmapressAdhesionQuantite( $quant['contrat_quantite'], $quant['quantite'] );
			}

			return $ret;
		}

		$factors = $this->getCustomAsFloatArray( 'amapress_adhesion_contrat_quantite_factors' );
		/** @var AmapressContrat_quantite[] $quants */
		$quants = $this->getCustomAsEntityArray( 'amapress_adhesion_contrat_quantite', 'AmapressContrat_quantite' );

		$ret = [];
		foreach ( $quants as $quant ) {
			$date_factor = 1;
			if ( $this->getContrat_instanceId() ) {
				$date_factor = $this->getContrat_instance()->getDateFactor( $dist_date, $quant->ID );
			}
			$factor = 1;
			if ( isset( $factors[ $quant->ID ] ) && $factors[ $quant->ID ] > 0 ) {
				$factor = $factors[ $quant->ID ];
			}
			if ( $this->hasBeforeEndDate_fin() && $dist_date > $this->getDate_fin() ) {
				$date_factor = 0;
			}

			if ( abs( $factor * $date_factor ) < 0.001 ) {
				continue;
			}
			$ret[ $quant->ID ] = new AmapressAdhesionQuantite( $quant, $factor * $date_factor );
		}

		return $ret;
	}

	/**
	 * @return float
	 */
	public function getContrat_quantite_factor( $quantite_id, $dist_date = null ) {
		if ( ! $this->getContrat_instance() ) {
			return 0;
		}
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$paniers = $this->getPaniersVariables();
			if ( isset( $paniers[ $dist_date ] ) && isset( $paniers[ $dist_date ][ $quantite_id ] ) ) {
				return $paniers[ $dist_date ][ $quantite_id ];
			}

			return 0;
		}

		$factors = $this->getCustomAsFloatArray( 'amapress_adhesion_contrat_quantite_factors' );
		/** @var AmapressContrat_quantite[] $quants */
		$quants = $this->getCustomAsEntityArray( 'amapress_adhesion_contrat_quantite', 'AmapressContrat_quantite' );

		$ret = [];
		foreach ( $quants as $quant ) {
			if ( $quant->ID != $quantite_id ) {
				continue;
			}

			$date_factor = 1;
			if ( $dist_date ) {
				$date_factor = $this->getContrat_instance()->getDateFactor( $dist_date, $quant->ID );
			}
			$factor = 1;
			if ( isset( $factors[ $quant->ID ] ) && $factors[ $quant->ID ] > 0 ) {
				$factor = $factors[ $quant->ID ];
			}
			if ( $this->hasBeforeEndDate_fin() && $dist_date > $this->getDate_fin() ) {
				$date_factor = 0;
			}

			if ( abs( $factor * $date_factor ) < 0.001 ) {
				continue;
			}

			return $factor;
		}

		return 0;
	}

	/** @return array */
	public function getPaniersVariables() {
		$paniers = $this->getCustomAsArray( 'amapress_adhesion_panier_variables' );
		foreach ( $paniers as $panier ) {
			if ( is_string( $panier ) && is_serialized( $panier ) ) {
				return maybe_unserialize( $panier );
			}
		}

		return $paniers;
	}

	public function getVariables_Contrat_quantites( $date ) {
		if ( ! $this->getContrat_instance()->isPanierVariable() ) {
			return array();
		}
		$date = Amapress::start_of_day( $date );

		$quants      = array();
		$quant_by_id = AmapressContrats::get_contrat_quantites( $this->getContrat_instanceId(), $date );
		$quant_by_id = array_combine( array_map( function ( $c ) {
			return $c->ID;
		}, $quant_by_id ), $quant_by_id );
		$paniers     = $this->getPaniersVariables();
		if ( isset( $paniers[ $date ] ) ) {
			$panier = $paniers[ $date ];
			if ( empty( $panier ) ) {
				$panier = [];
			}
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

	public function getPaniersVariablesDescription( $show_price_unit = false ) {
		$dates_desc = [];
		foreach ( $this->getPaniersVariables() as $k => $v ) {
			$date         = intval( $k );
			$quant_labels = $this->getContrat_quantites_AsString( $date, $show_price_unit );
			if ( ! empty( $quant_labels ) ) {
				$dates_desc[] = '* ' . date_i18n( 'd/m/Y', $date ) . ' : ' . $quant_labels;
			}
		}

		return implode( '<br/>', $dates_desc );
	}

	public function getContrat_quantites_AsString(
		$date = null,
		$show_price_unit = false,
		$separator = ', ',
		$only_pay_deliv = false,
		$filter_grp_name = null
	) {
		if ( empty( $this->getContrat_instance() ) ) {
			return '';
		}
		if ( null !== $filter_grp_name ) {
			$filter_grp_name = trim( $filter_grp_name, '[]' );
		}
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$grouped_quants     = [];
			$grouped_quants_sum = [];
			foreach ( $this->getVariables_Contrat_quantites( $date ) as $q ) {
				/** @var AmapressContrat_quantite $contrat_quantite */
				$contrat_quantite = $q['contrat_quantite'];
				if ( $only_pay_deliv && $contrat_quantite->getPrix_unitaire() > 0 ) {
					continue;
				}
				if ( null !== $filter_grp_name && $contrat_quantite->getGroupName() != $filter_grp_name ) {
					continue;
				}
				$quantite  = $q['quantite'];
				$title     = $contrat_quantite->getTitle();
				$grp_name  = '';
				$has_group = preg_match( '/^\s*(\[[^\]]+\])(.+)/', $contrat_quantite->getTitle(), $matches );
				if ( $has_group ) {
					if ( isset( $matches[1] ) ) {
						$grp_name = $matches[1];
						$title    = $matches[2];
					}
				}
				if ( ! isset( $grouped_quants[ $grp_name ] ) ) {
					$grouped_quants[ $grp_name ] = [];
				}
				if ( ! isset( $grouped_quants_sum[ $grp_name ] ) ) {
					$grouped_quants_sum[ $grp_name ] = 0;
				}
				$grouped_quants[ $grp_name ][]   =
					'<strong>' . esc_html( $contrat_quantite->formatValue( $quantite ) ) . '</strong>' .
					esc_html( ' x ' . $title . ( $show_price_unit ? ' à ' . $contrat_quantite->getPrix_unitaireDisplay() . '€' : '' ) );
				$grouped_quants_sum[ $grp_name ] += $quantite;
			}
			$quant_labels = array();
			foreach ( $grouped_quants as $grp_name => $labels ) {
				if ( null !== $filter_grp_name ) {
					$quant_labels[] = implode( $separator, $labels );
				} else {
					$quant_labels[] = sprintf( __( '<strong>%s</strong>(<em>%s</em>): %s', 'amapress' ),
						esc_html( $grp_name ),
						$grouped_quants_sum[ $grp_name ],
						implode( ' + ', $labels ) );
				}
			}

			return implode( $separator, $quant_labels );
		} else {
			$quants       = ( $only_pay_deliv ?
				array_filter( $this->getContrat_quantites( $date ), function ( $vv ) {
					/** @var AmapressAdhesionQuantite $vv */
					return abs( $vv->getPrice() ) < 0.001;
				} ) :
				$this->getContrat_quantites( $date ) );
			$quant_labels = array_map(
				function ( $vv ) use ( $show_price_unit ) {
					/** @var AmapressAdhesionQuantite $vv */
					return $vv->getTitleWithFactor( true ) . ( $show_price_unit ? ' à ' . Amapress::formatPrice( $vv->getPrice() ) . '€' : '' );
				}
				,
				null !== $filter_grp_name ?
					array_filter( $quants, function ( $vv ) use ( $filter_grp_name ) {
						/** @var AmapressAdhesionQuantite $vv */
						return $filter_grp_name === $vv->getGroupName();
					} ) : $quants
			);

			return implode( $separator, $quant_labels );
		}
	}

	public function getQuantite_pay_at_delivery( $date = null, $separator = ', ' ) {
		return $this->getContrat_quantites_AsString( $date, false, $separator, true );
	}

	public function getContrat_quantites_Codes_AsString( $date = null ) {
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			return __( 'Var.', 'amapress' );
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
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$ids     = [];
			$paniers = $this->getPaniersVariables();
			foreach ( $paniers as $panier ) {
				$ids = array_merge( $ids, array_keys( $panier ) );
			}

			return array_unique( $ids );
		}

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
					$contrat_quant = isset( $quant_by_id[ $quant_id ] ) ? $quant_by_id[ $quant_id ] : null;
					if ( empty( $contrat_quant ) ) {
						continue;
					}
					$sum += $contrat_quant->getPrix_unitaire() * $quant;
				}
			}
		} else {
			foreach ( $this->getContrat_quantites( $date ) as $c ) {
				if ( empty( $c ) ) {
					continue;
				}

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

	public function getRemainingDates( $quantite_id = null ) {
		if ( empty( $this->getContrat_instance() ) ) {
			return [];
		}

		$start_date = Amapress::start_of_day( $this->getDate_debut() );

		$dates = [];
		if ( $this->getContrat_instance()->isPanierVariable() ) {
			$paniers_vars = $this->getPaniersVariables();
			$dates        = array_filter( $this->getContrat_instance()->getRemainingDates( $start_date, null ),
				function ( $d ) use ( $paniers_vars ) {
					return isset( $paniers_vars[ $d ] );
				}
			);
		} else {
			$quantite_ids = $quantite_id ? [ $quantite_id ] : $this->getContrat_quantites_IDs();
			foreach ( $quantite_ids as $qid ) {
				$dates = array_merge( $dates, $this->getContrat_instance()->getRemainingDates( $start_date, $qid ) );
			}
		}
		$dates = array_unique( $dates );
		sort( $dates, SORT_ASC );

		if ( $this->hasDate_fin() && $this->hasPaiementDateFin() ) {
			$dates = array_filter( $dates, function ( $d ) {
				return Amapress::start_of_day( $d ) < Amapress::end_of_day( $this->getDate_fin() );
			} );
		}

		return $dates;
	}

	public function getRemainingDatesWithFactors( $quantite_id = null, $return_array = false ) {
		$start_date = Amapress::start_of_day( $this->getDate_debut() );

		if ( $quantite_id ) {
			if ( $return_array ) {
				$ret = $this->getContrat_instance()->getRemainingDatesWithFactors( $start_date, $quantite_id, true );
				if ( $this->hasDate_fin() && $this->hasPaiementDateFin() ) {
					foreach (
						$this->getContrat_instance()->getRemainingDatesWithFactors( Amapress::add_days( $this->getDate_fin(), 1 ), $quantite_id, true )
						as $k => $v
					) {
						unset( $ret[ $k ] );
					}
				}

				return $ret;
			} else {
				$val = $this->getContrat_instance()->getRemainingDatesWithFactors( $start_date, $quantite_id );
				if ( $this->hasDate_fin() && $this->hasPaiementDateFin() ) {
					$val -= $this->getContrat_instance()->getRemainingDatesWithFactors( Amapress::add_days( $this->getDate_fin(), 1 ), $quantite_id );
				}
			}
		} else if ( $this->getContrat_instance()->isPanierVariable() ) {
			return $this->getContrat_instance()->getRemainingDatesWithFactors( $start_date, null, $return_array );
		} else {
			if ( $return_array ) {
				foreach ( $this->getContrat_quantites_IDs() as $qid ) {
					return $this->getRemainingDatesWithFactors( $qid, true );
				}
			} else {
				$val = 0;
				foreach ( $this->getContrat_quantites_IDs() as $qid ) {
					$factor = $this->getRemainingDatesWithFactors( $qid );
					if ( $factor > $val ) {
						$val = $factor;
					}
				}
			}
		}

		return $val;
	}

	/** @return AmapressAdhesion */
	public function getRelatedAdhesion() {
		return $this->getCustomAsEntity( 'amapress_adhesion_related', 'AmapressAdhesion' );
	}

	/** @return int */
	public function getRelatedAdhesionId() {
		return $this->getCustomAsInt( 'amapress_adhesion_related' );
	}

	/** @return int */
	public function getPaiements() {
		return $this->getCustom( 'amapress_adhesion_paiements', 0 );
	}

	/** @return string */
	public function getMainPaiementType() {
		return $this->getCustom( 'amapress_adhesion_pmt_type', 'chq' );
	}

	public function hasPaiementDateFin() {
		return $this->getCustom( 'amapress_adhesion_pmt_fin', 0 );
	}

	public function getChequeOptions() {
		$amount = $this->getTotalAmount();
		if ( $this->getContrat_instance()->getPayByMonth() ) {
			if ( 1 == $this->getPaiements() ) {
				return sprintf( __( "1 %s de %0.2f €", 'amapress' ),
					'prl' == $this->getMainPaiementType() ? __( 'prélèvement', 'amapress' ) : __( 'chèque', 'amapress' ),
					$amount );
			} else {
				$by_months = $this->getTotalAmountByMonth();

				return sprintf( __( '%d %s (%s)', 'amapress' ),
					$this->getPaiements(),
					'prl' == $this->getMainPaiementType() ? __( 'prélèvement(s)', 'amapress' ) : __( 'chq.', 'amapress' ),
					implode( ' ; ', array_map( function ( $month, $month_amount ) {
						return sprintf( __( '%s: 1 %s de %0.2f €', 'amapress' ),
							$month,
							'prl' == $this->getMainPaiementType() ? __( 'prélèvement', 'amapress' ) : __( 'chèque', 'amapress' ),
							$month_amount );
					}, array_keys( $by_months ), array_values( $by_months ) ) ) );
			}
		} else {
			$option = $this->getContrat_instance()->getChequeOptionsForTotal(
				$this->getPaiements(), $amount,
				'prl' == $this->getMainPaiementType() ? __( 'prélèvement', 'amapress' ) : __( 'chèque', 'amapress' ) );

			return sprintf( __( '%d %s (%s)', 'amapress' ),
				$this->getPaiements(),
				'prl' == $this->getMainPaiementType() ? __( 'prélèvement(s)', 'amapress' ) : __( 'chq.', 'amapress' ),
				$option['desc'] );
		}
	}

	public function getPossibleChequeOptions() {
		$amount = $this->getTotalAmount();
		if ( $this->getContrat_instance()->getPayByMonth() ) {
			$cheques_options = [];
			if ( ! $this->getContrat_instance()->getPayByMonthOnly() && in_array( 1, $this->getContrat_instance()->getPossiblePaiements() ) ) {
				$cheques_options[] = sprintf( __( '1 %s de %0.2f €', 'amapress' ),
					'prl' == $this->getMainPaiementType() ? __( 'prélèvement', 'amapress' ) : __( 'chèque', 'amapress' ),
					$amount );
			}
			$by_months = $this->getTotalAmountByMonth();
			if ( in_array( count( $by_months ), $this->getContrat_instance()->getPossiblePaiements() ) ) {
				$cheques_options[] = implode( ' ; ', array_map( function ( $month, $month_amount ) {
					return sprintf( __( '%s: 1 %s de %0.2f €', 'amapress' ),
						'prl' == $this->getMainPaiementType() ? __( 'prélèvement', 'amapress' ) : __( 'chèque', 'amapress' ),
						$month,
						$month_amount );
				}, array_keys( $by_months ), array_values( $by_months ) ) );
			}
		} else {
			$cheques_options = array_map( function ( $v ) use ( $amount ) {
				$option = $this->getContrat_instance()->getChequeOptionsForTotal(
					$v, $amount,
					'prl' == $this->getMainPaiementType() ? __( 'prélèvement', 'amapress' ) : __( 'chèque', 'amapress' ) );

				return sprintf( __( '%d (%s)', 'amapress' ), $v, $option['desc'] );
			}, $this->getContrat_instance()->getPossiblePaiements() );
		}

		return $cheques_options;
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

	/** @return AmapressUser */
	public function getAdherent2() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent2', 'AmapressUser' );
	}

	public function getAdherent2Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent2' );
	}

	public function setAdherent2( $value ) {
		update_post_meta( $this->post->ID, 'amapress_adhesion_adherent2', $value );
	}

	/** @return AmapressUser */
	public function getAdherent3() {
		return $this->getCustomAsEntity( 'amapress_adhesion_adherent3', 'AmapressUser' );
	}

	public function getAdherent3Id() {
		return $this->getCustomAsInt( 'amapress_adhesion_adherent3' );
	}

	/** @return AmapressUser */
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

	public function getProducteurMessage() {
		return wp_unslash( $this->getCustom( 'amapress_adhesion_prod_msg' ) );
	}

	public function getDon_Distribution() {
		return $this->getCustomAsFloat( 'amapress_adhesion_don_dist', 0 );
	}

	public function getTotalDon() {
		return count( $this->getRemainingDates() ) * $this->getDon_Distribution();
	}

	public function getStatusDisplay() {
		switch ( $this->getCustom( 'amapress_adhesion_status' ) ) {

			case self::TO_CONFIRM:
				return 'A confirmer';
			case self::CONFIRMED:
				return __( 'Confirmée', 'amapress' );
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
	public function getTotalAmount( $include_don = null ) {
		if ( ! $this->getContrat_instanceId() ) {
			return 0;
		}
		$dates = $this->getRemainingDates();
//		if ( $this->getContrat_instance()->isPanierVariable() ) {
		$sum = 0;
		foreach ( $dates as $date ) {
			$sum += $this->getContrat_quantites_Price( $date );
		}
		if ( null === $include_don ) {
			$include_don = ! $this->getContrat_instance()->getDon_Distribution_Apart();
		}
		if ( $include_don ) {
			$sum += count( $dates ) * $this->getDon_Distribution();
		}

		return $sum;
//		} else {
//			return count( $dates ) * $this->getContrat_quantites_Price();
//		}
	}


	/** @return array */
	public function getTotalAmountByCustom() {
		if ( ! $this->getContrat_instanceId() ) {
			return [];
		}

		return $this->getContrat_instance()->getTotalAmountByCustom( $this->getPaiements(), $this->getTotalAmount() );
	}

	/** @return array */
	public function getTotalAmountByMonth() {
		if ( ! $this->getContrat_instanceId() ) {
			return [];
		}
		$dates           = $this->getRemainingDates();
		$by_month_totals = [];
		foreach ( $dates as $date ) {
			$month = date_i18n( 'M', $date );
			if ( empty( $by_month_totals[ $month ] ) ) {
				$by_month_totals[ $month ] = 0;
			}
			$by_month_totals[ $month ] += $this->getContrat_quantites_Price( $date );
		}

		return $by_month_totals;
	}

	private static $paiement_cache = null;

	/** @return  AmapressAdhesion[] */
	public static function getAllActiveByUserId() {
		if ( null === self::$paiement_cache ) {
			global $wpdb;
			$coadhs = array_group_by(
				amapress_get_results_cached(
					"SELECT DISTINCT $wpdb->usermeta.meta_value, $wpdb->usermeta.user_id
FROM $wpdb->usermeta
WHERE  $wpdb->usermeta.meta_key IN ('amapress_user_co-adherent-1', 'amapress_user_co-adherent-2', 'amapress_user_co-adherent-3', 'amapress_user_co-foyer-1', 'amapress_user_co-foyer-2', 'amapress_user_co-foyer-3')" ),
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
	public static function getUserActiveAdhesionsWithAllowPartialCheck(
		$user_id = null,
		$contrat_instance_id = null,
		$date = null,
		$ignore_renouv_delta = false,
		$allow_not_logged = false,
		$include_futur = true
	) {
		if ( Amapress::hasPartialCoAdhesion() ) {
			return AmapressAdhesion::getUserActiveDirectAdhesions( $user_id, $contrat_instance_id, $date, $ignore_renouv_delta, $allow_not_logged, $include_futur );
		} else {
			return AmapressAdhesion::getUserActiveAdhesions( $user_id, $contrat_instance_id, $date, $ignore_renouv_delta, $allow_not_logged, $include_futur );
		}
	}

	/**
	 * @return AmapressAdhesion[]
	 */
	public static function getUserActiveDirectAdhesions(
		$user_id = null,
		$contrat_instance_id = null,
		$date = null,
		$ignore_renouv_delta = false,
		$allow_not_logged = false,
		$include_futur = true
	) {
		$all_adhs = self::getUserActiveAdhesions( $user_id, $contrat_instance_id, $date, $ignore_renouv_delta, $allow_not_logged, $include_futur );

		return array_filter( $all_adhs, function ( $adh ) use ( $user_id ) {
			return $adh->getAdherentId() == $user_id
			       || $adh->getAdherent2Id() == $user_id
			       || $adh->getAdherent3Id() == $user_id
			       || $adh->getAdherent4Id() == $user_id;
		} );
	}

	/**
	 * @return AmapressAdhesion[]
	 */
	public static function getUserActiveAdhesions(
		$user_id = null,
		$contrat_instance_id = null,
		$date = null,
		$ignore_renouv_delta = false,
		$allow_not_logged = false,
		$include_futur = true
	) {
		$ids = self::getUserActiveAdhesionIds( $user_id, $contrat_instance_id, $date, $ignore_renouv_delta, $allow_not_logged, $include_futur );
		if ( empty( $ids ) ) {
			return [];
		}

		$key = 'AmapressAdhesion::getUserActiveAdhesionsPosts_' . implode( '_', $ids );
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			update_meta_cache( 'post', $ids );

			$res = array_map( function ( $p ) {
				return AmapressAdhesion::getBy( $p );
			}, get_posts(
				[
					'posts_per_page' => - 1,
					'post_type'      => AmapressAdhesion::INTERNAL_POST_TYPE,
					'post__in'       => $ids,
				]
			) );

			wp_cache_set( $key, $res );
		}

		return $res;
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

		$key = "AmapressAdhesion::getUserActiveAdhesions_{$date}_{$user_id}_{$abo_key}_{$include_futur}";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$user_ids = AmapressContrats::get_related_users( $user_id, $allow_not_logged, $date );
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
				'orderby'        => 'none',
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
	public function getAllPaiements( $no_cache = false ) {
		$key = "amapress_get_contrat_paiements_{$this->ID}";
		$res = wp_cache_get( $key );
		if ( $no_cache || false === $res ) {
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
				'orderby'        => 'none',
			);
			$res   = array_map( function ( $p ) {
				return new AmapressAmapien_paiement( $p );
			}, get_posts( $query ) );
			usort( $res, function ( $a, $b ) {
				/** @var AmapressAmapien_paiement $a */
				/** @var AmapressAmapien_paiement $b */
				$da = $a->getDate();
				$db = $b->getDate();
				if ( $da == $db ) {
					return 0;
				} else {
					return $da < $db ? - 1 : 1;
				}
			} );
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

	public function canSelfEdit( $days_before = 3 ) {
		return $this->getContrat_instance()->canSelfEdit()
		       && self::TO_CONFIRM == $this->getStatus()
		       && Amapress::end_of_day( $this->getContrat_instance()->getDate_cloture() ) > Amapress::start_of_day( amapress_time() )
		       && Amapress::add_days( $this->getDate_debut(), - abs( $days_before ) ) > Amapress::start_of_day( amapress_time() );
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

	public function sendConfirmationMail() {
		$inscription = $this;
		$amapien     = $inscription->getAdherent();

		$mail_subject = Amapress::getOption( 'online_subscription_confirm-mail-subject' );
		$mail_content = Amapress::getOption( 'online_subscription_confirm-mail-content' );

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $amapien, $inscription );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, $amapien, $inscription );

		$attachments = [];
		$doc_file    = $inscription->generateContratDoc( false );
		if ( ! empty( $doc_file ) ) {
			$attachments[] = $doc_file;
			$mail_content  = preg_replace( '/\[sans_contrat\].+?\[\/sans_contrat\]/', '', $mail_content );
			$mail_content  = preg_replace( '/\[\/?avec_contrat\]/', '', $mail_content );
		} else {
			$mail_content = preg_replace( '/\[avec_contrat\].+?\[\/avec_contrat\]/', '', $mail_content );
			$mail_content = preg_replace( '/\[\/?sans_contrat\]/', '', $mail_content );
		}

		$refs_mails = $inscription->getContrat_instance()->getAllReferentsEmails( $this->getLieuId() );
		amapress_wp_mail( $amapien->getAllEmails(), $mail_subject, $mail_content, [
			'Reply-To: ' . implode( ',', $refs_mails )
		], $attachments );
	}

	public function sendReferentsNotificationMail( $send_contrat = false, $notify_email = null, $for_type = 'new' ) {
		$inscription = $this;
		$amapien     = $inscription->getAdherent();

		$mail_subject = Amapress::getOption( 'new' == $for_type ?
			'online_subscription_referents-mail-subject' :
			( 'cancel' == $for_type ?
				'online_subscription_referents_cancel-mail-subject' :
				'online_subscription_referents_modif-mail-subject' ) );
		$mail_content = Amapress::getOption( 'new' == $for_type ?
			'online_subscription_referents-mail-content' :
			( 'cancel' == $for_type ?
				'online_subscription_referents_cancel-mail-content' :
				'online_subscription_referents_modif-mail-content' ) );

		$mail_subject = amapress_replace_mail_placeholders( $mail_subject, $amapien, $inscription );
		$mail_content = amapress_replace_mail_placeholders( $mail_content, $amapien, $inscription );

		$referents = [];
		foreach ( $inscription->getContrat_instance()->getReferentsIds( $this->getLieuId() ) as $ref_id ) {
			$user_obj  = AmapressUser::getBy( $ref_id );
			$referents = array_merge( $referents, $user_obj->getAllEmails() );
		}

		$attachments = [];
		if ( $send_contrat ) {
			$doc_file = $inscription->generateContratDoc( false );
			if ( ! empty( $doc_file ) ) {
				$attachments[] = $doc_file;
			}
		}
		$headers = [ 'Reply-To: ' . implode( ',', $inscription->getAdherent()->getAllEmails() ) ];

		amapress_wp_mail(
			$referents,
			$mail_subject,
			$mail_content,
			$headers, $attachments,
			$notify_email
		);
	}

	public function preparePaiements(
		$pre_filled_paiements = [],
		$overwrite = true,
		$default_status = 'not_received',
		$round_date = true
	) {
		$contrat_instance        = $this->getContrat_instance();
		$contrat_paiements_dates = $contrat_instance->getPaiements_Liste_dates();
		$nb_paiements            = $this->getPaiements();
		$contrat_paiements       = $this->getAllPaiements();
		$all_paiements           = AmapressContrats::get_all_paiements( $contrat_instance->ID );

		$deleted  = false;
		$pmt_type = $this->getMainPaiementType();
		for ( $i = ( $pmt_type != 'chq' ? 0 : $nb_paiements ); $i < count( $contrat_paiements ); $i ++ ) {
			wp_delete_post( $contrat_paiements[ $i ]->ID, true );
			$deleted = true;
		}
		if ( $deleted ) {
			$contrat_paiements = $this->getAllPaiements( true );
		}

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

				return sprintf( __( '%05d-%8x', 'amapress' ), $unit, $k );
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
		//TODO: handle pay by month + custom rep dates
		$def_date = 0;
		foreach ( $contrat_paiements as $paiement ) {
			if ( ! $paiement ) {
				$new_paiement_date[] = ( isset( $dates_by_cheque_count[ $def_date ] ) ? $dates_by_cheque_count[ $def_date ++ ] : 0 );
			}
		}
		if ( $contrat_instance->getPayByMonth() ) {
			$new_paiement_date = [];
			$pay_dates         = $contrat_instance->getPaiements_Liste_dates();
			sort( $pay_dates );
			foreach ( $this->getTotalAmountByMonth() as $month => $month_amount ) {
				$found = false;
				foreach ( $pay_dates as $pay_date ) {
					if ( date_i18n( 'M', $pay_date ) == $month ) {
						$new_paiement_date[] = $pay_date;
						$found               = true;
						break;
					}
				}
				if ( ! $found ) {
					$new_paiement_date[] = $pay_dates[0];
				}
			}
		}
		sort( $new_paiement_date );

		$nb_paiements = count( $contrat_paiements );
		$amounts      = [];
		if ( $contrat_instance->getPayByMonth() ) {
			if ( 1 == $this->getPaiements() ) {
				$amounts[] = $this->getTotalAmount();
			} else {
				foreach ( $this->getTotalAmountByMonth() as $month_amount ) {
					$amounts[] = $month_amount;
				}
			}
		} elseif ( $contrat_instance->hasCustomMultiplePaiements() ) {
			foreach ( $this->getTotalAmountByCustom() as $cust_amount ) {
				$amounts[] = $cust_amount;
			}
		} else {
			$paiements_options = $contrat_instance->getChequeOptionsForTotal(
				$nb_paiements, $this->getTotalAmount(),
				'prl' == $this->getMainPaiementType() ? 'prélèvement' : 'chèque' );
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
		}
		$pre_filled_paiement_index = 1;
		$def_date                  = 0;
		$def_id                    = - 1;
		foreach ( $contrat_paiements as $paiement ) {
			$id     = $paiement ? $paiement->ID : $def_id --;
			$numero = $paiement ? $paiement->getNumero() : '';
			$banque = $paiement ? $paiement->getBanque() : '';

			$adherent = $paiement ? $paiement->getEmetteur() : $this->getAdherent()->getDisplayName();
			if ( empty( $adherent ) ) {
				$adherent = $this->getAdherent()->getDisplayName();
			}
			$amount      = array_shift( $amounts );
			$status      = $paiement ? $paiement->getStatus() : $default_status;
			$paiement_dt = $paiement && ! $contrat_instance->getPayByMonth() ?
				$paiement->getDate() :
				( isset( $new_paiement_date[ $def_date ] ) ? $new_paiement_date[ $def_date ++ ] : 0 );

			if ( isset( $pre_filled_paiements[ $pre_filled_paiement_index ] ) ) {
				if ( isset( $pre_filled_paiements[ $pre_filled_paiement_index ]['num'] ) ) {
					$numero = $pre_filled_paiements[ $pre_filled_paiement_index ]['num'];
				}
				if ( isset( $pre_filled_paiements[ $pre_filled_paiement_index ]['banque'] ) ) {
					$banque = $pre_filled_paiements[ $pre_filled_paiement_index ]['banque'];
				}
				if ( isset( $pre_filled_paiements[ $pre_filled_paiement_index ]['emetteur'] ) ) {
					$adherent = $pre_filled_paiements[ $pre_filled_paiement_index ]['emetteur'];
				}
				if ( isset( $pre_filled_paiements[ $pre_filled_paiement_index ]['date'] ) ) {
					$paiement_dt = intval( $pre_filled_paiements[ $pre_filled_paiement_index ]['date'] );
				}
			}
			if ( $round_date ) {
				$paiement_dt = Amapress::start_of_day( $paiement_dt );
			}

			$meta = array(
				'amapress_contrat_paiement_adhesion'         => $this->ID,
				'amapress_contrat_paiement_contrat_instance' => $contrat_instance->ID,
				'amapress_contrat_paiement_date'             => $paiement_dt,
				'amapress_contrat_paiement_status'           => $status,
				'amapress_contrat_paiement_amount'           => $amount,
				'amapress_contrat_paiement_numero'           => $numero,
				'amapress_contrat_paiement_emetteur'         => $adherent,
				'amapress_contrat_paiement_banque'           => $banque,
			);
			if ( ( $overwrite || null == $paiement ) && 'esp' == $this->getMainPaiementType() ) {
				$meta['amapress_contrat_paiement_type'] = 'esp';
			}
			if ( ( $overwrite || null == $paiement ) && 'stp' == $this->getMainPaiementType() ) {
				$meta['amapress_contrat_paiement_type'] = 'stp';
			}
			if ( ( $overwrite || null == $paiement ) && 'vir' == $this->getMainPaiementType() ) {
				$meta['amapress_contrat_paiement_type'] = 'vir';
			}
			if ( ( $overwrite || null == $paiement ) && 'mon' == $this->getMainPaiementType() ) {
				$meta['amapress_contrat_paiement_type'] = 'mon';
			}
			if ( ( $overwrite || null == $paiement ) && 'prl' == $this->getMainPaiementType() ) {
				$meta['amapress_contrat_paiement_type'] = 'prl';
			}
			$my_post = array(
				'post_type'    => AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				'post_content' => '',
				'post_status'  => 'publish',
				'meta_input'   => $meta,
			);
			if ( $id < 0 ) {
				wp_insert_post( $my_post );
			} else {
				$my_post['ID'] = $id;
				wp_update_post( $my_post, true );
				if ( ( $overwrite || null == $paiement ) && 'chq' == $this->getMainPaiementType() ) {
					delete_post_meta( $id, 'amapress_contrat_paiement_type' );
				}
			}
			$pre_filled_paiement_index += 1;
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
			set_transient( 'amps_adh_to_confirm', $cnt, HOUR_IN_SECONDS );
		}

		return $cnt[ $current_user_id ];
	}
}