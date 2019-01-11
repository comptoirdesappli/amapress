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
	}
} );

function amapress_inscriptions_to_validate_recall_options() {
	return array(
		array(
			'id'                  => 'inscriptions-validate-recall-1',
			'name'                => 'Rappel 1',
			'desc'                => 'Inscriptions à valider',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_inscriptions_validate',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'                  => 'inscriptions-validate-recall-2',
			'name'                => 'Rappel 2',
			'desc'                => 'Inscriptions à valider',
			'type'                => 'event-scheduler',
			'hook_name'           => 'amapress_recall_inscriptions_validate',
			'hook_args_generator' => function ( $option ) {
				return amapress_get_next_distributions_cron();
			},
		),
		array(
			'id'       => 'inscriptions-validate-recall-mail-subject',
			'name'     => 'Sujet du mail',
			'type'     => 'text',
			'sanitize' => false,
			'default'  => '%%nb_inscriptions%% inscriptions restent à valider',
		),
		array(
			'id'      => 'inscriptions-validate-recall-mail-content',
			'name'    => 'Contenu du mail',
			'type'    => 'editor',
			'default' => wpautop( "Bonjour,\nLes %%nb_inscriptions%% inscriptions suivantes restent à valider (%%lien_inscriptions%%):\n%%inscriptions%%\n\n%%nom_site%%" ),
			'desc'    => 'Les placeholders suivants sont disponibles:' .
			             Amapress::getPlaceholdersHelpTable( 'inscr-validate-placeholders', [
				             'nb_inscriptions'   => 'Nombre d\'inscriptions à valider',
				             'inscriptions'      => 'Liste des inscriptions à valider',
				             'lien_inscriptions' => 'Lien vers la liste des inscriptions à valider'
			             ], null ),
		),
		array(
			'id'           => 'inscriptions-validate-recall-cc',
			'name'         => amapress__( 'Cc' ),
			'type'         => 'select-users',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Mails en copie',
		),
		array(
			'id'           => 'inscriptions-validate-recall-cc-groups',
			'name'         => amapress__( 'Groupes Cc' ),
			'type'         => 'select',
			'options'      => 'amapress_get_collectif_target_queries',
			'autocomplete' => true,
			'multiple'     => true,
			'tags'         => true,
			'desc'         => 'Groupe(s) en copie',
		),
		array(
			'type' => 'save',
		),
	);
}
