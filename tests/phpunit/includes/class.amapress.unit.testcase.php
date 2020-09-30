<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 07/10/2017
 * Time: 09:27
 */

class Amapress_UnitTestCase extends WP_UnitTestCase {
	protected $users = [ '0' => 0 ];
	protected $posts = [ '0' => 0 ];
	protected $adhesion_legumes_amapien_2_contrats;
	protected $contrat_quantite_legumes_2;
	protected $adhesion_champignons_amapien_2_contrats;
	protected $adhesion_legumes_amapien_4_contrats;
	protected $contrat_quantite_legumes_1;
	protected $contrat_instance_legumes;
	protected $contrat_legumes;
	protected $producteur_legumes_prod;
	protected $adhesion_champignons_amapien_4_contrats;
	protected $contrat_quantite_champignons_1;
	protected $contrat_instance_champignons;
	protected $contrat_champignons;
	protected $producteur_champignons_prod;
	protected $adhesion_fruits_amapien_4_contrats;
	protected $contrat_quantite_fruits_1;
	protected $contrat_instance_fruits;
	protected $contrat_fruits;
	protected $producteur_fruits_prod;
	protected $adhesion_poulet_amapien_4_contrats;
	protected $contrat_quantite_poulet_1;
	protected $contrat_quantite_oeuf_1;
	protected $contrat_instance_oeufs;
	protected $contrat_oeufs;
	protected $contrat_instance_poulet;
	protected $contrat_poulet;
	protected $producteur_poulet_prod;
	protected $lieu_2;
	protected $lieu_1;
	protected $producteur_legumes;
	protected $producteur_chamoignons;
	protected $producteur_fruits;
	protected $referent_champignons_lieu_2;
	protected $referent_fruits_lieu_2;
	protected $amapien_4_contrats;
	protected $producteur_poulet;
	protected $referent_legumes_lieu_2;
	protected $referent_poulet;
	protected $coadhrent_amapien_2_et_4_contrats;
	protected $amapien_2_contrats;
	protected $referent_champignons_lieu_1;
	protected $referent_fruits;
	protected $intermittent;
	protected $coordinateur_amap;
	protected $tresorier;
	protected $responsable_amap;
	protected $administrateur;
	protected $create_distrib_and_paniers = true;

	function setUp() {
		parent::setUp();

		$this->create_amap( $this->create_distrib_and_paniers );
	}


	protected function create_amap( $create_distrib_and_paniers = true ) {
//		$this->start_transaction();

		echo 'Preparing Amap';
		//Users
		$this->administrateur   = $this->users['2'] = self::factory()->user->create(
			[ 'role' => 'administrator' ]
		);
		$this->responsable_amap = $this->users['31'] = self::factory()->user->create(
			[ 'role' => 'responsable_amap' ]
		);
		$this->tresorier        = $this->users['143'] = self::factory()->user->create(
			[ 'role' => 'tresorier' ]
		);

		$this->coordinateur_amap = $this->users['86'] = self::factory()->user->create(
			[ 'role' => 'coordinateur_amap' ]
		);

		$this->intermittent = $this->users['141'] = self::factory()->user->create(
			[ 'role' => 'amapien' ]
		);
		update_user_meta( $this->users['141'], 'amapress_user_intermittent', '1' );
		update_user_meta( $this->users['141'], 'amapress_user_intermittent_date', '1502213530' );
		$this->referent_fruits = $this->referent_champignons_lieu_1 = $this->users['9'] = self::factory()->user->create(
			[ 'role' => 'referent' ]
		);
		update_user_meta( $this->users['9'], 'amapress_user_moyen', 'mail' );
		update_user_meta( $this->users['9'], 'amapress_user_adresse', '126 Rue de l\'Université, 75355 Paris' );
		update_user_meta( $this->users['9'], 'amapress_user_long', '2.330' );
		update_user_meta( $this->users['9'], 'amapress_user_lat', '48.833' );
		update_user_meta( $this->users['9'], 'amapress_user_location_type', 'ROOFTOP' );
		$this->amapien_2_contrats = $this->users['54'] = self::factory()->user->create(
			[ 'role' => 'amapien' ]
		);
		update_user_meta( $this->users['54'], 'amapress_user_adresse', '292 Rue Saint-Martin, 75003 Paris' );
		update_user_meta( $this->users['54'], 'amapress_user_long', '2.316' );
		update_user_meta( $this->users['54'], 'amapress_user_lat', '48.833' );
		update_user_meta( $this->users['54'], 'amapress_user_location_type', 'ROOFTOP' );
		update_user_meta( $this->users['54'], 'amapress_user_intermittent', '1' );
		update_user_meta( $this->users['54'], 'amapress_user_intermittent_date', '1502213530' );
		update_user_meta( $this->users['54'], 'amapress_user_co-adherent-1', $this->users['31'] );
		$this->coadhrent_amapien_2_et_4_contrats = $this->users['36'] = self::factory()->user->create(
			[ 'role' => 'amapien' ]
		);
		$this->referent_poulet                   = $this->referent_legumes_lieu_2 = $this->users['6'] = self::factory()->user->create(
			[ 'role' => 'referent' ]
		);
		update_user_meta( $this->users['6'], 'amapress_user_moyen', 'mail' );
		update_user_meta( $this->users['6'], 'amapress_user_adresse', '55 Rue du Faubourg Saint-Honoré, 75008 Paris' );
		update_user_meta( $this->users['6'], 'amapress_user_telephone', '01 02 03 04 05' );
		update_user_meta( $this->users['6'], 'amapress_user_co-adherents', 'Yuti Lizateur' );
		update_user_meta( $this->users['6'], 'amapress_user_long', '2.314' );
		update_user_meta( $this->users['6'], 'amapress_user_lat', '48.827' );
		update_user_meta( $this->users['6'], 'amapress_user_location_type', 'ROOFTOP' );
		$this->producteur_poulet = $this->users['188'] = self::factory()->user->create(
			[ 'role' => 'producteur' ]
		);
		update_user_meta( $this->users['188'], 'amapress_user_adresse', '57 Rue de Varenne' );
		update_user_meta( $this->users['188'], 'amapress_user_code_postal', '75007' );
		update_user_meta( $this->users['188'], 'amapress_user_ville', 'Paris' );
		update_user_meta( $this->users['188'], 'amapress_user_telephone', '01 02 03 04 05' );
		update_user_meta( $this->users['188'], 'amapress_user_moyen', 'mail' );
		update_user_meta( $this->users['188'], 'amapress_user_long', '2.491' );
		update_user_meta( $this->users['188'], 'amapress_user_lat', '48.400' );
		update_user_meta( $this->users['188'], 'amapress_user_location_type', 'RANGE_INTERPOLATED' );
		$this->amapien_4_contrats = $this->users['26'] = self::factory()->user->create(
			[ 'role' => 'administrator' ]
		);
		update_user_meta( $this->users['26'], 'amapress_user_adresse', '15 Rue de Vaugirard, 75006 Paris' );
		update_user_meta( $this->users['26'], 'amapress_user_moyen', 'mail' );
		update_user_meta( $this->users['26'], 'amapress_user_long', '2.309' );
		update_user_meta( $this->users['26'], 'amapress_user_lat', '48.838' );
		update_user_meta( $this->users['26'], 'amapress_user_location_type', 'ROOFTOP' );
		update_user_meta( $this->users['26'], 'amapress_user_intermittent', 1 );
		update_user_meta( $this->users['26'], 'amapress_user_intermittent_date', '1502213530' );
		$this->referent_fruits_lieu_2 = $this->referent_champignons_lieu_2 = $this->users['7'] = self::factory()->user->create(
			[ 'role' => 'referent' ]
		);
		update_user_meta( $this->users['7'], 'amapress_user_adresse', '126 Rue de l\'Université, 75355 Paris' );
		update_user_meta( $this->users['7'], 'amapress_user_long', '2.311' );
		update_user_meta( $this->users['7'], 'amapress_user_lat', '48.826' );
		update_user_meta( $this->users['7'], 'amapress_user_location_type', 'ROOFTOP' );
		$this->producteur_fruits = $this->users['8'] = self::factory()->user->create(
			[ 'role' => 'producteur' ]
		);
		update_user_meta( $this->users['8'], 'amapress_user_code_postal', '95450' );
		update_user_meta( $this->users['8'], 'amapress_user_ville', 'ABLEIGES' );
		update_user_meta( $this->users['8'], 'amapress_user_adresse', 'D28' );
		update_user_meta( $this->users['8'], 'amapress_user_moyen', 'mail' );
		update_user_meta( $this->users['8'], 'amapress_user_long', '1.975' );
		update_user_meta( $this->users['8'], 'amapress_user_lat', '49.079' );
		update_user_meta( $this->users['8'], 'amapress_user_location_type', 'GEOMETRIC_CENTER' );
		$this->producteur_chamoignons = $this->users['10'] = self::factory()->user->create(
			[ 'role' => 'producteur' ]
		);
		update_user_meta( $this->users['10'], 'amapress_user_code_postal', '95630' );
		update_user_meta( $this->users['10'], 'amapress_user_ville', 'MERIEL' );
		update_user_meta( $this->users['10'], 'amapress_user_adresse', '82 Grande Rue' );
		update_user_meta( $this->users['10'], 'amapress_user_moyen', 'mail' );
		update_user_meta( $this->users['10'], 'amapress_user_long', '2.204' );
		update_user_meta( $this->users['10'], 'amapress_user_lat', '49.077' );
		update_user_meta( $this->users['10'], 'amapress_user_location_type', 'ROOFTOP' );
		$this->producteur_legumes = $this->users['5'] = self::factory()->user->create(
			[ 'role' => 'producteur' ]
		);
		update_user_meta( $this->users['5'], 'amapress_user_code_postal', '77660' );
		update_user_meta( $this->users['5'], 'amapress_user_ville', 'Changis-sur-Marne' );
		update_user_meta( $this->users['5'], 'amapress_user_adresse', 'Rue Marcel Neyrat' );
		update_user_meta( $this->users['5'], 'amapress_user_moyen', 'mail' );
		update_user_meta( $this->users['5'], 'amapress_user_long', '3.025' );
		update_user_meta( $this->users['5'], 'amapress_user_lat', '48.962' );
		update_user_meta( $this->users['5'], 'amapress_user_location_type', 'GEOMETRIC_CENTER' );

		$this->lieu_1                                  = $this->posts['33'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-15 12:23:13',
				'post_date_gmt'     => '2016-09-15 10:23:13',
				'post_content'      => 'C\'est lors des distributions',
				'post_title'        => 'Pernety - Château Ouvrier',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-14 10:42:07',
				'post_modified_gmt' => '2017-09-14 08:42:07',
				'post_type'         => 'amps_lieu',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_lieu_distribution_contact_externe' => 'xxxx',
						'amapress_lieu_distribution_nb_responsables' => '3',
						'amapress_lieu_distribution_heure_debut'     => '66600',
						'amapress_lieu_distribution_heure_fin'       => '72000',
						'amapress_lieu_distribution_adresse'         => '9 Place Marcel Paul',
						'amapress_lieu_distribution_code_postal'     => '75014',
						'amapress_lieu_distribution_ville'           => 'PARIS',
						'amapress_lieu_distribution_long'            => '2.3192114',
						'amapress_lieu_distribution_lat'             => '48.8338269',
						'amapress_lieu_distribution_location_type'   => 'ROOFTOP',
						'amapress_lieu_distribution_shortname'       => 'Pernety',
					),
			) );
		$this->lieu_2                                  = $this->posts['34'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-15 12:25:25',
				'post_date_gmt'     => '2016-09-15 10:25:25',
				'post_content'      => 'C\'est lors des distributions...',
				'post_title'        => 'Porte de Vanves - Centre Social Maurice Noguès',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2016-11-18 16:47:53',
				'post_modified_gmt' => '2016-11-18 15:47:53',
				'post_type'         => 'amps_lieu',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_lieu_distribution_contact_externe' => 'mmmmm',
						'amapress_lieu_distribution_nb_responsables' => '2',
						'amapress_lieu_distribution_heure_debut'     => '66600',
						'amapress_lieu_distribution_heure_fin'       => '72000',
						'amapress_lieu_distribution_adresse'         => '5 Avenue de la Porte de Vanves',
						'amapress_lieu_distribution_code_postal'     => '75014',
						'amapress_lieu_distribution_ville'           => 'PARIS',
						'amapress_lieu_distribution_long'            => '2.3047233',
						'amapress_lieu_distribution_lat'             => '48.8261277',
						'amapress_lieu_distribution_location_type'   => 'ROOFTOP',
						'amapress_lieu_distribution_shortname'       => 'Porte de Vanves',
					),
			) );
		$this->producteur_poulet_prod                  = $this->posts['5138'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-04 08:59:10',
				'post_date_gmt'     => '2017-09-04 06:59:10',
				'post_title'        => 'Poulet',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-04 09:13:51',
				'post_modified_gmt' => '2017-09-04 07:13:51',
				'post_type'         => 'amps_producteur',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_producteur_resume'                             => 'Les poulets au grand air !',
						'amapress_producteur_presentation'                       => '
Récemment installé à Milly-la-Forêt',
						'amapress_producteur_historique'                         => 'La ferme ',
						'amapress_producteur_nom_exploitation'                   => 'Poulet dorémi',
						'amapress_producteur_adresse_exploitation_long'          => '2.491',
						'amapress_producteur_adresse_exploitation_lat'           => '48.400',
						'amapress_producteur_adresse_exploitation_location_type' => 'RANGE_INTERPOLATED',
						'amapress_producteur_adresse_exploitation'               => '57 Rue de Varenne, 75007 Paris',
						'amapress_producteur_acces'                              => '57 Rue de Varenne, 75007 Paris',
						'amapress_producteur_user'                               => $this->users['188'],
						'amapress_producteur_referent'                           => $this->users['6'],
					),
			) );
		$this->contrat_poulet                          = $this->posts['5139'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-04 09:28:19',
				'post_date_gmt'     => '2017-09-04 07:28:19',
				'post_content'      => 'Objet : Le contrat de préachat',
				'post_title'        => 'Poulet',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-04 09:53:34',
				'post_modified_gmt' => '2017-09-04 07:53:34',
				'post_type'         => 'amps_contrat',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_producteur' => $this->posts['5138'],
					),
			) );
		$this->contrat_instance_poulet                 = $this->posts['6551'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-27 17:26:45',
				'post_date_gmt'     => '2017-09-27 15:26:45',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-28 11:00:10',
				'post_modified_gmt' => '2017-09-28 09:00:10',
				'post_type'         => 'amps_contrat_inst',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_instance_model'                 => $this->posts['5139'],
						'amapress_contrat_instance_type'                  => 'panier',
						'amapress_contrat_instance_date_debut'            => 1504224000,
						'amapress_contrat_instance_date_fin'              => 1535587200,
						'amapress_contrat_instance_date_ouverture'        => 1504224000,
						'amapress_contrat_instance_date_cloture'          => 1532649600,
						'amapress_contrat_instance_lieux'                 => array(
							$this->posts['33'],
							$this->posts['34']
						),
						'amapress_contrat_instance_max_adherents'         => '100',
						'amapress_contrat_instance_liste_dates'           => '28/09/2017, 26/10/2017, 30/11/2017, 21/12/2017, 25/01/2018, 22/02/2018, 29/03/2018, 26/04/2018, 31/05/2018, 28/06/2018, 26/07/2018, 30/08/2018',
						'amapress_contrat_instance_paiements'             => 'a:3:{i:0;s:1:""1"";i:1;s:1:""2"";i:2;s:1:""3"";}',
						'amapress_contrat_instance_liste_dates_paiements' => '27/09/2017',
					),
			) );
		$this->contrat_oeufs                           = $this->posts['6527'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-27 16:18:49',
				'post_date_gmt'     => '2017-09-27 14:18:49',
				'post_content'      => '',
				'post_title'        => 'Oeufs',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-27 16:18:49',
				'post_modified_gmt' => '2017-09-27 14:18:49',
				'post_type'         => 'amps_contrat',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_producteur' => $this->posts['5138'],
					),
			) );
		$this->contrat_instance_oeufs                  = $this->posts['6530'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-27 16:30:07',
				'post_date_gmt'     => '2017-09-27 14:30:07',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-28 10:05:06',
				'post_modified_gmt' => '2017-09-28 08:05:06',
				'post_type'         => 'amps_contrat_inst',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_instance_model'                 => $this->posts['6527'],
						'amapress_contrat_instance_type'                  => 'panier',
						'amapress_contrat_instance_date_debut'            => 1506556800,
						'amapress_contrat_instance_date_fin'              => 1532563200,
						'amapress_contrat_instance_date_ouverture'        => 1506556800,
						'amapress_contrat_instance_date_cloture'          => 1530144000,
						'amapress_contrat_instance_lieux'                 => $this->posts['33'],
						'amapress_contrat_instance_max_adherents'         => '100',
						'amapress_contrat_instance_liste_dates'           => '28/09/2017, 26/10/2017, 30/11/2017, 21/12/2017, 25/01/2018, 22/02/2018, 29/03/2018, 26/04/2018, 31/05/2018, 28/06/2018, 26/07/2018',
						'amapress_contrat_instance_paiements'             => 'a:3:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";}',
						'amapress_contrat_instance_liste_dates_paiements' => '28/09/2017, 30/11/2017, 25/01/2018',
					),
			) );
		$this->contrat_quantite_oeuf_1                 = $this->posts['6531'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-27 16:33:04',
				'post_date_gmt'     => '2017-09-27 14:33:04',
				'post_title'        => '6 OEUFS',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-28 10:05:06',
				'post_modified_gmt' => '2017-09-28 08:05:06',
				'post_type'         => 'amps_contrat_quant',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_quantite_contrat_instance' => $this->posts['6530'],
						'amapress_contrat_quantite_prix_unitaire'    => '3',
						'amapress_contrat_quantite_code'             => '6O',
						'amapress_contrat_quantite_description'      => '6 œufs (en moyenne) /mois ',
						'amapress_contrat_quantite_quantite'         => '0.0',
					),
			) );
		$this->contrat_quantite_poulet_1               = $this->posts['6552'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-27 17:33:23',
				'post_date_gmt'     => '2017-09-27 15:33:23',
				'post_title'        => '1 petit poulet',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-28 11:00:09',
				'post_modified_gmt' => '2017-09-28 09:00:09',
				'post_type'         => 'amps_contrat_quant',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_quantite_contrat_instance' => $this->posts['6551'],
						'amapress_contrat_quantite_prix_unitaire'    => '17.6',
						'amapress_contrat_quantite_code'             => '1PP',
						'amapress_contrat_quantite_description'      => 'Petit poulet (1,6 kg en moyenne)',
					),
			) );
		$this->adhesion_poulet_amapien_4_contrats      = $this->posts['6578'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-27 19:30:06',
				'post_date_gmt'     => '2017-09-27 17:30:06',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-27 19:30:06',
				'post_modified_gmt' => '2017-09-27 17:30:06',
				'post_type'         => 'amps_adhesion',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_adhesion_adherent'         => $this->users['26'],
						'amapress_adhesion_status'           => 'confirmed',
						'amapress_adhesion_date_debut'       => '1506556800',
						'amapress_adhesion_contrat_instance' => $this->posts['6551'],
						'amapress_adhesion_contrat_quantite' =>
							array(
								0 => $this->posts['6552'],
//								1 => $this->posts['6531'],
							),
						'amapress_adhesion_lieu'             => $this->posts['34'],
					),
			) );
		$this->producteur_fruits_prod                  = $this->posts['30'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-14 19:17:11',
				'post_date_gmt'     => '2016-09-14 17:17:11',
				'post_title'        => 'Fruits',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-01-27 15:30:11',
				'post_modified_gmt' => '2017-01-27 14:30:11',
				'post_type'         => 'amps_producteur',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_producteur_resume'                         => 'Production de pommes et poires',
						'amapress_producteur_user'                           => $this->users['8'],
						'amapress_producteur_referent_' . $this->posts['34'] => $this->users['7'],
						'amapress_producteur_referent_' . $this->posts['33'] => $this->users['9'],
					),
			) );
		$this->contrat_fruits                          = $this->posts['36'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-15 12:30:07',
				'post_date_gmt'     => '2016-09-15 10:30:07',
				'post_content'      => 'Engagements du bénéficiaire du panier',
				'post_title'        => 'Fruits',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-02-28 12:23:36',
				'post_modified_gmt' => '2017-02-28 11:23:36',
				'post_type'         => 'amps_contrat',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_presentation' => 'Engagements du bénéficiaire du panier ',
						'amapress_contrat_nb_visites'   => '1',
						'amapress_contrat_producteur'   => $this->posts['30'],
					),
			) );
		$this->contrat_instance_fruits                 = $this->posts['6429'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-20 19:58:13',
				'post_date_gmt'     => '2017-09-20 17:58:13',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-20 19:59:07',
				'post_modified_gmt' => '2017-09-20 17:59:07',
				'post_type'         => 'amps_contrat_inst',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_instance_model'                 => $this->posts['36'],
						'amapress_contrat_instance_type'                  => 'panier',
						'amapress_contrat_instance_date_debut'            => 1505952000,
						'amapress_contrat_instance_date_fin'              => 1526515200,
						'amapress_contrat_instance_date_ouverture'        => 1505952000,
						'amapress_contrat_instance_date_cloture'          => 1526515200,
						'amapress_contrat_instance_lieux'                 => array(
							$this->posts['33'],
							$this->posts['34']
						),
						'amapress_contrat_instance_max_adherents'         => '100',
						'amapress_contrat_instance_liste_dates'           => '21/09/2017, 19/10/2017, 23/11/2017, 21/12/2017, 11/01/2018, 08/02/2018, 15/03/2018, 12/04/2018, 17/05/2018',
						'amapress_contrat_instance_paiements'             => 'a:3:{i:0;s:1:""1"";i:1;s:1:""2"";i:2;s:1:""3"";}',
						'amapress_contrat_instance_liste_dates_paiements' => '21/09/2017, 21/12/2017, 08/02/2018',
					),
			) );
		$this->contrat_quantite_fruits_1               = $this->posts['6430'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-20 19:59:07',
				'post_date_gmt'     => '2017-09-20 17:59:07',
				'post_title'        => 'panier',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-20 19:59:07',
				'post_modified_gmt' => '2017-09-20 17:59:07',
				'post_type'         => 'amps_contrat_quant',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_quantite_contrat_instance' => $this->posts['6429'],
						'amapress_contrat_quantite_prix_unitaire'    => '9.5',
						'amapress_contrat_quantite_code'             => 'PANIER_2017',
						'amapress_contrat_quantite_description'      => 'panier 2017',
						'amapress_contrat_quantite_quantite'         => '1',
					),
			) );
		$this->adhesion_fruits_amapien_4_contrats      = $this->posts['6445'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-23 18:09:40',
				'post_date_gmt'     => '2017-09-23 16:09:40',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-23 18:09:40',
				'post_modified_gmt' => '2017-09-23 16:09:40',
				'post_type'         => 'amps_adhesion',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_adhesion_adherent'         => $this->users['26'],
						'amapress_adhesion_adherent2'        => $this->users['36'],
						'amapress_adhesion_status'           => 'confirmed',
						'amapress_adhesion_date_debut'       => '1505952000',
						'amapress_adhesion_contrat_instance' => $this->posts['6429'],
						'amapress_adhesion_contrat_quantite' =>
							array(
								0 => $this->posts['6430'],
							),
						'amapress_adhesion_lieu'             => $this->posts['34'],
					),
			) );
		$this->producteur_champignons_prod             = $this->posts['31'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-15 11:49:30',
				'post_date_gmt'     => '2016-09-15 09:49:30',
				'post_title'        => 'Champignons',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-03-14 18:25:51',
				'post_modified_gmt' => '2017-03-14 17:25:51',
				'post_type'         => 'amps_producteur',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_producteur_resume'                             => 'La philosophie : la qualité plutôt que la quantité',
						'amapress_producteur_historique'                         => '4 générations de champignons',
						'amapress_producteur_user'                               => $this->users['10'],
						'amapress_producteur_referent_' . $this->posts['34']     => $this->users['7'],
						'amapress_producteur_referent_' . $this->posts['33']     => $this->users['9'],
						'amapress_producteur_adresse_exploitation_long'          => '2.180',
						'amapress_producteur_adresse_exploitation_lat'           => '49.061',
						'amapress_producteur_adresse_exploitation_location_type' => 'RANGE_INTERPOLATED',
					),
			) );
		$this->contrat_champignons                     = $this->posts['37'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-15 12:31:42',
				'post_date_gmt'     => '2016-09-15 10:31:42',
				'post_content'      => 'Engagements du bénéficiaire du panier ',
				'post_title'        => 'Champignons',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-02-28 12:23:45',
				'post_modified_gmt' => '2017-02-28 11:23:45',
				'post_type'         => 'amps_contrat',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_presentation' => 'Engagements du bénéficiaire du panier ',
						'amapress_contrat_nb_visites'   => '1',
						'amapress_contrat_producteur'   => $this->posts['31'],
					),
			) );
		$this->contrat_instance_champignons            = $this->posts['5322'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-10 17:00:49',
				'post_date_gmt'     => '2017-09-10 15:00:49',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-14 00:09:43',
				'post_modified_gmt' => '2017-09-13 22:09:43',
				'post_type'         => 'amps_contrat_inst',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_instance_model'                 => $this->posts['37'],
						'amapress_contrat_instance_max_adherents'         => '200',
						'amapress_contrat_instance_date_debut'            => 1505347200,
						'amapress_contrat_instance_date_fin'              => 1530316800,
						'amapress_contrat_instance_date_ouverture'        => 1504224000,
						'amapress_contrat_instance_date_cloture'          => 1526515200,
						'amapress_contrat_instance_lieux'                 => array(
							$this->posts['33'],
							$this->posts['34']
						),
						'amapress_contrat_instance_type'                  => 'panier',
						'amapress_contrat_instance_nb_visites'            => '1',
						'amapress_contrat_instance_contrat'               => 'Engagements du bénéficiaire du panier ',
//    'amapress_contrat_instance_is_principal' => '1',
						'amapress_contrat_instance_liste_dates'           => '06/07/2017, 03/08/2017, 31/08/2017, 21/09/2017, 05/10/2017, 02/11/2017, 07/12/2017, 04/01/2018, 01/02/2018, 01/03/2018, 05/04/2018, 03/05/2018, 07/06/2018',
						'amapress_contrat_instance_paiements'             => 'a:2:{i:0;s:1:""1"";i:1;s:1:""2"";}',
						'amapress_contrat_instance_liste_dates_paiements' => '14/09/2017, 05/10/2017, 02/11/2017, 07/12/2017, 04/01/2018, 01/02/2018, 01/03/2018, 05/04/2018, 03/05/2018, 07/06/2018',
					),
			) );
		$this->contrat_quantite_champignons_1          = $this->posts['5323'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-10 17:00:49',
				'post_date_gmt'     => '2017-09-10 15:00:49',
				'post_title'        => 'Petit',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-14 00:09:43',
				'post_modified_gmt' => '2017-09-13 22:09:43',
				'post_type'         => 'amps_contrat_quant',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_quantite_contrat_instance' => $this->posts['5322'],
						'amapress_contrat_quantite_prix_unitaire'    => '7',
						'amapress_contrat_quantite_quantite'         => '7',
						'amapress_contrat_quantite_code'             => 'Petit',
					),
			) );
		$this->adhesion_champignons_amapien_4_contrats = $this->posts['5729'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-12 16:38:44',
				'post_date_gmt'     => '2017-09-12 14:38:44',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-12 16:40:20',
				'post_modified_gmt' => '2017-09-12 14:40:20',
				'post_type'         => 'amps_adhesion',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_adhesion_adherent'         => $this->users['26'],
						'amapress_adhesion_status'           => 'confirmed',
						'amapress_adhesion_date_debut'       => '1505347200',
						'amapress_adhesion_contrat_instance' => $this->posts['5322'],
						'amapress_adhesion_contrat_quantite' =>
							array(
								0 => $this->posts['5323'],
							),
						'amapress_adhesion_lieu'             => $this->posts['34'],
						'amapress_adhesion_paiements'        => '1',
					),
			) );

		$this->producteur_legumes_prod             = $this->posts['27'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-14 16:07:24',
				'post_date_gmt'     => '2016-09-14 14:07:24',
				'post_title'        => 'Le maraîcher',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-13 12:29:05',
				'post_modified_gmt' => '2017-09-13 10:29:05',
				'post_type'         => 'amps_producteur',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_producteur_resume'                         => 'Présentation de Xavier, notre maraîcher partenaire',
						'amapress_producteur_user'                           => $this->users['5'],
						'amapress_producteur_referent_' . $this->posts['34'] => $this->users['6'],
					),
			) );
		$this->contrat_legumes                     = $this->posts['35'] = self::factory()->post->create(
			array(
				'post_date'         => '2016-09-15 12:28:36',
				'post_date_gmt'     => '2016-09-15 10:28:36',
				'post_content'      => 'Engagements de l\'adhérent-e ',
				'post_title'        => 'Légumes',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-02-28 12:23:24',
				'post_modified_gmt' => '2017-02-28 11:23:24',
				'post_type'         => 'amps_contrat',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_presentation' => 'Engagements de l\'adhérent-e ',
						'amapress_contrat_nb_visites'   => '1',
						'amapress_contrat_producteur'   => $this->posts['27'],
					),
			) );
		$this->contrat_instance_legumes            = $this->posts['5130'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-08-31 20:44:04',
				'post_date_gmt'     => '2017-08-31 18:44:04',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-10-06 18:42:11',
				'post_modified_gmt' => '2017-10-06 16:42:11',
				'post_type'         => 'amps_contrat_inst',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_instance_model'                 => $this->posts['35'],
						'amapress_contrat_instance_max_adherents'         => '150',
						'amapress_contrat_instance_date_debut'            => 1505347200,
						'amapress_contrat_instance_date_fin'              => 1536192000,
						'amapress_contrat_instance_date_ouverture'        => 1503532800,
						'amapress_contrat_instance_date_cloture'          => 1535673600,
						'amapress_contrat_instance_lieux'                 => array(
							$this->posts['33'],
							$this->posts['34']
						),
						'amapress_contrat_instance_type'                  => 'panier',
						'amapress_contrat_instance_nb_visites'            => '1',
						'amapress_contrat_instance_contrat'               => 'c',
						'amapress_contrat_instance_is_principal'          => '1',
						'amapress_contrat_instance_liste_dates'           => '14/09/2017, 21/09/2017, 28/09/2017, 05/10/2017, 12/10/2017, 19/10/2017, 26/10/2017, 02/11/2017, 09/11/2017, 16/11/2017, 23/11/2017, 30/11/2017, 07/12/2017, 14/12/2017, 21/12/2017, 04/01/2018, 11/01/2018, 18/01/2018, 25/01/2018, 01/02/2018, 08/02/2018, 15/02/2018, 22/02/2018, 01/03/2018, 08/03/2018, 15/03/2018, 22/03/2018, 29/03/2018, 03/05/2018, 10/05/2018, 17/05/2018, 24/05/2018, 31/05/2018, 07/06/2018, 14/06/2018, 21/06/2018, 28/06/2018, 05/07/2018, 12/07/2018, 19/07/2018, 26/07/2018, 02/08/2018, 09/08/2018, 16/08/2018, 23/08/2018, 30/08/2018, 06/09/2018',
						'amapress_contrat_instance_paiements'             => 'a:5:{i:0;s:1:""1"";i:1;s:1:""2"";i:2;s:1:""3"";i:3;s:1:""5"";i:4;s:2:""10"";}',
						'amapress_contrat_instance_liste_dates_paiements' => '05/10/2017, 02/11/2017, 07/12/2017, 04/01/2018, 01/02/2018, 01/03/2018, 29/03/2018, 03/05/2018, 07/06/2018, 05/07/2018, 02/08/2018, 06/09/2018',
					),
			) );
		$this->contrat_quantite_legumes_1          = $this->posts['5132'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-08-31 20:44:04',
				'post_date_gmt'     => '2017-08-31 18:44:04',
				'post_title'        => 'Panier',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-10-06 18:42:11',
				'post_modified_gmt' => '2017-10-06 16:42:11',
				'post_type'         => 'amps_contrat_quant',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_quantite_contrat_instance' => $this->posts['5130'],
						'amapress_contrat_quantite_prix_unitaire'    => '20',
						'amapress_contrat_quantite_quantite'         => '1',
						'amapress_contrat_quantite_code'             => 'entier',
					),
			) );
		$this->adhesion_legumes_amapien_4_contrats = $this->posts['5394'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-11 14:53:08',
				'post_date_gmt'     => '2017-09-11 12:53:08',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-11 14:54:29',
				'post_modified_gmt' => '2017-09-11 12:54:29',
				'post_type'         => 'amps_adhesion',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_adhesion_adherent'         => $this->users['26'],
						'amapress_adhesion_lieu'             => $this->posts['34'],
						'amapress_adhesion_contrat_instance' => $this->posts['5130'],
						'amapress_adhesion_contrat_quantite' =>
							array(
								0 => $this->posts['5132'],
							),
						'amapress_adhesion_date_debut'       => '1505347200',
						'amapress_adhesion_status'           => 'confirmed',
						'amapress_adhesion_paiements'        => '2',
					),
			) );


		$this->adhesion_champignons_amapien_2_contrats = $this->posts['5757'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-12 23:31:32',
				'post_date_gmt'     => '2017-09-12 21:31:32',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-12 23:33:06',
				'post_modified_gmt' => '2017-09-12 21:33:06',
				'post_type'         => 'amps_adhesion',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_adhesion_adherent'         => $this->users['54'],
						'amapress_adhesion_status'           => 'confirmed',
						'amapress_adhesion_date_debut'       => '1505347200',
						'amapress_adhesion_contrat_instance' => $this->posts['5322'],
						'amapress_adhesion_contrat_quantite' =>
							array(
								0 => $this->posts['5323'],
							),
						'amapress_adhesion_lieu'             => $this->posts['33'],
						'amapress_adhesion_paiements'        => '2',
					),
			) );
		$this->contrat_quantite_legumes_2              = $this->posts['5131'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-08-31 20:44:04',
				'post_date_gmt'     => '2017-08-31 18:44:04',
				'post_title'        => '1/2 Panier',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-10-06 18:42:11',
				'post_modified_gmt' => '2017-10-06 16:42:11',
				'post_type'         => 'amps_contrat_quant',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_contrat_quantite_contrat_instance' => $this->posts['5130'],
						'amapress_contrat_quantite_prix_unitaire'    => '10',
						'amapress_contrat_quantite_quantite'         => '0.5',
						'amapress_contrat_quantite_code'             => 'demi',
					),
			) );
		$this->adhesion_legumes_amapien_2_contrats     = $this->posts['5544'] = self::factory()->post->create(
			array(
				'post_date'         => '2017-09-11 21:45:54',
				'post_date_gmt'     => '2017-09-11 19:45:54',
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => '2017-09-11 21:51:57',
				'post_modified_gmt' => '2017-09-11 19:51:57',
				'post_type'         => 'amps_adhesion',
				'filter'            => 'raw',
				'meta_input'        =>
					array(
						'amapress_adhesion_adherent'         => $this->users['54'],
						'amapress_adhesion_lieu'             => $this->posts['33'],
						'amapress_adhesion_contrat_instance' => $this->posts['5130'],
						'amapress_adhesion_contrat_quantite' =>
							array(
								0 => $this->posts['5131'],
							),
						'amapress_adhesion_date_debut'       => '1505347200',
						'amapress_adhesion_status'           => 'confirmed',
						'amapress_adhesion_adherent2'        => $this->users['36'],
						'amapress_adhesion_paiements'        => '10',
					),
			) );

		$this->loginUser( $this->administrateur );
		echo 'Getting contrats';
		$contrats = AmapressContrats::get_active_contrat_instances_ids();
		$this->assertNotEmpty( $contrats );

		if ( $create_distrib_and_paniers ) {
			echo 'Generating Distributions and paniers';
			foreach ( AmapressContrats::get_active_contrat_instances_ids() as $contrat_instances_id ) {
				AmapressDistributions::generate_distributions( $contrat_instances_id );
				AmapressPaniers::generate_paniers( $contrat_instances_id );
			}
		}

//		$this->commit_transaction();
	}

	protected function set_is_admin_true() {
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}
	}

	protected function set_post( $post_object ) {
		setup_postdata( $GLOBALS['post'] =& $post_object );
	}

	protected function supposedPostResultSet( $post_ids ) {
		return implode( ',',
			array_map(
				function ( $p ) {
					return "\$this->posts['$p']";
				}, $post_ids
			) );
	}

	protected function supposedUsersResultSet( $user_ids ) {
		return implode( ',',
			array_map(
				function ( $p ) {
					return "\$this->users['$p']";
				}, $user_ids
			) );
	}

	protected function assertUserIdsOrCount( $expected_users, $actual_user_ids ) {
		if ( is_int( $expected_users ) ) {
			$this->assertCount( $expected_users, $actual_user_ids, 'Arrays are not equal : ' . count( $actual_user_ids ) );
		} else {
			$this->assertEquals( $expected_users, $actual_user_ids, 'Arrays are not equal : ' . $this->supposedUsersResultSet( $actual_user_ids ) );
		}
	}

	protected function assertPostIdsOrCount( $expected_posts, $actual_post_ids ) {
		if ( is_int( $expected_posts ) ) {
			$this->assertCount( $expected_posts, $actual_post_ids, 'Arrays are not equal : ' . count( $actual_post_ids ) );
		} else {
			$this->assertEquals( $expected_posts, $actual_post_ids, 'Arrays are not equal : ' . $this->supposedPostResultSet( $actual_post_ids ) );
		}
	}

	protected function getIDs( $posts_or_uses ) {
		return array_map(
			function ( $p ) {
				return $p->ID;
			},
			$posts_or_uses
		);
	}

//	protected function call_wp_admin( $url ) {
//		$script_name = parse_url($url, PHP_URL_PATH);
//		$query = wp_parse_args( parse_url($url, PHP_URL_QUERY) );
//		foreach ($query as $k => $v) {
//			$_REQUEST[$k] = $v;
//			$_GET[$k] = $v;
//		}
//		$this->go_to( $url );
//
//		require ABSPATH . WPINC . '/version.php';
//
//		wp_fix_server_vars();
//
//		require ABSPATH . $script_name;
//	}

	/** @param string $url */
	protected function call_url( $url ) {
		$this->go_to( $url );

		the_post();
		the_author();
		the_category();
		the_content();
		the_excerpt();
		the_permalink();
		the_tags();
	}

	/** @param WP_Post $post */
	protected function call_post( $post ) {
		$this->call_url( get_permalink( $post->ID ) );
	}

	protected function loginUser( $user_id ) {
		wp_cache_flush();
		if ( empty( $user_id ) ) {
			$this->logOut();
		}
		wp_set_current_user( $user_id );
	}

	protected function logOut() {
		wp_logout();
	}
}