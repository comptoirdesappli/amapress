<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 16/02/2018
 * Time: 05:52
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'amapress_recall_inscriptions_validate', function ( $args ) {
	$dist = AmapressDistribution::getBy( $args['id'] );
	if ( null == $dist ) {
		echo '<p>' . __( 'Distribution introuvable', 'amapress' ) . '</p>';

		return;
	}

	/** @var AmapressAdhesion[] $adhesions */
	$adhesions = array_map( function ( $p ) {
		return AmapressAdhesion::getBy( $p );
	}, get_posts( [
		'posts_per_page'  => - 1,
		'post_type'       => AmapressAdhesion::INTERNAL_POST_TYPE,
		'amapress_date'   => 'active',
		'amapress_status' => 'to_confirm'
	] ) );

	if ( empty( $adhesions ) ) {
		echo '<p>' . __( 'Pas d\'inscriptions à valider', 'amapress' ) . '</p>';

		return;
	}

	$adhesions_by_referent = array_group_by( $adhesions,
		function ( $adh ) {
			/** @var AmapressAdhesion $adh */
			return implode( ',', $adh->getContrat_instance()->getAllReferentsEmails( $adh->getLieuId() ) );
		} );

	foreach ( $adhesions_by_referent as $ref_emails => $inscriptions ) {
		$replacements = [];

		$replacements['nb_inscriptions']   = count( $inscriptions );
		$replacements['inscriptions']      = '<ul>' . implode( '', array_map( function ( $adh ) {
				/** @var AmapressAdhesion $adh */
				return '<li>' . Amapress::makeLink( $adh->getAdminEditLink(), $adh->getTitle() ) . '</li>';
			}, $inscriptions ) ) . '</ul>';
		$replacements['lien_inscriptions'] = admin_url( 'edit.php?post_type=amps_adhesion&amapress_date=active&amapress_status=to_confirm' );

		$subject = Amapress::getOption( 'inscriptions-validate-recall-mail-subject' );
		$content = Amapress::getOption( 'inscriptions-validate-recall-mail-content' );
		foreach ( $replacements as $k => $v ) {
			$subject = str_replace( "%%$k%%", $v, $subject );
			$content = str_replace( "%%$k%%", $v, $content );
		}
		$subject = amapress_replace_mail_placeholders( $subject, null );
		$content = amapress_replace_mail_placeholders( $content, null );
		amapress_wp_mail(
			$ref_emails,
			$subject,
			$content,
			'', [],
			amapress_get_recall_cc_from_option( 'inscriptions-validate-recall-cc' )
		);
		echo '<p>' . __( 'Email d\'inscriptions à valider envoyé', 'amapress' ) . '</p>';
	}
} );

function amapress_inscriptions_to_validate_recall_options() {
	return array(
		array(
			'id'         => 'inscriptions-validate-recall-1',
			'name'       => __( 'Rappel 1', 'amapress' ),
			'desc'       => __( 'Inscriptions à valider', 'amapress' ),
			'type'       => 'event-scheduler',
			'show_after' => true,
			'hook_name'  => 'amapress_recall_inscriptions_validate',

			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'inscriptions-validate-recall-2',
			'name'                => __( 'Rappel 2', 'amapress' ),
			'desc'                => __( 'Inscriptions à valider', 'amapress' ),
			'type'                => 'event-scheduler',
			'show_resend_links'   => false,
			'show_test_links'     => false,
			'show_after'          => true,
			'hook_name'           => 'amapress_recall_inscriptions_validate',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'inscriptions-validate-recall-mail-subject',
			'name'     => __( 'Sujet de l\'email', 'amapress' ),
			'type'     => 'text',
			'sanitize' => false,
			'default'  => '%%nb_inscriptions%% inscriptions restent à valider',
		),
		array(
			'id'      => 'inscriptions-validate-recall-mail-content',
			'name'    => __( 'Contenu de l\'email', 'amapress' ),
			'type'    => 'editor',
			'default' => wpautop( __( "Bonjour,\nLes %%nb_inscriptions%% inscriptions suivantes restent à valider (%%lien_inscriptions%%):\n%%inscriptions%%\n\n%%nom_site%%", 'amapress' ) ),
			'desc'    => function ( $option ) {
				return __( 'Les placeholders suivants sont disponibles:', 'amapress' ) .
				       Amapress::getPlaceholdersHelpTable( 'inscr-validate-placeholders', [
					       'nb_inscriptions'   => __( 'Nombre d\'inscriptions à valider', 'amapress' ),
					       'inscriptions'      => __( 'Liste des inscriptions à valider', 'amapress' ),
					       'lien_inscriptions' => __( 'Lien vers la liste des inscriptions à valider', 'amapress' )
				       ], null, [], 'recall' );
			},
		),
		array(
			'id'           => 'inscriptions-validate-recall-cc',
			'name'         => __( 'Cc', 'amapress' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Emails en copie', 'amapress' ),
		),
		array(
			'id'           => 'inscriptions-validate-recall-cc-groups',
			'name'         => __( 'Groupes Cc', 'amapress' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => __( 'Groupe(s) en copie', 'amapress' ),
		),
		array(
			'type' => 'save',
		),
	);
}
