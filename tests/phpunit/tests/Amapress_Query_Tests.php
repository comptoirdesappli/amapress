<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 08/10/2017
 * Time: 22:37
 */

class Amapress_Query_Tests extends Amapress_UnitTestCase {
	function getAdminQueries() {
		$this->create_amap();

		$temp = [
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressContrat::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressProducteur::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressProduit::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressContrat_quantite::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_legumes_lieu_2, 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE, [] ],

			[ $this->referent_fruits, 'post_type=' . AmapressContrat::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressProducteur::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressProduit::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressContrat_quantite::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE, [] ],

			[ $this->responsable_amap, 'post_type=' . AmapressContrat::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressProducteur::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressProduit::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressContrat_quantite::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE, [] ],
			[ $this->responsable_amap, 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE, [] ],
		];

		$ret = [];
		foreach ( $temp as $t ) {
			$ret["User $t[0] / $t[1]"] = $t;
		}

		return $ret;
	}

	function getQueries() {
		$this->create_amap();

		$temp = [
			[ 0, 'post_type=' . AmapressContrat::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressProducteur::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressProduit::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressContrat_quantite::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE, [] ],
			[ 0, 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE, [] ],

			[ $this->referent_fruits, 'post_type=' . AmapressContrat::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressProducteur::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressProduit::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressContrat_quantite::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE, [] ],
			[ $this->referent_fruits, 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE, [] ],

			[ $this->amapien_2_contrats, 'post_type=' . AmapressContrat::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressProducteur::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressProduit::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressContrat_quantite::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE, [] ],
			[ $this->amapien_2_contrats, 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE, [] ],

			[ $this->coordinateur_amap, 'post_type=' . AmapressContrat::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressProducteur::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressProduit::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressContrat_quantite::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE, [] ],
			[ $this->coordinateur_amap, 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE, [] ],

			[
				$this->responsable_amap,
				'amapress_producteur=' . $this->producteur_legumes_prod . '&post_type=' . AmapressProduit::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_producteur=' . $this->producteur_legumes_prod . '&post_type=' . AmapressContrat::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_producteur=' . $this->producteur_legumes_prod . '&post_type=' . AmapressVisite::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_producteur=' . $this->producteur_legumes_prod . '&post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE,
				[]
			],

//amapress_recette_produits, amapress_recette_tag, amapress_recette_tag_not_in, amapress_produit_tag, amapress_produit_tag_not_in, amapress_produit_recette

			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_legumes . 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_legumes . 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_legumes . 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_legumes . 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_legumes . 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE,
				[]
			],

			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_fruits . 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_fruits . 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_fruits . 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_fruits . 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat_inst=' . $this->contrat_instance_fruits . 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE,
				[]
			],

			[
				$this->responsable_amap,
				'amapress_contrat_qt=' . $this->contrat_quantite_legumes_2 . 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE,
				[]
			],

			[
				$this->responsable_amap,
				'amapress_contrat=' . $this->contrat_champignons . 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat=' . $this->contrat_champignons . 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat=' . $this->contrat_champignons . 'post_type=' . AmapressPanier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat=' . $this->contrat_champignons . 'post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_contrat=' . $this->contrat_champignons . 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE,
				[]
			],

			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressVisite::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressDistribution::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressAdhesion_paiement::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_user=' . $this->amapien_4_contrats . 'post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				[]
			],

			[
				$this->responsable_amap,
				'amapress_lieu=' . $this->lieu_1 . '&post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_lieu=' . $this->lieu_1 . '&post_type=' . AmapressAmap_event::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_lieu=' . $this->lieu_1 . '&post_type=' . AmapressAssemblee_generale::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_lieu=' . $this->lieu_1 . '&post_type=' . AmapressIntermittence_panier::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_lieu=' . $this->lieu_1 . '&post_type=' . AmapressDistribution::INTERNAL_POST_TYPE,
				[]
			],
			[
				$this->responsable_amap,
				'amapress_lieu=' . $this->lieu_1 . '&post_type=' . AmapressAmapien_paiement::INTERNAL_POST_TYPE,
				[]
			],
//			[ $this->responsable_amap, 'amapress_lieu=' . $this->lieu_1 . '&post_type=' . Amapres::INTERNAL_POST_TYPE, [] ],

			[
				$this->responsable_amap,
				'amapress_with_coadherents&post_type=' . AmapressAdhesion::INTERNAL_POST_TYPE,
				[]
			],

			//amapress_date

			//search
		];
		$ret  = [];
		foreach ( $temp as $t ) {
			$ret["User $t[0] / $t[1]"] = $t;
		}

		return $ret;
	}

	function getUserQueries() {
		$this->create_amap();

		$temp = [
			[ $this->responsable_amap, 'amapress_info=address_unk', [] ],
			[ $this->responsable_amap, 'amapress_info=phone_unk', [] ],
			[ $this->responsable_amap, 'amapress_contrat=intermittent', [] ],
			[ $this->responsable_amap, 'amapress_contrat=coadherent', [] ],
			[ $this->responsable_amap, 'amapress_contrat=no', [] ],
			[ $this->responsable_amap, 'amapress_contrat=none', [] ],
			[ $this->responsable_amap, 'amapress_contrat=active', [] ],
			[ $this->responsable_amap, 'amapress_contrat=lastyear', [] ],
			//contrat
			[ $this->responsable_amap, 'amapress_contrat=' . $this->contrat_champignons, [] ],
			//contrat, multi
			[
				$this->responsable_amap,
				'amapress_contrat=' . $this->contrat_fruits . ',' . $this->contrat_champignons,
				[]
			],
			//contrat instance
			[ $this->responsable_amap, 'amapress_contrat=' . $this->contrat_instance_champignons, [] ],
			//contrat instance, multi
			[
				$this->responsable_amap,
				'amapress_contrat=' . $this->contrat_instance_fruits . ',' . $this->contrat_instance_champignons,
				[]
			],
			[ $this->responsable_amap, 'amapress_role=access_admin', [] ],
			[ $this->responsable_amap, 'amapress_role=never_logged', [] ],
			[ $this->responsable_amap, 'amapress_role=referent_lieu', [] ],
			[ $this->responsable_amap, 'amapress_role=referent_producteur', [] ],
			[ $this->responsable_amap, 'amapress_role=resp_distrib', [] ],
			[ $this->responsable_amap, 'amapress_role=resp_distrib_next', [] ],
			[ $this->responsable_amap, 'amapress_role=resp_distrib_month', [] ],
			[ $this->responsable_amap, 'amapress_role=amap_role_any', [] ],
			//amap_role_
			//lieu
			[ $this->responsable_amap, 'amapress_lieu=', [] ],
			//lieu, multi
			[ $this->responsable_amap, 'amapress_lieu=', [] ],
			[ $this->responsable_amap, 'amapress_adhesion=nok', [] ],
			[ $this->responsable_amap, 's=Université', [] ],
			[ $this->responsable_amap, 's=Lizateur', [] ],
			[ $this->responsable_amap, 's=01 02 03 04 05', [] ],
			[
				$this->responsable_amap,
				's=' . get_user_by( 'ID', $this->coadhrent_amapien_2_et_4_contrats )->user_email,
				[]
			],
		];

		$ret = [];
		foreach ( $temp as $t ) {
			$ret["User $t[0] / $t[1]"] = $t;
		}

		return $ret;
	}

	/**
	 * @param $user_id
	 * @param $query
	 * @param $expected_posts
	 *
	 * @dataProvider getQueries
	 */
	function testQuery( $user_id, $query, $expected_posts ) {
		$this->loginUser( $user_id );

		$post_ids = $this->getIDs(
			get_posts( wp_parse_args(
					$query,
					[
						'posts_per_page' => - 1,
					] )
			)
		);
		$this->assertPostIdsOrCount( $expected_posts, $post_ids );
	}

	/**
	 * @param $user_id
	 * @param $query
	 * @param $expected_posts
	 *
	 * @dataProvider getAdminQueries
	 */
	function testAdminQuery( $user_id, $query, $expected_posts ) {
		$this->set_is_admin_true();
		$this->loginUser( $user_id );

		$post_ids = $this->getIDs(
			get_posts( wp_parse_args(
					$query,
					[
						'posts_per_page' => - 1,
					] )
			)
		);
		$this->assertPostIdsOrCount( $expected_posts, $post_ids );
	}

	/**
	 * @param $user_id
	 * @param $query
	 * @param $expected_users
	 *
	 * @dataProvider getUserQueries
	 */
	function testUsers( $user_id, $query, $expected_users ) {
		$this->set_is_admin_true();
		$this->loginUser( $user_id );

		$user_ids = $this->getIDs(
			get_users( wp_parse_args(
					$query,
					[
					] )
			)
		);
		$this->assertUserIdsOrCount( $expected_users, $user_ids );

	}


}