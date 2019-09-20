<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'template_redirect', function () {
	if ( 'shouldredirect' == get_query_var( 'amp_action' ) ) {
		wp_die( '<strong style="color: #2b542c">Redirection réussie</strong>' );
	}
} );

function amapress_get_plugin_install_link( $plugin_slug ) {
	$action = 'install-plugin';

	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $plugin_slug
			),
			admin_url( 'update.php' )
		),
		$action . '_' . $plugin_slug
	);
}

function amapress_get_plugin_activate_link( $plugin_slug ) {
	$installed_plugins = array_keys( get_plugins() );
	$installed_plugins = array_combine( array_map( function ( $v ) {
		$vv = explode( '/', $v );

		return $vv[0];
	}, $installed_plugins ), array_values( $installed_plugins ) );
	$plugin_slug       = isset( $installed_plugins[ $plugin_slug ] ) ? $installed_plugins[ $plugin_slug ] : $plugin_slug;
	$action            = 'activate';

	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $plugin_slug
			),
			admin_url( 'plugins.php' )
		),
		'activate-plugin_' . $plugin_slug
	);
}

function amapress_get_check_state( $state, $name, $message, $link, $values = null, $target_blank = true ) {
	return array(
		'state'        => $state,
		'name'         => $name,
		'message'      => $message,
		'link'         => $link,
		'values'       => $values,
		'target_blank' => $target_blank,
	);
}

function amapress_is_plugin_active( $plugin_slug ) {
// Check if get_plugins() function exists. This is required on the front end of the
// site, since it is in a file that is normally only loaded in the admin.
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$installed_plugins      = array_keys( get_plugins() );
	$network_active_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
	$active_plugins         = array_values( get_option( 'active_plugins', array() ) );
	$active_plugins         = array_merge( $active_plugins, $network_active_plugins );
//    var_dump($active_plugins);
	$active_plugins    = array_map( function ( $v ) {
		$vv = explode( '/', $v );

		return $vv[0];
	}, $active_plugins );
	$installed_plugins = array_map( function ( $v ) {
		$vv = explode( '/', $v );

		return $vv[0];
	}, $installed_plugins );

	return in_array( $plugin_slug, $active_plugins ) ? 'active' : ( in_array( $plugin_slug, $installed_plugins ) ? 'installed' : 'not-installed' );
}

function amapress_check_plugin_install( $plugin_slug, $plugin_name, $message_if_install_needed, $not_installed_level = 'warning' ) {
	$is_active = amapress_is_plugin_active( $plugin_slug );

	return amapress_get_check_state(
		$is_active == 'active' ? 'success' : $not_installed_level,
		$plugin_name . ( $is_active != 'active' ? ' (' . ( $is_active == 'not-installed' ? 'installer' : 'activer' ) . ')' : '' ),
		$message_if_install_needed,
		$is_active == 'not-installed' ? amapress_get_plugin_install_link( $plugin_slug ) : ( $is_active == 'installed' ? amapress_get_plugin_activate_link( $plugin_slug ) : '' )
	);
}

function amapress_clean_state_transient() {
	static $amapress_clean_state_transient = false;

	if ( ! $amapress_clean_state_transient ) {
		delete_transient( 'amapress_state_summary' );
		$amapress_clean_state_transient = true;
	}
}

function amapress_get_state() {
	amapress_clean_state_transient();

	$state               = array();
	$state['01_plugins'] = array();
//    $state['01_plugins'][] = amapress_check_plugin_install('google-sitemap-generator', 'Google XML Sitemaps',
//        'Permet un meilleur référencement par les moteurs de recherche pour votre AMAP');
	$state['01_plugins'][] = amapress_check_plugin_install( 'updraftplus', 'UpdraftPlus WordPress Backup',
		'<strong>Recommandé</strong> : Sauvegarde du site. Permet de réinstaller en cas de panne, bug, hack. <br/> Voir la <a target="_blank" href="' . admin_url( 'options-general.php?page=updraftplus' ) . '">Configuration de la sauvegarde</a>. Configurer ici pour sauvegarder les données de votre site vers un drive ou stockage externe.',
		'error' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'akismet', 'Akismet',
		'<strong>Recommandé</strong> : Protège le site du SPAM.',
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'block-bad-queries', 'Block Bad Queries',
		'<strong>Recommandé</strong> : Protège votre site contre les attaques par requêtes malveillantes',
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'new-user-approve', 'New User Approve',
		'<strong>Optionnel</strong> : Installer ce plugin si le paramètre « Création de compte sur le site » (Section 2 – configuration) est activé. Une inscription en ligne nécessitera une validation de l’utilisateur par un administrateur.',
		Amapress::userCanRegister() ? 'error' : 'warning' );
//    $state['01_plugins'][] = amapress_check_plugin_install('smtp-mailing-queue', 'SMTP Mailing Queue',
//        'Installer ce plugin permet d\'envoyer les mails aux adhérents au fur et à mesure pour éviter une blocage SMTP (par ex, lors des imports CSV)');
	$state['01_plugins'][] = amapress_check_plugin_install( 'tinymce-advanced', 'TinyMCE Advanced',
		'<strong>Recommandé</strong> : Enrichi l\'éditeur de texte intégré de Wordpress afin de faciliter la création de contenu sur le site',
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'google-sitemap-generator', 'Google Sitemap Generator',
		'<strong>Recommandé</strong> : Utilisation simple, améliore le référencement du site en générant un plan du site et en notifiant les moteurs de recherche des modifications du site. 
<br/>Après activation rendez-vous dans sa <a target="_blank" href="' . admin_url( 'options-general.php?page=google-sitemap-generator%2Fsitemap.php#sm_includes' ) . '">configuration</a> (Section Contenu du sitemap/Autres types d\'article) et cocher les cases "Inclure les articles de type Produits/Recettes/Producteurs/Lieux de distribution/Productions"',
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'wordpress-seo', 'Yoast SEO',
		'<strong>Optionnel</strong> : Utilisation avancée, améliore le référencement du site. Ce plugin ajoute de nombreuse options dans le back-office, à installer par un webmaster.',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'count-per-day', 'Count Per Day',
		'<strong>Optionnel</strong> : Permet d\'obtenir des statistiques de visites journalières simples sans recourir à des moteurs de statistiques externes.',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'unconfirmed', 'Unconfirmed',
		'<strong>Recommandé</strong> : Permet de gérer les inscriptions en cours, renvoyer le mail de bienvenue avec le lien pour activer le compte utilisateur.',
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'user-switching', 'User Switching',
		'<strong>Recommandé</strong> : Permet aux administrateurs de consulter Amapress avec un autre compte utilisateur. Ce plugin est à installer par un webmaster. ',
		'warning' );

	$state['01_plugins'][] = amapress_check_plugin_install( 'wp-maintenance', 'WP Maintenance',
		'<strong>Optionnel</strong> : Permet d\'indiquer aux visiteurs que le site de votre AMAP est en construction et d\'éviter l\'affichage de contenu non finalisé.',
		'info' );
//	$state['01_plugins'][] = amapress_check_plugin_install( 'aryo-activity-log', 'Activity Log',
//		'<strong>Optionnel</strong> : Permet de tracer l\'activité des utilisateurs dans votre AMAP (création, modification, suppression de contenu, pages, articles, utilisateurs...)',
//		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'error-log-monitor', 'Error Log Monitor',
		'<strong>Optionnel</strong> : Permet de logger les erreurs PHP/Wordpress et de les envoyer automatiquement au support Amapress pour aider à son développement',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'uk-cookie-consent', 'GDPR Cookie Consent Banner',
		'<strong>Recommandé</strong> : Affiche un bandeau de consentement à l\'utilisation des cookies sur votre site. Cela est nécessaire pour être en conformité avec la loi RGPD, par exemple, si vous faites des statistiques (ie, Google Analytics) sur les visiteurs.',
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'gdpr', 'GDPR Avancé/Professionel',
		'<strong>Optionnel</strong> : Utilisation avancée, suite d\'outils relatifs à la réglementation européenne RGPD sur la protection des données.',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'contact-form-7', 'Contact Form 7',
		'<strong>Optionnel</strong> : Permet de créer des formulaires de préinscription à l’AMAP, de contacter les auteurs de recettes…',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'icalendrier', 'iCalendrier',
		'<strong>Optionnel</strong> : Affiche la date du jour avec la fête du jour et les phases de la lune',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'latest-post-shortcode', 'Latest Post Shortcode',
		'<strong>Optionnel</strong> : Permet de créér une gallerie des articles récents (par ex, pour donner des nouvelles de l\'AMAP sur la page d\'Acceuil',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'feed-them-social', 'Feed Them Social',
		'<strong>Optionnel</strong> : Permet d\'afficher le flux d\'actualité d\'une page Facebook/Twitter/Instagram..., par exemple, la page Facebook de votre AMAP.',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'external-media', 'External Media',
		'<strong>Optionnel</strong> : Permet de référencer des documents accessibles sur GoogleDrive, OneDrive, DropBox sans les importer via la «Media Library » de Wordpress',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'enable-media-replace', 'Enable Media Replace',
		'<strong>Recommandé</strong> : Permet de remplacer facilement une image ou un contrat Word dans la « Media Library » de Wordpress',
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'imsanity', 'Imsanity',
		'<strong>Optionnel</strong> : Permet d’optimiser le poids des images dans la « Media Library » de Wordpress. Ce plugin est à installer par un webmaster. ',
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'bbpress', 'bbPress',
		'<strong>Optionnel</strong> : Permet de gérer un forum (avec toutes ses fonctionnalités) sur le site.',
		'info' );

	$state['05_config'] = array();

	if ( version_compare( phpversion(), '7.0', '<' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'warning',
			'PHP 7 ou sup recommandée (actuellement ' . phpversion() . ')',
			'Voir la configuration de votre hébergement (par ex, pour <a href="https://docs.ovh.com/fr/hosting/configurer-le-php-sur-son-hebergement-web-mutu-2014/">OVH</a>). Utiliser la version 7 ou supérieur de PHP est recommandé pour obtenir des performances optimales pour WordPress et Amapress.',
			''
		);
	}

	$github_updater = get_option( 'github_updater' );
	if ( is_multisite() ) {
		$github_updater = get_site_option( 'github_updater' );
	}
	if ( empty( $github_updater ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'error',
			'Le plugin GitHub Updater est requis pour la bonne mise à jour d\'Amapress',
			'Veuillez utiliser l\'installateur automatique qui est affiché en haut du <a target="_blank" href="' . admin_url( 'index.php' ) . '">tableau de bord</a> ou suivre la <a target="_blank" href="https://github.com/afragen/github-updater/wiki/Installation">procédure d\'installation manuelle</a>',
			''
		);
	} else if ( empty( $github_updater['github_access_token'] ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'error',
			'Un jeton d\'accès GitHub (Personal Access Token) pour le plugin GitHub Updater est requis pour la bonne mise à jour d\'Amapress',
			'Veuillez créer un Personal Access Token en suivant ce <a target="_blank" href="https://github.com/afragen/github-updater/wiki/Messages#personal-github-access-token">lien</a>',
			admin_url( 'options-general.php?page=github-updater&tab=github_updater_settings&subtab=github' )
		);
	}

	if ( ! extension_loaded( 'zip' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'error',
			'Extension PHP ZIP',
			'L\'extension ZIP de PHP doit être activée pour le bon fonctionnement d\'Amapress',
			admin_url( 'https://www.php.net/manual/fr/zip.setup.php' )
		);
	}
	if ( ! extension_loaded( 'curl' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'error',
			'Extension PHP cURL',
			'L\'extension cURL de PHP doit être activée pour le bon fonctionnement d\'Amapress',
			admin_url( 'https://www.php.net/manual/fr/curl.setup.php' )
		);
	}

	if ( ! extension_loaded( 'imap' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'warning',
			'Extension PHP IMAP',
			'L\'extension IMAP de PHP doit être activée pour que les Emails groupés soient actifs',
			admin_url( 'https://www.php.net/manual/fr/imap.setup.php' )
		);
	}

	$state['05_config'][] = amapress_get_check_state(
		is_ssl() ? 'success' : 'warning',
		is_ssl() ? 'HTTPS Activé' : 'HTTPS Désactivé',
		'Passer votre site en HTTPS améliore sa sécurité et son référencement. Voir plugin Really Simple SSL ci-dessous.',
		''
	);
	$state['05_config'][] = amapress_check_plugin_install( 'really-simple-ssl', 'Really Simple SSL',
		'<strong>Plugin recommandé</strong> : Passer votre site en HTTPS sécurise et protège les échanges de données et les données de votre AMAP.',
		'warning' );

	$permalink_structure = get_option( 'permalink_structure' );
	if ( defined( 'FREE_PAGES_PERSO' ) && FREE_PAGES_PERSO ) {
		$state['05_config'][] = amapress_get_check_state(
			empty( $permalink_structure )
			|| ! in_array( $permalink_structure,
				[
					'/index.php/%year%/%monthnum%/%day%/%postname%/',
					'/index.php/%year%/%monthnum%/%postname%/',
					'/index.php/%postname%/'
				] ) ? 'error' : 'success',
			'Réglage des permaliens',
			'Le réglage des permaliens pour Free Pages Perso doit être "Structure personnalisée", commencer par /index.php/ suivi de "%postname%/" ou "%year%/%monthnum%/%postname%/" ou "%year%/%monthnum%/%day%/%postname%/"',
			admin_url( 'options-permalink.php' )
		);
	} else {
		$state['05_config'][] = amapress_get_check_state(
			empty( $permalink_structure )
			|| ! in_array( $permalink_structure,
				[
					'/%year%/%monthnum%/%day%/%postname%/',
					'/%year%/%monthnum%/%postname%/',
					'/%postname%/'
				] ) ? 'error' : 'success',
			'Réglage des permaliens',
			'Le réglage des permaliens doit suivre une des valeurs suivantes : Date et titre, Mois et titre ou Titre de la publication',
			admin_url( 'options-permalink.php' )
		);
	}

	$redir_test_url       = site_url( 'shouldredirect' );
	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Test de fonctionnement des redirections WordPress',
		'Cliquez sur le lien suivant : <a target="_blank" href="' . $redir_test_url . '">' . $redir_test_url . '</a>.<br/>Si vous voyez un message indiquant "Redirection réussie", tout va bien. Sinon vérifiez que le mod_rewrite est actif et que les htaccess ne sont désactivés.',
		''
	);

	$htaccess_test_url    = wp_upload_dir()['baseurl'] . '/amapress-contrats/';
	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Test de fonctionnement de protection de dossier',
		'Cliquez sur le lien suivant : <a target="_blank" href="' . $htaccess_test_url . '">' . $htaccess_test_url . '</a>.<br/>Si vous voyez un message indiquant "Accès interdit", tout va bien. Sinon vérifiez que les htaccess ne sont désactivés.',
		''
	);

	$admin_email          = get_bloginfo( 'admin_email' );
	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Adresse email de l\'administrateur',
		'L\'adresse email de l\'administrateur du site est actuellement : <strong>' . esc_html( $admin_email ) . '</strong>. L\'administrateur reçoit des emails sur l\'activité sur le site comme le changement de mot de passe)',
		admin_url( 'options-general.php' )
	);

	$blog_desc            = get_bloginfo( 'description' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $blog_desc ) ? 'warning' : 'success',
		'Description de l\'AMAP',
		'Cette section permet le référencement dans les moteurs de recherche.
<br/>Remplir les champs <strong>Titre</strong> (Le nom de votre AMAP) et <strong>Slogan</strong> (Un sous titre pour votre AMAP. Vous pouvez ajouter la mention suivante "Construit avec Amapress, l\'outil pour les AMAP")',
		admin_url( 'customize.php?autofocus[section]=title_tagline' )
	);
	$site_icon            = get_option( 'site_icon' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $site_icon ) ? 'warning' : 'success',
		'Icône de l\'AMAP',
		'Ajouter une icône pour personnaliser l\'entête du navigateur et les signets/favoris.',
		admin_url( 'customize.php?autofocus[section]=title_tagline' )
	);
	$state['05_config'][] = amapress_get_check_state(
		! Amapress::userCanRegister() ? 'success' : ( 'active' != amapress_is_plugin_active( 'new-user-approve' ) ? 'error' : 'warning' ),
		'Création de compte sur le site : ' . ( Amapress::userCanRegister() ? 'activée' : 'désactivée' ),
		'<strong>Non recommandé</strong> : Cette option permet aux nouveaux visiteurs de créer un compte utilisateur en direct. Sans cette option, seuls les responsables pourront créer des comptes utilisateurs. ',
		admin_url( 'options-general.php#users_can_register' )
	);
//    $blog_desc = get_theme_mod('custom_logo');
//    $state['05_config'][] = amapress_get_check_state(
//        empty($blog_desc) ? 'warning' : 'success',
//        'Icone de l\'AMAP',
//        'Ajouter une icone pour l\'AMAP personnalise l\'entête du navigateur et les signets',
//        admin_url('customize.php?autofocus[section]=title_tagline')
//    );

	$static_front_id      = get_option( 'page_on_front' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $static_front_id ) ? 'error' : 'success',
		'Page d\'accueil statique',
		'Vérifier que votre thème est configuré avec l’option « page d\'accueil statique ».<br/>Sélectionner votre page d’accueil existante, ou configurer une nouvelle page.',
		admin_url( 'customize.php?autofocus[section]=static_front_page' )
	);
	$front_page_content   = null;
	$front_page_logo      = null;
	if ( ! empty( $static_front_id ) ) {
		$page = get_post( $static_front_id );
		if ( $page ) {
			$front_page_content = $page->post_content;
			$front_page_logo    = get_post_thumbnail_id( $page->ID );
		}
	}
	$state['05_config'][] = amapress_get_check_state(
		empty( $front_page_content ) ? 'warning' : 'success',
		'Contenu à la page d\'accueil',
		'Ajouter le texte de présentation de votre Amap',
		admin_url( 'post.php?post=' . $static_front_id . '&action=edit' )
	);

	$static_blog_id       = get_option( 'page_for_posts' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $static_blog_id ) ? 'error' : 'success',
		'Page de blog/articles statique',
		'Vérifier que votre thème est configuré avec l’option « page d\'accueil statique ».<br/>Sélectionner votre page de blog/articles existante, ou configurer une nouvelle page.',
		admin_url( 'customize.php?autofocus[section]=static_front_page' )
	);

	$contact_page         = Amapress::getContactInfos();
	$state['05_config'][] = amapress_get_check_state(
		empty( $contact_page ) || strpos( $contact_page, '[[' ) !== false ? 'warning' : 'success',
		'Contenu de la page de contact',
		'Ajouter les informations nécessaires pour contacter l’Amap pour une nouvelle inscription.',
		admin_url( 'admin.php?page=amapress_contact_options_page' )
	);
	$state['05_config'][] = amapress_get_check_state(
		empty( $front_page_logo ) ? 'warning' : 'success',
		'Logo de la page d\'accueil',
		'Ajouter votre logo sur la page d\'accueil',
		admin_url( 'post.php?post=' . $static_front_id . '&action=edit' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Configuration de la liste d\'émargement',
		'Personnaliser les infos affichées (téléphones, mails, instructions...) sur la liste d\'émargement et sa taille d\'impression.',
		admin_url( 'admin.php?page=amapress_emargement_options_page' )
	);

//    $contrat_anon = Amapress::getOption('contrat_info_anonymous');
//    $state['05_config'][] = amapress_get_check_state(
//        empty($contrat_anon) ? 'warning' : 'success',
//        'Information sur les contrats',
//        empty($contrat_anon) ?
//            'Ajouter le texte d\'information sur les contrats' :
//            'Cliquer sur le lien ci-dessus pour éditer le texte d\'information sur les contrats',
//        admin_url('admin.php?page=amapress_options_page&tab=contrats')
//    );

//    $menu_name = 'primary';
//    $locations = get_nav_menu_locations();

//    $state['05_config'][] = amapress_get_check_state(
//        empty($main_menu) || count($main_menu) == 0 ? 'error' : 'success',
//        'Menu principal du site',
//        empty($main_menu) || count($main_menu) == 0 ?
//            'Remplir le menu principal du site' :
//            'Cliquer sur le lien ci-dessus pour éditer le menu',
//        admin_url('customize.php?autofocus[panel]=nav_menus')
//    );
	$info_page_menu_item_found = false;
	$blog_page_menu_item_found = false;
	$info_page_id              = Amapress::getOption( 'mes-infos-page' );
	foreach ( get_nav_menu_locations() as $menu_name => $menu_id ) {
		$menus = wp_get_nav_menu_items( $menu_id );
		if ( ! empty( $menus ) ) {
			foreach ( $menus as $menu_item ) {
				if ( $menu_item->object_id == $info_page_id ) {
					$info_page_menu_item_found = true;
				} else if ( $menu_item->object_id == $static_blog_id ) {
					$blog_page_menu_item_found = true;
				}
			}
		}
	}
	$state['05_config'][] = amapress_get_check_state(
		! $blog_page_menu_item_found ? 'warning' : 'success',
		'Entrée de menu - Page de blog',
		'Créer une entrée dans le menu principal vers la page « Blog/Articles » (menu permettant l\'accès aux articles publiés sur le site).',
		admin_url( 'nav-menus.php' )
	);

	$state['05_config'][] = amapress_get_check_state(
		! $info_page_menu_item_found ? 'error' : 'success',
		'Entrée de menu - Mes Infos',
		'<strong>Important</strong> : Créer obligatoirement une entrée dans le menu principal vers la page « Mes Infos » (menu permettant la connexion).',
		admin_url( 'nav-menus.php' )
	);

//    $state['05_config'][] = amapress_get_check_state(
//        empty($front_page_logo) ? 'warning' : 'success',
//        'Logo de la page d\'accueil',
//        empty($front_page_logo) ?
//            'Ajouter un logo à la page d\'accueil' :
//            'Cliquer sur le lien ci-dessus pour éditer la page d\'accueil et son logo',
//        admin_url('post.php?post=' . $static_front_id . '&action=edit')
//    );

	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Choix de la géolocalisation (actuellement ' . Amapress::getOption( 'geocode_provider' ) . ') et de l\'affichage des cartes (actuellement ' . Amapress::getOption( 'map_provider' ) . ')',
		'Vous pouvez choisir entre Nominatim/Open Street Map et Google Maps pour la géolocalisation et l\'affichage des cartes',
		admin_url( 'admin.php?page=amapress_options_page&tab=amp_google_api_config' )
	);

	if ( 'google' == Amapress::getOption( 'geocode_provider' ) || 'google' == Amapress::getOption( 'map_provider' ) ) {
		$google_key           = Amapress::getOption( 'google_map_key' );
		$state['05_config'][] = amapress_get_check_state(
			! empty( $google_key ) ? 'success' : 'error',
			'Clé API Google',
			'<strong>Requis</strong> : Une clé Google API est nécessaire pour le bon fonctionnement de la géolocalisation ',
			admin_url( 'admin.php?page=amapress_options_page&tab=amp_google_api_config' )
		);
	}

	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Adresse mail du site',
		'Configurer l\'adresse email du site (par défaut, "wordpress", actuellement "<strong>' . esc_html( Amapress::getOption( 'email_from_mail' ) ) . '</strong>") et son nom d\'affichage (par défaut, le nom du site). Pensez à configurer une redirection pour cette adresse dans la configuration de votre hébergement.',
		admin_url( 'admin.php?page=amapress_options_page&tab=amp_site_mail_config' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Message sur la page de connexion',
		'Personnaliser le message qui s\'affiche sur la page de connexion, par exemple, pour rappeler la procédure de récupération de son mot de passe.',
		admin_url( 'admin.php?page=amapress_options_page&tab=amp_connection_config#amapress_below_login_message' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Email de bienvenue/demande de récupération mot de passe',
		'Ajoutez et personnalisez le mail de bienvenue que chaque amapien reçoit à la création de son compte ou lorsqu\'il demande à récupérer son mot de passe',
		admin_url( 'admin.php?page=amapress_options_page&tab=welcome_mail' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		'Configuration des mailing lists',
		'<p>Si vous avez un accès au système de mailing list (Sympa), par ex Ouvaton, Sud Ouest ou autre fournisseur, 
configurer le mot de passe du listmaster et le domaine de liste <a href="' . admin_url( 'admin.php?page=amapress_mailinglist_options_page' ) . '">ici</a>.</p>
<p>Créez vos listes depuis l\'interface de Sympa chez votre fournisseur, puis <a href="' . admin_url( 'edit.php?post_type=amps_mailing' ) . '">configurer les membres et modérateurs pour chaque liste</a></p>',
		admin_url( 'edit.php?post_type=amps_mailing' )
	);

	$use_mail_queue = Amapress::getOption( 'mail_queue_use_queue' );
	$nb_mails       = '"pas de limite" (file désactivée)';
	if ( $use_mail_queue ) {
		$mail_interval = Amapress::getOption( 'mail_queue_interval' );
		if ( empty( $mail_interval ) ) {
			$mail_interval = 30;
		}
		$mail_limite = Amapress::getOption( 'mail_queue_limit' );
		$mails_hours = $mail_limite / (float) $mail_interval * 3600;
		$nb_mails    = "$mails_hours (max {$mail_limite} emails toute les {$mail_interval}s)";
	}
	$state['05_config'][] = amapress_get_check_state(
		$use_mail_queue ? 'success' : 'warning',
		'Configuration de la file d\'envoi des emails sortants',
		'<p>La plupart des hébergeurs ont une limite d\'envoi des emails sortants par heure. Actuellement le site est configuré pour envoyer au maximum ' . $nb_mails . ' emails par heure.
<br/>Par défaut, Amapress met les mails dans une file d\'attente avant de les envoyer pour éviter les blocages et rejets de l\'hébergeur. 
<br />Un autre bénéfice est le réessaie d\'envoi en cas d\'erreur temporaire et le logs des emails envoyés par le site pour traçage des activités (pour une durée configurable).</p>',
		admin_url( 'admin.php?page=amapress_mailqueue_options_page&tab=amapress_mailqueue_options' )
	);

	$state['10_users'] = array();

	$users               = get_users( array( 'role' => 'responsable_amap' ) );
	$state['10_users'][] = amapress_get_check_state(
		count( $users ) == 0 ? 'error' : 'success',
		'Compte Responsable AMAP',
		'Créer les comptes des Responsables de l\'AMAP',
		admin_url( 'user-new.php?role=responsable_amap' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressUser::getBy( $u );
			$l  = admin_url( 'user-edit.php?user_id=' . $dn->getID() . '&wp_http_referer=%2Fwp-admin%2Fusers.php' );

			return "<a href='{$l}' target='_blank'>{$dn->getDisplayName()}</a>";
		}, $users ) )
	);
	$prod_users          = get_users( array( 'role' => 'producteur' ) );
	$state['10_users'][] = amapress_get_check_state(
		count( $prod_users ) == 0 ? 'error' : 'success',
		'Compte Producteur',
		'Créer les comptes des producteurs',
		admin_url( 'user-new.php?role=producteur' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressUser::getBy( $u );
			$l  = admin_url( 'user-edit.php?user_id=' . $dn->getID() . '&wp_http_referer=%2Fwp-admin%2Fusers.php' );

			return "<a href='{$l}' target='_blank'>{$dn->getDisplayName()}</a>";
		}, $prod_users ) )
	);
	$users               = get_users( 'amapress_role=referent_producteur' );
	$state['10_users'][] = amapress_get_check_state(
		count( $users ) == 0 ? 'error' : 'success',
		'Compte Référent Producteur',
		'Créer les comptes des Référents Producteurs',
		admin_url( 'user-new.php?role=referent' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressUser::getBy( $u );
			$l  = admin_url( 'user-edit.php?user_id=' . $dn->getID() . '&wp_http_referer=%2Fwp-admin%2Fusers.php' );

			return "<a href='{$l}' target='_blank'>{$dn->getDisplayName()}</a>";
		}, $users ) )
	);

	$amap_roles          = amapress_get_amap_roles();
	$state['10_users'][] = amapress_get_check_state(
		count( $amap_roles ) == 0 ? 'warning' : 'success',
		'Rôle descriptif des membres du collectif',
		'Créer et <a href="' . admin_url( 'users.php?amapress_role=collectif' ) . '" target=\'_blank\'>associer des rôles descriptifs aux utilisateurs</a> (par ex "Responsable des distribution", "Boîte contact", "Accueil des nouveaux")',
		admin_url( 'edit-tags.php?taxonomy=amps_amap_role_category' ),
		implode( ', ', array_map( function ( $t ) {
			/** @var WP_Term $t */
			$l = admin_url( 'users.php?amps_amap_role_category=' . $t->slug );

			return "<a href='{$l}' target='_blank'>{$t->name}</a>";
		}, $amap_roles ) )
	);
	$empty_resp_roles    = false;
	foreach (
		[
			'resp-distrib-amap-role',
			'resp-visite-amap-role',
			'resp-intermittents-amap-role',
			'resp-amap_event-amap-role'
		] as $option
	) {
		if ( empty( Amapress::getOption( $option ) ) ) {
			$empty_resp_roles = true;
		}
	}
	$state['10_users'][] = amapress_get_check_state(
		count( $amap_roles ) == 0 ? 'warning' : 'success',
		'Rôle descriptif spécifiques des membres du collectif',
		'<a href="' . admin_url( 'admin.php?page=amapress_collectif&tab=amp_amap_roles_config' ) . '" target="_blank">Associer des rôles descriptifs spécifiques</a> aux responsables de la gestion des distributions, des visites/sorties, des intermittents ou des évènements',
		admin_url( 'admin.php?page=amapress_collectif&tab=amp_amap_roles_config' )
	);

	/** @var WP_User[] $users_no_desc */
	$users_no_desc   = get_users( [
		'amapress_role' => 'collectif_no_amap_role',
	] );
	$members_no_desc = array_map( function ( $user ) {
		$amapien = AmapressUser::getBy( $user );

		return AMapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName() . ' (' . $amapien->getEmail() . ')[' . $amapien->getAmapRolesString() . ']', true, true );
	}, $users_no_desc );
	if ( ! empty( $members_no_desc ) ) {
		$only_admins = true;
		foreach ( $users_no_desc as $user ) {
			if ( ! in_array( 'administrator', $user->roles ) ) {
				$only_admins = false;
			}
		}
		$state['10_users'][] = amapress_get_check_state(
			$only_admins ? 'success' : 'warning',
			'Membres du collectif sans rôle descriptif',
			'<a target="_blank" href="' . admin_url( 'admin.php?page=amapress_collectif' ) . '">Associer</a> des rôles descriptifs aux utilisateurs ayant accès au backoffice. (<em>Les administrateurs n\'ont pas forcement besoin de rôle descriptif</em>)',
			admin_url( 'admin.php?page=amapress_collectif' ),
			implode( ', ', $members_no_desc )
		);
	}

	$members_no_contrats = array_map( function ( $user ) {
		$amapien = AmapressUser::getBy( $user );

		return AMapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName() . ' (' . $amapien->getEmail() . ')', true, true );
	}, get_users( [
		'amapress_role'    => 'collectif_no_prod',
		'amapress_contrat' => 'no',
	] ) );
	if ( ! empty( $members_no_contrats ) ) {
		$state['10_users'][] = amapress_get_check_state(
			'info',
			'Membres du collectif sans contrat',
			'<a target="_blank" href="' . admin_url( 'admin.php?page=amapress_collectif' ) . '">Vérifier</a> les utilisateurs membres du collectif qui n\'ont pas de contrats',
			admin_url( 'users.php?amapress_contrat=no&amapress_role=collectif_no_prod' ),
			implode( ', ', $members_no_contrats )
		);
	}

	$state['15_posts'] = array();

	$lieux               = Amapress::get_lieux();
	$not_localized_lieux = array_filter( $lieux,
		function ( $lieu ) {
			/** @var AmapressLieu_distribution $lieu */
			return ! $lieu->isAdresseLocalized();
		} );
	$state['15_posts'][] = amapress_get_check_state(
		count( $lieux ) == 0 ? 'error' : ( ! empty( $not_localized_lieux ) ? 'warning' : 'success' ),
		'Lieu de distribution',
		'Créer au moins un lieu de distribution',
		admin_url( 'post-new.php?post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressLieu_distribution $dn */
			$l = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}' target='_blank'>{$dn->getTitle()}</a>";
		}, $lieux ) ) .
		( ! empty( $not_localized_lieux ) ? '<br /><strong>Lieux non localisés :</strong> ' . implode( ', ', array_map( function ( $dn ) {
					/** @var AmapressLieu_distribution $dn */
					$l = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

					return "<a href='{$l}' target='_blank'>{$dn->getTitle()}</a>";
				}, $not_localized_lieux )
			) : '' )
	);

	$subscribable_contrat_instances = AmapressContrats::get_subscribable_contrat_instances();
	$online_contrats                = array_filter( $subscribable_contrat_instances, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->canSelfSubscribe();
	} );
	$not_online_contrats            = array_filter( AmapressContrats::get_active_contrat_instances(), function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return ! $c->canSelfSubscribe();
	} );
	$first_online_date              = 0;
	foreach ( $online_contrats as $online_contrat ) {
		if ( $online_contrat->getDate_debut() > $first_online_date ) {
			$first_online_date = $online_contrat->getDate_debut();
			break;
		}
	}
	if ( empty( $first_online_date ) ) {
		$first_online_date = amapress_time();
	}

	$adh_period          = AmapressAdhesionPeriod::getCurrent();
	$state['15_posts'][] = amapress_get_check_state(
		empty( $adh_period ) ? 'error' : 'success',
		'Période d\'adhésion',
		'Créer une période d\'adhésion pour les cotisations de l\'année en cours',
		admin_url( 'post-new.php?post_type=' . AmapressAdhesionPeriod::INTERNAL_POST_TYPE ),
		( ! empty( $adh_period ) ? '<a href="' . esc_attr( $adh_period->getAdminEditLink() ) . '" target=\'_blank\'>' . esc_html( $adh_period->getTitle() ) . '</a>' : 'Aucune période d\'adhésion' )
	);

	$adh_period2 = AmapressAdhesionPeriod::getCurrent( $first_online_date );
	if ( ! $adh_period || ! $adh_period2 || $adh_period2->ID != $adh_period->ID ) {
		$state['15_posts'][] = amapress_get_check_state(
			empty( $adh_period2 ) ? 'error' : 'success',
			'Période d\'adhésion',
			'Créer une période d\'adhésion pour les cotisations du début des contrats en ligne',
			admin_url( 'post-new.php?post_type=' . AmapressAdhesionPeriod::INTERNAL_POST_TYPE ),
			( ! empty( $adh_period2 ) ? '<a href="' . esc_attr( $adh_period2->getAdminEditLink() ) . '" target=\'_blank\'>' . esc_html( $adh_period2->getTitle() ) . '</a>' : 'Aucune période d\'adhésion' )
		);
	}

	$adhesion_categs     = get_categories( array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'taxonomy'   => 'amps_paiement_category',
		'hide_empty' => false,
	) );
	$state['15_posts'][] = amapress_get_check_state(
		count( $adhesion_categs ) == 0 ? 'warning' : 'success',
		'Types de paiement des cotisations',
		'Créer des <a href="' . admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ) . '" target=\'_blank\'>types de paiement pour le bulletin d\'adhésion à l\'AMAP</a> (par ex "Don à l\'AMAP", "Panier solidaire").',
		admin_url( 'edit-tags.php?taxonomy=amps_amap_role_category' ),
		implode( ', ', array_map( function ( $t ) {
			/** @var WP_Term $t */
			$l = admin_url( 'term.php?taxonomy=amps_paiement_category&tag_ID=' . $t->term_id );

			return "<a href='{$l}' target='_blank'>{$t->name}</a>";
		}, $adhesion_categs ) )
	);

	$all_producteurs = get_posts( array(
		'post_type'      => AmapressProducteur::INTERNAL_POST_TYPE,
		'post_status'    => [ 'publish', 'archived' ],
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'amapress_producteur_user',
				'value'   => amapress_prepare_in( array_map( 'Amapress::to_id', $prod_users ) ),
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			)
		)
	) );
	$producteurs     = get_posts( array(
		'post_type'      => AmapressProducteur::INTERNAL_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'amapress_producteur_user',
				'value'   => amapress_prepare_in( array_map( 'Amapress::to_id', $prod_users ) ),
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			)
		)
	) );
	//TODO better check prod vs user
	$state['15_posts'][] = amapress_get_check_state(
		count( $prod_users ) == 0 ? 'error' : ( count( $all_producteurs ) < count( $prod_users ) ? 'warning' : 'success' ),
		'Producteurs',
		'Créer les Producteur correspondant à leur compte utilisateur',
		admin_url( 'post-new.php?post_type=' . AmapressProducteur::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressProducteur::getBy( $u );
			$l  = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}' target='_blank'>{$dn->getTitle()}</a>";
		}, $producteurs ) )
	);

	$prod_no_referent    = array_filter( $producteurs,
		function ( $u ) {
			$dn = AmapressProducteur::getBy( $u );

			return from( $dn->getContrats() )->any( function ( $contrat ) {
				/** @var AmapressContrat $contrat */
				return empty( $contrat->getAllReferentsIds() );
			} );
		} );
	$state['15_posts'][] = amapress_get_check_state(
		! empty( $prod_no_referent ) ? 'error' : 'success',
		'Référents Producteurs',
		'Associer le(s) référent(s) producteur pour chacun des producteurs ou productions',
		admin_url( 'edit.php?post_type=amps_producteur' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressProducteur::getBy( $u );
			$l  = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			$refs = [];
			foreach ( $dn->getContrats() as $contrat ) {
				foreach ( Amapress::get_lieux() as $lieu ) {
					foreach ( $contrat->getReferentsIds( $lieu->ID ) as $referents_id ) {
						$user   = AmapressUser::getBy( $referents_id );
						$refs[] = esc_html( sprintf( count( $dn->getContrats() ) == 1 ? '%1$s (%3$s)' : '%1$s (%2$s/%3$s)', $user->getDisplayName(), $contrat->getTitle(), $lieu->getShortName() ) );
					}
				}
			}
			$refs = array_unique( $refs );
			if ( empty( $refs ) ) {
				$refs[] = '<strong>Pas de référent</strong>';
			}

			$refs = '(' . implode( ', ', $refs ) . ')';

			return "<a href='{$l}' target='_blank'>{$dn->getTitle()}</a>$refs";
		}, $producteurs ) )
	);

	$contrat_types                  = get_posts( array(
		'post_type'      => AmapressContrat::INTERNAL_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'amapress_contrat_producteur',
				'value'   => amapress_prepare_in( array_map( 'Amapress::to_id', $producteurs ) ),
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			)
		)
	) );
	$not_subscribable_contrat_types = array_filter(
		$producteurs,
		function ( $p ) use ( $contrat_types ) {
			/** @var AmapressProducteur $p */
			foreach ( $contrat_types as $contrat ) {
				$contrat_type = AmapressContrat::getBy( $contrat );
				if ( $contrat_type->getProducteurId() == $p->ID ) {
					return false;
				}
			}

			return true;
		}
	);
	$state['15_posts'][]            = amapress_get_check_state(
		count( $contrat_types ) == 0 ? 'error' : ( ! empty( $not_subscribable_contrat_types ) ? 'warning' : 'success' ),
		'Présentation des productions',
		'Créer au moins une production par producteur pour présenter son/ses offre(s)',
		admin_url( 'post-new.php?post_type=' . AmapressContrat::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressContrat::getBy( $u );
			$l  = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}' target='_blank'>{$dn->getTitle()}</a>";
		}, $contrat_types ) ) .
		( ! empty( $not_subscribable_contrat_types ) ? '<p><strong>Les producteurs suivants n\'ont pas de production</strong> : ' .
		                                               implode( ', ', array_map( function ( $dn ) {

			                                               $l = admin_url( 'post.php?post=' . $dn->ID . '&action=edit' );
			                                               $t = esc_html( $dn->post_title );

			                                               return "<a href='{$l}' target='_blank'>{$t}</a>";
		                                               }, $not_subscribable_contrat_types ) ) . '</p>' : '' )
	);

	$contrat_types                      = get_posts( array(
		'post_type'      => AmapressContrat::INTERNAL_POST_TYPE,
		'post_status'    => [ 'publish' ],
		'posts_per_page' => - 1,
		'meta_query'     => array(
			array(
				'key'     => 'amapress_contrat_producteur',
				'value'   => amapress_prepare_in( array_map( 'Amapress::to_id', $producteurs ) ),
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			)
		)
	) );
	$active_contrat_instances           = AmapressContrats::get_active_contrat_instances();
	$not_subscribable_contrat_instances = array_filter(
		$contrat_types,
		function ( $c ) use ( $subscribable_contrat_instances ) {
			/** @var AmapressContrat $c */
			foreach ( $subscribable_contrat_instances as $contrat_instance ) {
				if ( $contrat_instance->getModelId() == $c->ID ) {
					return false;
				}
			}

			return true;
		}
	);
	$not_active_contrat_instances       = array_filter(
		$contrat_types,
		function ( $c ) use ( $active_contrat_instances ) {
			/** @var AmapressContrat $c */
			foreach ( $active_contrat_instances as $contrat_instance ) {
				if ( $contrat_instance->getModelId() == $c->ID ) {
					return false;
				}
			}

			return true;
		}
	);
	$state['15_posts'][]                = amapress_get_check_state(
		count( $subscribable_contrat_instances ) == 0 ? 'error' : ( count( $subscribable_contrat_instances ) < count( $contrat_types ) ? 'warning' : 'success' ),
		'Modèles de contrats',
		'Créer au moins un modèle de contrat par contrat pour permettre aux amapiens d\'adhérer',
		admin_url( 'post-new.php?post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressContrat_instance $dn */
			$l      = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
			$tit    = esc_html( $dn->getTitle() );
			$status = '(' . AmapressContrats::contratStatus( $dn->getID(), 'span' ) . ')';

			return "<a href='{$l}' target='_blank'>{$tit}</a> {$status}";
		}, $subscribable_contrat_instances ) ) .
		( ! empty( $not_subscribable_contrat_instances ) ? '<p><strong>Les contrats suivants n\'ont pas de modèles actifs (selon date ouverture/bloture)</strong> : ' .
		                                                   implode( ', ', array_map( function ( $dn ) {

			                                                   $l = admin_url( 'post.php?post=' . $dn->ID . '&action=edit' );
			                                                   $t = esc_html( $dn->post_title );

			                                                   return "<a href='{$l}' target='_blank'>{$t}</a>";
		                                                   }, $not_subscribable_contrat_instances ) ) . '</p>' : '' ) .
		( ! empty( $not_active_contrat_instances ) ? '<p><strong>Les contrats suivants n\'ont pas de modèles en cours (selon les dates début/fin)</strong> : ' .
		                                             implode( ', ', array_map( function ( $dn ) {

			                                             $l = admin_url( 'post.php?post=' . $dn->ID . '&action=edit' );
			                                             $t = esc_html( $dn->post_title );

			                                             return "<a href='{$l}' target='_blank'>{$t}</a>";
		                                             }, $not_active_contrat_instances ) ) . '</p>' : '' )
	);

	$contrat_to_renew = get_posts( 'post_type=amps_contrat_inst&amapress_date=renew' );
	if ( ! empty( $contrat_to_renew ) ) {
		$state['15_posts'][] = amapress_get_check_state(
			'error',
			'Contrats à renouveler/clôturer',
			'Les contrats suivants sont à renouveler/clôturer pour la saison suivante',
			admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=renew' ),
			implode( ', ', array_map( function ( $dn ) {
				/** @var WP_Post $dn */
				$l = admin_url( 'post.php?post=' . $dn->ID . '&action=edit' );
				$t = esc_html( $dn->post_title );

				return "<a href='{$l}' target='_blank'>{$t}</a>";
			}, $contrat_to_renew ) )
		);
	}

	foreach (
		get_posts(
			[
				'post_type'      => AmapressProducteur::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
			]
		) as $post
	) {
		$prod = AmapressProducteur::getBy( $post );
		if ( empty( $prod->getUser() ) ) {
			$state['15_posts'][] = amapress_get_check_state(
				'error',
				'Producteur invalide',
				'Le producteur ' . $prod->getTitle() . ' n\'est pas associé à un utilisateur.',
				$prod->getAdminEditLink()
			);
		}
	}
	foreach (
		get_posts(
			[
				'post_type'      => AmapressContrat::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
			]
		) as $post
	) {
		$contrat = AmapressContrat::getBy( $post );
		if ( empty( $contrat->getProducteur() ) ) {
			$state['15_posts'][] = amapress_get_check_state(
				'error',
				'Production invalide',
				'La production ' . $contrat->getTitle() . ' n\'est pas associée à un producteur.',
				$contrat->getAdminEditLink()
			);
		}
	}
	foreach (
		get_posts(
			[
				'post_type'      => AmapressContrat_instance::INTERNAL_POST_TYPE,
				'posts_per_page' => - 1,
			]
		) as $post
	) {
		$contrat_instance = AmapressContrat_instance::getBy( $post );
		if ( empty( $contrat_instance->getModel() ) ) {
			$state['15_posts'][] = amapress_get_check_state(
				'error',
				'Modèle de contrat invalide',
				'Le modèle de contrat ' . $contrat_instance->getTitle() . ' n\'est pas associé à une production.',
				$contrat_instance->getAdminEditLink()
			);
		}
	}

	$all_producteurs           = array_map( function ( $p ) {
		return AmapressProducteur::getBy( $p );
	}, $all_producteurs );
	$not_localized_producteurs = array_filter( $all_producteurs, function ( $p ) {
		/** @var AmapressProducteur $p */
		return ! $p->isAdresseExploitationLocalized();
	} );
	if ( ! empty( $not_localized_producteurs ) ) {
		$state['15_posts'][] = amapress_get_check_state(
			'warning',
			'Producteurs non localisés',
			'Les producteurs suivants ne sont pas localisés',
			'',
			implode( ', ', array_map( function ( $p ) {
				/** @var AmapressProducteur $p */
				$l = admin_url( 'post.php?post=' . $p->ID . '&action=edit' );
				$t = esc_html( $p->getTitle() );

				return "<a href='{$l}' target='_blank'>{$t}</a>";
			}, $not_localized_producteurs ) )
		);
	}

	if ( ! empty( $not_localized_lieux ) ) {
		$state['15_posts'][] = amapress_get_check_state(
			'error',
			'Lieux de distribution non localisés',
			'Les lieux de distribution suivants ne sont pas localisés',
			'',
			implode( ', ', array_map( function ( $lieu ) {
				/** @var AmapressLieu_distribution $lieu */
				$l = admin_url( 'post.php?post=' . $lieu->ID . '&action=edit' );
				$t = esc_html( $lieu->getTitle() );

				return "<a href='{$l}' target='_blank'>{$t}</a>";
			}, $not_localized_lieux ) )
		);
	}

	$not_localized_amapiens_count = get_users_count( 'amapress_info=address_unk&amapress_contrat=active' );
	if ( $not_localized_amapiens_count > 0 ) {
		$state['15_posts'][] = amapress_get_check_state(
			'info',
			'Amapiens non localisés',
			"$not_localized_amapiens_count amapien(s) ne sont pas localisés",
			admin_url( 'users.php?amapress_info=address_unk&amapress_contrat=active' )
		);
	}

	$all_pages_and_presentations = get_pages( [
		'post_status' => 'publish'
	] );
	$all_pages_and_presentations = array_merge( $all_pages_and_presentations, get_posts( [
		'post_status' => 'publish',
		'post_type'   => [
			AmapressProducteur::INTERNAL_POST_TYPE,
			AmapressContrat::INTERNAL_POST_TYPE,
			AmapressLieu_distribution::INTERNAL_POST_TYPE,
		]
	] ) );

	$state['20_content']   = array();
	$state['20_content'][] = amapress_get_check_state(
		'info',
		'Pages particulières',
		'Configuration des pages particulières (Mes infos, espace intermittents...)',
		admin_url( 'admin.php?page=amapress_options_page&tab=amp_pages_config' )
	);
	foreach ( AmapressEntities::getMenu() as $item ) {
		if ( isset( $item['type'] ) && $item['type'] == 'panel' && isset( $item['id'] ) ) {
			$page_name = isset( $item['settings']['name'] ) ? $item['settings']['name'] . ' - ' : '';
			$page_id   = $item['id'];
			if ( ! empty( $item['tabs'] ) ) {
				foreach ( $item['tabs'] as $tab_id => $tab ) {
					$tab_name = ( isset( $tab['name'] ) ? $tab['name'] : $tab_id ) . ' - ';
					if ( isset( $tab['id'] ) ) {
						$tab_id = $tab['id'];
					}
					if ( ! empty( $tab['options'] ) ) {
						foreach ( $tab['options'] as $option ) {
							if ( empty( $option['id'] ) ) {
								continue;
							}
							if ( empty( $option['name'] ) ) {
								continue;
							}

							$val = Amapress::getOption( $option['id'] );
							if ( ! is_string( $val ) ) {
								continue;
							}

							if ( preg_match( '/\[\[[^\]]+\]\]/', $val ) ) {
								$tab_href = add_query_arg( [
										'page' => $page_id,
										'tab'  => $tab_id,
									], admin_url( 'admin.php' ) ) . '#' . $option['id'];

								$state['20_content'][] = amapress_get_check_state(
									'error',
									$page_name . $tab_name . $option['name'],
									'Information à compléter',
									$tab_href
								);
							}
						}
					}
				}
			}
			if ( ! empty( $item['options'] ) ) {
				foreach ( $item['options'] as $option ) {
					if ( empty( $option['id'] ) ) {
						continue;
					}
					if ( empty( $option['name'] ) ) {
						continue;
					}

					$val = Amapress::getOption( $option['id'] );
					if ( ! is_string( $val ) ) {
						continue;
					}

					if ( preg_match( '/\[\[[^\]]+\]\]/', $val ) ) {
						$tab_href = add_query_arg( [
								'page' => $page_id,
							], admin_url( 'admin.php' ) ) . '#' . $option['id'];

						$state['20_content'][] = amapress_get_check_state(
							'error',
							$page_name . $option['name'],
							'Information [[à compléter]]' . ( ! empty( $option['desc'] ) ? ' : ' . $option['desc'] : '' ),
							$tab_href
						);
					}
				}
			}
		}
	}
	foreach (
		$all_pages_and_presentations as $page
	) {
		//Blog page can be empty
		if ( $page->ID == $static_blog_id ) {
			continue;
		}
		/** @var WP_Post $page */
		if ( preg_match( '/\[\[[^\]]+\]\]/', $page->post_content ) ) {
			$state['20_content'][] = amapress_get_check_state(
				'error',
				$page->post_title,
				'Information [[à compléter]] sur la page ' . $page->post_title,
				admin_url( 'post.php?post=' . $page->ID . '&action=edit' )
			);
		}
		if ( empty( trim( strip_tags( $page->post_content ) ) ) ) {
			$state['20_content'][] = amapress_get_check_state(
				'warning',
				$page->post_title,
				'Compléter le contenu de la page ' . $page->post_title,
				admin_url( 'post.php?post=' . $page->ID . '&action=edit' )
			);
		}
		if ( 'page' != $page->post_type ) {
			$thumb_id = get_post_meta( $page->ID, '_thumbnail_id', true );
			if ( empty( $thumb_id ) ) {
				$state['20_content'][] = amapress_get_check_state(
					'warning',
					$page->post_title,
					'Ajouter un logo/image dans "L\'image à la une" de la page ' . $page->post_title,
					admin_url( 'post.php?post=' . $page->ID . '&action=edit' )
				);
			}
		}
	}

	$front_page_edit_href          = $static_front_id ? admin_url( 'post.php?post=' . $static_front_id . '&action=edit' ) : '';
	$amapien_mes_infos_edit_href   = admin_url( 'post.php?post=' . Amapress::getOption( 'mes-infos-page' ) . '&action=edit' );
	$amapien_mes_paniers_edit_href = admin_url( 'post.php?post=' . Amapress::getOption( 'mes-paniers-intermittents-page' ) . '&action=edit' );
	$amapien_les_paniers_edit_href = admin_url( 'post.php?post=' . Amapress::getOption( 'paniers-intermittents-page' ) . '&action=edit' );
	$new_page_href                 = admin_url( 'post-new.php?post_type=page' );
	$new_private_page_href         = admin_url( 'post-new.php?post_type=page&amps_lo=1' );
	$needed_shortcodes             = [
		'trombinoscope'                 => [
			'desc'  => 'Ajouter une page privée avec le shortcode %s pour afficher le trombinoscope des amapiens',
			'href'  => $new_private_page_href,
			'categ' => '3/ Info utiles',
		],
		'recettes'                      => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour afficher les recettes',
			'href'  => $new_page_href,
			'categ' => '1/ Site public',
		],
		'produits'                      => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour afficher les produits',
			'href'  => $new_page_href,
			'categ' => '1/ Site public',
		],
		'inscription-distrib'           => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour permettre aux amapiens de s\'inscrire comme responsable de distribution',
			'href'  => $new_private_page_href,
			'categ' => '4/ Gestion AMAP',
		],
		'echanger-paniers-list'         => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour permettre aux amapiens de proposer leurs paniers en cas d\'absence',
			'href'  => $new_private_page_href,
			'categ' => '5/ Espace intermittents',
		],
		'intermittents-inscription'     => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour permettre aux amapiens d\'inscrire des intermittents',
			'href'  => $new_private_page_href,
			'categ' => '5/ Espace intermittents',
		],
		'intermittents-desinscription'  => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour permettre aux intermittents de se désinscrire',
			'href'  => $new_private_page_href,
			'categ' => '5/ Espace intermittents',
		],
		'amapress-post-its'             => [
			'desc'  => 'Ajouter le shortcode %s à la page d\'Accueil pour afficher les post-its de gestion de l\'AMAP',
			'href'  => $front_page_edit_href,
			'categ' => '2/ Page Accueil - Infos utiles',
		],
		'amapien-edit-infos'            => [
			'desc'  => 'Ajouter le shortcode %s à la page "Mes infos" pour permettre aux amapiens d\'éditer leur profil',
			'href'  => $amapien_mes_infos_edit_href,
			'categ' => '4/ Profil amapien',
		],
		'mes-contrats'                  => [
			'desc'  => 'Ajouter le shortcode %s à une page "Mes contrats" pour permettre aux amapiens de voir leurs inscriptions, de télécharger leurs contrats Word ou de s\'inscrire à de nouveaux contrats en cours de saison',
			'href'  => $amapien_mes_infos_edit_href,
			'categ' => '4/ Profil amapien',
		],
		'amapien-paniers-intermittents' => [
			'desc'  => 'Ajouter le shortcode %s à la page Mes paniers échangés pour afficher "Les paniers que j\'ai proposé"',
			'href'  => $amapien_mes_paniers_edit_href,
			'categ' => '5/ Espace intermittents',
		],
		'les-paniers-intermittents'     => [
			'desc'  => 'Ajouter le shortcode %s à la page "Intermittent - Réserver un panier" pour permettre aux intermittents de réserver des paniers',
			'href'  => $amapien_les_paniers_edit_href,
			'categ' => '5/ Espace intermittents',
		],
		'intermittent-paniers'          => [
			'desc'  => 'Ajouter le shortcode %s à la page Mes paniers échangés pour afficher "Les paniers que j\'ai réservé"',
			'href'  => $amapien_mes_paniers_edit_href,
			'categ' => '5/ Espace intermittents',
		],
		'amapiens-map'                  => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour afficher la carte des amapiens',
			'href'  => $new_private_page_href,
			'categ' => '3/ Info utiles',
		],
		'amapiens-role-list'            => [
			'desc'  => 'Ajouter une page avec le shortcode %s pour afficher la liste des membres du collectif',
			'href'  => $new_private_page_href,
			'categ' => '3/ Info utiles',
		],
		'agenda-url'                    => [
			'desc'  => 'Ajouter le shortcode %s à la page Mes infos pour permettre aux amapiens d\'ajouter leur calendrier à leur agenda',
			'href'  => $amapien_mes_infos_edit_href,
			'categ' => '4/ Profil amapien',
		],
		'nous-contacter'                => [
			'desc'  => 'Ajouter une page Contact avec le shortcode %s',
			'href'  => $new_page_href,
			'categ' => '1/ Site public',
		],
		'front_next_events'             => [
			'desc'  => 'Ajouter le shortcode %s à la page d\'Accueil pour afficher le calendrier',
			'href'  => $front_page_edit_href,
			'categ' => '2/ Page Accueil - Infos utiles',
		],
		'front_produits'                => [
			'desc'  => 'Ajouter le shortcode %s à la page d\'Accueil pour afficher les contrats',
			'href'  => $front_page_edit_href,
			'categ' => '2/ Page Accueil - Infos utiles',
		],
		'front_nous_trouver'            => [
			'desc'  => 'Ajouter le shortcode %s à la page d\'Accueil pour afficher la carte des lieux de distribution',
			'href'  => $front_page_edit_href,
			'categ' => '2/ Page Accueil - Infos utiles',
		],
		'front_default_grid'            => [
			'desc'  => 'Ajouter le shortcode %s à la page d\'Accueil pour afficher le calendrier, les contrats et la carte des lieux de distribution',
			'href'  => $front_page_edit_href,
			'categ' => '2/ Page Accueil - Infos utiles',
		],
		'inscription-en-ligne'          => [
			'desc'  => 'Ajouter le shortcode %s sur une page pour permettre aux amapiens de s\'inscrire en ligne aux contrats',
			'href'  => $new_page_href,
			'categ' => '6/ Inscriptions en ligne',
		],
		'listes-diffusions'             => [
			'desc'  => 'Ajouter le shortcode %s sur une page protégée pour permettre aux amapiens ou au collectif de connaitre les listes de diffusions configurées de votre AMAP',
			'href'  => $new_page_href,
			'categ' => '3/ Info utiles',
		],
		'docspace-responsables'         => [
			'desc'  => 'Ajouter le shortcode %s sur une page protégée pour permettre au collectif de partager des fichiers entre les membres du collectif',
			'href'  => $new_page_href,
			'categ' => '7/ Stockage',
		],
		'docspace-amapiens'             => [
			'desc'  => 'Ajouter le shortcode %s sur une page protégée pour permettre au collectif de partager des fichiers avec les amapiens',
			'href'  => $new_page_href,
			'categ' => '7/ Stockage',
		],
		'docspace-public'               => [
			'desc'  => 'Ajouter le shortcode %s sur une page non protégée pour permettre au collectif de partager des fichiers publiquement',
			'href'  => $new_page_href,
			'categ' => '7/ Stockage',
		],
		'inscription-visite'            => [
			'desc'  => 'Ajouter le shortcode %s sur une page protégée pour permettre aux amapiens de s\'inscrires aux visites aux producteurs',
			'href'  => $new_page_href,
			'categ' => '7/ Stockage',
		],
	];
	$found_shortcodes              = [];
	$found_shortcodes_desc         = [];
	uasort( $needed_shortcodes, function ( $a, $b ) {
		return strcmp( $a['categ'], $b['categ'] );
	} );
	foreach (
		$all_pages_and_presentations as $page
	) {
		foreach ( $needed_shortcodes as $shortcode => $desc ) {
			/** @var WP_Post $page */
			if ( preg_match( '/\[' . $shortcode . '/', $page->post_content ) ) {
				$found_shortcodes[ $shortcode ]      = $page;
				$found_shortcodes_desc[ $shortcode ] = $needed_shortcodes[ $shortcode ];
				unset( $needed_shortcodes[ $shortcode ] );
			}
		}
	}
	if ( ! isset( $needed_shortcodes['front_default_grid'] ) ) {
		unset( $needed_shortcodes['front_next_events'] );
		unset( $needed_shortcodes['front_produits'] );
		unset( $needed_shortcodes['front_nous_trouver'] );
	}
	if ( ! isset( $needed_shortcodes['front_next_events'] )
	     || ! isset( $needed_shortcodes['front_produits'] )
	     || ! isset( $needed_shortcodes['front_nous_trouver'] ) ) {
		unset( $needed_shortcodes['front_default_grid'] );
	}

	$state['24_shortcodes'] = array();
	foreach ( $found_shortcodes as $shortcode => $page ) {
		$desc                     = $found_shortcodes_desc[ $shortcode ];
		$state['24_shortcodes'][] = amapress_get_check_state(
			'success',
			$desc['categ'] . ' : ' . $shortcode,
			sprintf( $desc['desc'], '[' . $shortcode . ']' ),
			admin_url( 'post.php?post=' . $page->ID . '&action=edit' )
		);
	}

	$state['25_shortcodes'] = array();
	foreach ( $needed_shortcodes as $shortcode => $desc ) {
		$state['25_shortcodes'][] = amapress_get_check_state(
			'do',
			$desc['categ'] . ' : ' . $shortcode,
			sprintf( $desc['desc'], '[' . $shortcode . ']' ),
			$desc['href']
		);
	}

	$state['26_online_inscr']   = array();
	$state['26_online_inscr'][] = amapress_get_check_state(
		count( $online_contrats ) == 0 ? 'warning' : 'success',
		'Modèles de contrats accessibles en ligne',
		'Activer l\'inscription en ligne pour au moins un contrat pour permettre aux amapiens d\'adhérer',
		admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ),
		'<strong>Contrats accessibles en ligne :</strong> ' . ( count( $online_contrats ) == 0 ? 'aucun' : implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressContrat_instance $dn */
			$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
			$tit = esc_html( $dn->getTitle() );

			return "<a href='{$l}' target='_blank'>{$tit}</a>";
		}, $online_contrats ) ) ) .
		( count( $not_online_contrats ) > 0 ? '<br /><strong>Contrats non accessibles en ligne :</strong> ' . implode( ', ', array_map( function ( $dn ) {
				/** @var AmapressContrat_instance $dn */
				$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
				$tit = esc_html( $dn->getTitle() );

				return "<a href='{$l}' target='_blank'>{$tit}</a>";
			}, $not_online_contrats ) ) : '' )
	);
	$with_word_contrats         = array_filter( $subscribable_contrat_instances, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->canSelfSubscribe() && $c->getContratWordModelId();
	} );
	$without_word_contrats      = array_filter( AmapressContrats::get_active_contrat_instances(), function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->canSelfSubscribe() && ! $c->getContratWordModelId();
	} );
	$state['26_online_inscr'][] = amapress_get_check_state(
		count( $without_word_contrats ) > 0 ? 'warning' : 'success',
		'Modèles de contrats avec contrat DOCX (Word) associé',
		'Préparer un contrat papier (DOCX) <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) . '">générique pour tous les contrats</a> ou par modèle de contrat pour permettre aux amapiens d\'imprimer et signer directement leur contrat lors de leur inscription en ligne. Un modèle générique est télchargeable <a target="_blank" href="' . esc_attr( Amapress::getContratGenericUrl() ) . '">ici</a>.',
		admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ),
		'<strong>Contrats avec Word attaché :</strong> ' . ( count( $online_contrats ) == 0 ? 'aucun' : implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressContrat_instance $dn */
			$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
			$tit = esc_html( $dn->getTitle() );

			return "<a href='{$l}' target='_blank'>{$tit}</a>";
		}, $with_word_contrats ) ) ) .
		( count( $without_word_contrats ) > 0 ? '<br /><strong>Contrats sans Word attaché :</strong> ' . implode( ', ', array_map( function ( $dn ) {
				/** @var AmapressContrat_instance $dn */
				$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
				$tit = esc_html( $dn->getTitle() );

				return "<a href='{$l}' target='_blank'>{$tit}</a>";
			}, $without_word_contrats ) ) : '' )
	);

	$convertws_url              = Amapress::getOption( 'convertws_url' );
	$convertws_user             = Amapress::getOption( 'convertws_user' );
	$convertws_pass             = Amapress::getOption( 'convertws_pass' );
	$state['26_online_inscr'][] = amapress_get_check_state(
		( empty( $convertws_url ) || empty( $convertws_user ) || empty( $convertws_pass ) ) ? 'warning' : 'success',
		'Configuration du webservice de conversion DOCX vers PDF (et autres services)',
		'Un webservice de conversion DOCX vers PDF est nécessaire afin que les amapiens recoivent leur contrat en PDF et non en DOCX.<br/>Vous pouvez faire une <a href="mailto:contact.amapress@gmail.com">demande de code d\'accès</a> au webservice mis en place par l\'équipe Amapress. Ce WebService pourra également fournir d\'autres services, tels que la réduction de poids de PDF.',
		admin_url( 'admin.php?page=amapress_options_page&tab=amp_convertws_config' )
	);

	$adh_period                 = AmapressAdhesionPeriod::getCurrent( $first_online_date );
	$state['26_online_inscr'][] = amapress_get_check_state(
		empty( $adh_period ) ? 'error' : ( ! $adh_period->getWordModelId() ? 'warning' : 'success' ),
		'Période d\'adhésion',
		'Créer une période d\'adhésion au ' . date_i18n( 'd/m/Y', $first_online_date ) . ' pour les adhésions en ligne et attaché lui un bulletin d\'adhésion en Word',
		$adh_period ? $adh_period->getAdminEditLink() : admin_url( 'post-new.php?post_type=' . AmapressAdhesionPeriod::INTERNAL_POST_TYPE ),
		( ! empty( $adh_period ) ? '<a href="' . esc_attr( $adh_period->getAdminEditLink() ) . '" target=\'_blank\'>' . esc_html( $adh_period->getTitle() ) . '</a>' : 'Aucune période d\'adhésion' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		isset( $needed_shortcodes['inscription-en-ligne'] ) ? 'warning' : 'success',
		'Ajouter le shortcode [inscription-en-ligne] pour permettre aux amapiens de s\'inscrire en ligne.',
		'Ce shortcode nécessite une clé de sécurité afin que seule les personnes à qui vous avez transmis le lien puissent s\'inscrire',
		isset( $needed_shortcodes['inscription-en-ligne'] ) ? admin_url( 'post-new.php?post_type=page' ) : admin_url( 'post.php?post=' . $found_shortcodes['inscription-en-ligne']->ID . '&action=edit' ),
		'Par exemple : [inscription-en-ligne key=' . uniqid() . uniqid() . ' email=contact@votre-amap.xxx]'
	);
	$assistant_conf_url         = admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' );
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		'Réglage de l\'étape "Réglement AMAP" et autres réglages de l\'assistant inscription en ligne',
		'Si vous souhaitez inclure une étape "Règlement de l\'AMAP" préalable à l\'inscription aux contrats, saisir le titre de l\'étape et le règlement <a href="' . $assistant_conf_url . '" target="_blank">ici</a>, puis ajouter "agreement=true dans le shortcode [inscription-en-ligne]".',
		$assistant_conf_url
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		'Autres réglages de l\'assistant inscription en ligne',
		'Vous pouvez y configurer les messages de certaines étapes.',
		$assistant_conf_url
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		'Emails envoyés par l\'assistant inscription en ligne',
		'Vous pouvez y configurer les mails de confirmation.',
		admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_mails' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		isset( $needed_shortcodes['amapien-edit-infos'] ) ? 'warning' : 'success',
		'Ajouter le shortcode [amapien-edit-infos] pour permettre aux amapiens de modifier leurs coordonnées.',
		'Typiquement sur la page Mes infos',
		isset( $needed_shortcodes['amapien-edit-infos'] ) ? $amapien_mes_infos_edit_href : admin_url( 'post.php?post=' . $found_shortcodes['amapien-edit-infos']->ID . '&action=edit' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		isset( $needed_shortcodes['mes-contrats'] ) ? 'warning' : 'success',
		'Ajouter le shortcode [mes-contrats] pour permettre aux amapiens de voir leurs inscriptions.',
		'Ce shortcode permet aussi aux amapiens de s\'inscrire à d\'autres contrats en cours d\'année',
		isset( $needed_shortcodes['mes-contrats'] ) ? admin_url( 'post-new.php?post_type=page' ) : admin_url( 'post.php?post=' . $found_shortcodes['mes-contrats']->ID . '&action=edit' )
	);

	$state['30_recalls'] = array();
	foreach ( AmapressEntities::getMenu() as $item ) {
		if ( isset( $item['type'] ) && $item['type'] == 'panel' && isset( $item['id'] ) ) {
			$page_name = isset( $item['settings']['name'] ) ? $item['settings']['name'] . ' - ' : '';
			$page_id   = $item['id'];
			if ( ! empty( $item['tabs'] ) ) {
				foreach ( $item['tabs'] as $tab_id => $tab ) {
					$tab_name = ( isset( $tab['name'] ) ? $tab['name'] : $tab_id ) . ' - ';
					if ( isset( $tab['id'] ) ) {
						$tab_id = $tab['id'];
					}
					if ( ! empty( $tab['options'] ) ) {
						foreach ( $tab['options'] as $option ) {
							if ( empty( $option['id'] ) ) {
								continue;
							}
							if ( empty( $option['name'] ) ) {
								continue;
							}
							if ( empty( $option['hook_name'] ) ) {
								continue;
							}

							$val = Amapress::getOption( $option['id'] );

							$tab_href = add_query_arg( [
									'page' => $page_id,
									'tab'  => $tab_id,
								], admin_url( 'admin.php' ) ) . '#' . $option['id'];

							if ( ! empty( $val['enabled'] ) || false !== strpos( $option['id'], '-1' ) ) {
								$state['30_recalls'][] = amapress_get_check_state(
									'info',
									$page_name . $tab_name . ( isset( $option['desc'] ) ? ' - ' . $option['desc'] . ' - ' : '' ) . $option['name'],
									TitanFrameworkOptionEventScheduler::getFormattedEventDate( $val, isset( $option['scheduler_type'] ) ? $option['scheduler_type'] : 'days' ),
									$tab_href
								);
							}
						}
					}
				}
			}
			if ( ! empty( $item['options'] ) ) {
				foreach ( $item['options'] as $option ) {
					if ( empty( $option['id'] ) ) {
						continue;
					}
					if ( empty( $option['name'] ) ) {
						continue;
					}
					if ( empty( $option['hook_name'] ) ) {
						continue;
					}

					$val = Amapress::getOption( $option['id'] );

					$tab_href = add_query_arg( [
							'page' => $page_id,
						], admin_url( 'admin.php' ) ) . '#' . $option['id'];

					$state['30_recalls'][] = amapress_get_check_state(
						'error',
						$page_name . ( isset( $option['desc'] ) ? ' - ' . $option['desc'] . ' - ' : '' ) . $option['name'],
						TitanFrameworkOptionEventScheduler::getFormattedEventDate( $val, isset( $option['scheduler_type'] ) ? $option['scheduler_type'] : 'days' ),
						$tab_href
					);
				}
			}
		}
	}

	$state['35_import']   = array();
	$state['35_import'][] = amapress_get_check_state(
		'do',
		'Amapiens',
		'Importer des amapiens à partir d\'un fichier Excel.',
		admin_url( 'admin.php?page=amapress_import_page&tab=import_users_tab' )
	);
	$state['35_import'][] = amapress_get_check_state(
		count( $subscribable_contrat_instances ) == 0 ? 'error' : 'do',
		'Adhésions',
		count( $subscribable_contrat_instances ) == 0 ? 'Vous devez créer au moins un modèle de contrat pour importer les adhésions' : 'Importer des inscriptions à partir d\'un fichier Excel.',
		admin_url( 'admin.php?page=amapress_import_page&tab=import_adhesions_tab' )
	);

	$clean_messages = '';
	if ( isset( $_REQUEST['clean'] ) ) {
		if ( 'orphans' == $_REQUEST['clean'] ) {
			$clean_messages = implode( '<br />', [
				AmapressAmapien_paiement::cleanOrphans(),
				AmapressContrat_quantite::cleanOrphans()
			] );
		}
	}
	$state['40_clean']   = array();
	$state['40_clean'][] = amapress_get_check_state(
		'do',
		'Nettoyer les éléments orphelins',
		'<p>Permet de nettoyer la base de donnée</p>
<p style="font-family: monospace">' . $clean_messages . '</p>',
		admin_url( 'admin.php?page=amapress_state&clean=orphans' ),
		null,
		false
	);

	return $state;
}

function embedded_phpinfo() {
	ob_start();
	phpinfo();
	$phpinfo = ob_get_contents();
	ob_end_clean();
	$phpinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo );
	echo "
        <style type='text/css'>
            #phpinfo {}
            #phpinfo pre {margin: 0; font-family: monospace;}
            #phpinfo a:link {color: #009; text-decoration: none; background-color: #fff;}
            #phpinfo a:hover {text-decoration: underline;}
            #phpinfo table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
            #phpinfo .center {text-align: center;}
            #phpinfo .center table {margin: 1em auto; text-align: left;}
            #phpinfo .center th {text-align: center !important;}
            #phpinfo td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
            #phpinfo h1 {font-size: 150%;}
            #phpinfo h2 {font-size: 125%;}
            #phpinfo .p {text-align: left;}
            #phpinfo .e {background-color: #ccf; width: 300px; font-weight: bold;}
            #phpinfo .h {background-color: #99c; font-weight: bold;}
            #phpinfo .v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
            #phpinfo .v i {color: #999;}
            #phpinfo img {float: right; border: 0;}
            #phpinfo hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
        </style>
        <div id='phpinfo'>
            $phpinfo
        </div>
        ";
}

function amapress_echo_and_check_amapress_state_page() {
//	if (!defined( 'AMAPRESS_DEMO_MODE' ))
//		define('AMAPRESS_DEMO_MODE', 1);
	if ( current_user_can( 'update_core' ) ) {
		if ( isset( $_GET['generate_full_amap'] ) ) {
			echo '<textarea cols="80" rows="100" style="width: 100%; font-family: monospace">';
			echo esc_textarea( wp_kses_decode_entities( wp_specialchars_decode( preg_replace( '/\<\/?pre\>/', '',
				Amapress::generate_full_amap( isset( $_REQUEST['no_anonymize'] ) ) ), ENT_QUOTES ) ) );
			echo '</textarea>';

			return;
		}
		if ( defined( 'AMAPRESS_DEMO_MODE' ) && isset( $_GET['clean_amap'] ) ) {
			require_once 'demos/AmapDemoBase.php';

			$cnt = AmapDemoBase::deleteAutoGeneratedPosts();
			echo '<p class="notice notice-info">' . $cnt . ' deleted posts</p>';
			$cnt = AmapDemoBase::deleteAutoGeneratedUsers();
			echo '<p class="notice notice-info">' . $cnt . ' deleted users</p>';

			return;
		}
		if ( defined( 'AMAPRESS_DEMO_MODE' ) && isset( $_GET['clean_partial_amap'] ) ) {
			require_once 'demos/AmapDemoBase.php';

			$cnt = AmapDemoBase::deletePartialAutoGeneratedPosts();
			echo '<p class="notice notice-info">' . $cnt . ' deleted posts</p>';

			return;
		}
		if ( defined( 'AMAPRESS_DEMO_MODE' ) && isset( $_GET['import_amap'] ) ) {
			require_once 'demos/AmapDemoBase.php';
			require_once 'demos/' . $_GET['import_amap'];

			if ( ! defined( 'ALLOW_UNFILTERED_UPLOADS' ) ) {
				define( 'ALLOW_UNFILTERED_UPLOADS', true );
			}

			/** @var AmapDemoBase $amap_class */
			global $amap_class;
			$cnt = AmapDemoBase::deleteAutoGeneratedPosts();
			echo '<p class="notice notice-info">' . $cnt . ' deleted posts</p>';
			$amap_class->createAMAP();
			echo '<p class="notice notice-info">AMAP Created</p>';

			return;
		}
		if ( isset( $_REQUEST['rand_addr'] ) ) {
			$address = $_REQUEST['address'];
			$around  = $_REQUEST['around'];

			$resolved = TitanFrameworkOptionAddress::lookup_address( $address );
			if ( ! empty( $resolved ) && ! is_wp_error( $resolved ) ) {
				require_once 'demos/AmapDemoBase.php';

				amapress_dump( AmapDemoBase::generateRandomAddress( $resolved['latitude'], $resolved['longitude'], intval( $around ) ) );
			} else {
				echo '<p style="color:red">' . esc_html( $address ) . '</p>';
				echo '<p style="color:red">Addresse non localisée</p>';
			}
		}

		if ( isset( $_GET['phpinfo'] ) ) {
			embedded_phpinfo();

			return;
		}
	}

	$labels = array(
		'01_plugins'      => 'Plugins',
		'05_config'       => 'Configuration',
		'10_users'        => 'Comptes utilisateurs',
		'15_posts'        => 'Votre AMAP',
		'20_content'      => 'Contenus complémentaires',
		'24_shortcodes'   => 'Shortcodes configurés',
		'25_shortcodes'   => 'Shortcodes à configurer',
		'26_online_inscr' => 'Inscriptions en ligne',
		'30_recalls'      => 'Rappels',
		'35_import'       => 'Import CSV',
		'40_clean'        => 'Nettoyage',
	);
	$i      = 1;
	foreach ( $labels as $k => $v ) {
		$labels[ $k ] = "$i/ $v";
		$i            += 1;
	}

	$state = amapress_get_state();

	if ( current_user_can( 'update_core' ) ) {
		if ( defined( 'AMAPRESS_DEMO_MODE' ) ) {
			echo '<h2>DEMO MODE Administrative section</h2>';
			echo '<p><a href="' . esc_attr( add_query_arg( 'clean_amap', 'T', admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">Nettoyer les custom posts</a></p>';
			echo '<p><a href="' . esc_attr( add_query_arg( 'clean_partial_amap', 'T', admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">Nettoyer les générables</a></p>';
			echo '<p><a href="' . esc_attr( add_query_arg( 'generate_full_amap', 'T', admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">Générer le code de démo</a></p>';
			echo '<p><a href="' . esc_attr( add_query_arg( [
					'generate_full_amap' => 'T',
					'no_anonymize'       => 'T'
				], admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">Générer le code de démo (sans anonymisation)</a></p>';
			echo '<form method="post">
<input type="hidden" name="rand_addr" />
<label>Adresse à anonymiser: <input type="text" name="address"/></label>
<br/>
<label>Dans un rayon de: <input type="number" step="100" name="around" value="2000"/></label>
<input type="submit" value="Générer" />
</form>';
			echo '<hr/>';
			echo '<h3>Modèles d\'AMAP</h3>';
			if ( $handle = opendir( AMAPRESS__PLUGIN_DIR . '/demos' ) ) {
				while ( false !== ( $entry = readdir( $handle ) ) ) {
					if ( $entry != '.' && $entry != '..' && $entry != 'AmapDemoBase.php' ) {
						echo '<a target="_blank" href="' . esc_attr( add_query_arg( 'import_amap', $entry, admin_url( 'admin.php?page=amapress_state' ) ) ) . '">' . esc_html( $entry ) . '</a>';
					}
				}
				closedir( $handle );
			}
			echo '<hr/>';
		}


		echo '<p><a href="' . esc_attr( amapress_get_github_updater_url() ) . '" target="_blank">Rafraichir le cache Github Updater</a> / <a href="' . esc_attr( admin_url( 'plugins.php' ) ) . '" target="_blank">Voir les extensions installées</a></p>';
		echo '<p><a href="' . esc_attr( add_query_arg( 'phpinfo', 'T' ) ) . '" target="_blank">Afficher PHP Infos</a></p>';
	}

	if ( defined( 'FREE_PAGES_PERSO' ) && FREE_PAGES_PERSO ) {
		echo '<p><strong>Fonctionnement Free Pages Perso: actif</strong></p>';
	}
	if ( defined( 'SEND_EMAILS_AS_PLAIN_TEXT' ) ) {
		echo '<p><strong>Envoi des mails en texte brut: actif</strong></p>';
	}

	global $wp_version;
	echo '<p><strong>Version PHP : ' . PHP_VERSION . '</strong></p>';
	echo '<p><strong>Version Wordpress : ' . $wp_version . '</strong></p>';
	echo '<p><strong>Version d\'Amapress : ' . AMAPRESS_VERSION . '</strong></p>';

	echo '<div id="amps-state-accordion">';
	foreach ( $state as $categ => $checks ) {
		$global_states = [];
		foreach ( $checks as $check ) {
			$global_states[] = $check['state'];
		}
		$global_state = 'info';
		if ( in_array( 'error', $global_states ) ) {
			$global_state = 'error';
		} else if ( in_array( 'warning', $global_states ) ) {
			$global_state = 'warning';
		} else if ( in_array( 'success', $global_states ) ) {
			$global_state = 'success';
		}

		echo '<h3><span class="check-item state  ' . $global_state . '">' . esc_html( $labels[ $categ ] ) . '</span></h3>';

		echo '<div>';

		foreach ( $checks as $check ) {
			$title  = $check['name'];
			$state  = $check['state'];
			$desc   = $check['message'];
			$link   = $check['link'];
			$values = isset( $check['values'] ) ? $check['values'] : '';
			$target = "target='_blank'";
			if ( isset( $check['target_blank'] ) && ! $check['target_blank'] ) {
				$target = '';
			}

			echo "<div class='amapress-check'>";
			if ( empty( $link ) ) {
				echo "<p class='check-item state {$state}'>{$title}<span class='dashicons dashicons-external'></span></p>";
			} else {
				echo "<p class='check-item state {$state}'><a href='$link' $target>{$title}</a><span class='dashicons dashicons-external'></span></p>";
			}
			echo "<div class='amapress-check-content'>";
			if ( ! empty( $values ) ) {
				echo "<p class='values'>{$values}</p>";
			}
			if ( ! empty( $desc ) ) {
				echo "<p class='description'>{$desc}</p>";
			}
			echo "</div>";
			echo "</div>";
		}

		echo '</div>';
	}
	echo '</div>';
	echo '<script type="text/javascript">
	jQuery(document).ready(function($) {
		$( "#amps-state-accordion" ).accordion({
			heightStyle: "content",
			collapsible: true,
			active : "none"
		});
	});
</script>';
}

function amapress_get_state_summary() {
	$key     = 'amapress_state_summary';
	$summary = get_transient( $key );
	if ( false === $summary ) {
		$summary = [
			'warning' => 0,
			'error'   => 0,
		];
		$state   = amapress_get_state();
		foreach ( $state as $categ => $checks ) {
			foreach ( $checks as $check ) {
				if ( ! isset( $summary[ $check['state'] ] ) ) {
					$summary[ $check['state'] ] = 0;
				}
				$summary[ $check['state'] ] += 1;
			}
		}
		set_transient( $key, $summary );
	}

	return $summary;
}

add_action( 'pre_current_active_plugins', function ( $plugins ) {
	echo '<p><a href="' . esc_attr( amapress_get_github_updater_url() ) . '" target="_blank">Rafraichir le cache Github Updater</a></p>';
} );

add_action( 'activate_plugin', 'amapress_clean_state_transient' );
add_action( 'save_post', 'amapress_clean_state_transient' );
add_action( 'tf_save_options_amapress', 'amapress_clean_state_transient' );