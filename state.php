<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once AMAPRESS__PLUGIN_DIR . '/utils/install-from-github.php';

add_action( 'template_redirect', function () {
	if ( 'shouldredirect' == get_query_var( 'amp_action' ) ) {
		wp_die( '<strong style="color: #2b542c">' . __( 'Redirection réussie', 'amapress' ) . '</strong>' );
	}
} );

function amapress_check_spf() {
	/* Taken from stop-wp-emails-going-to-spam plugin */
	$ip = $_SERVER['SERVER_ADDR'];
	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$ip4 = true;
	} else {
		$ip4 = false;
	}
	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		$ip6 = true;
	} else {
		$ip6 = false;
	}
	$domain = Amapress::getSiteDomainName( true );

	$dns = @dns_get_record( $domain, DNS_ALL );
	$spf = false;
	if ( $dns ) {
		foreach ( $dns as $dnstxt ) {
			if ( 'TXT' == $dnstxt['type'] ) {
				if ( isset( $dnstxt['txt'] ) ) {
					if ( 'v=spf' == substr( $dnstxt['txt'], 0, 5 ) ) {
						$spf = $dnstxt['txt'];
						break;
					}
				}
			}
		}
	}

	ob_start();
	if ( ! $dns ) {
		_e( '<p class="notice notice-error">Cannot get DNS records - refresh this page - if you still get this message after a few refreshes you may want to check your domain DNS control panel</p>', 'stop-wp-emails-going-to-spam' );
	} else {
		if ( false == $spf ) {
			printf( __( '<p class="notice notice-error">No SPF record found for %s, the following SPF record is recommended', 'stop-wp-emails-going-to-spam' ), $domain );
			if ( $ip4 || $ip6 ) {
				printf( ' v=spf1 +a +mx %s:%s ~all', ( $ip4 ) ? 'ip4' : 'ip6', esc_html( $ip ) );
			} else {
				echo 'v=spf1 +a +mx ~all';
			}
			echo '</p>';
		} else {
			printf( __( 'Current record SPF record for %s: <br><strong>%s</strong><br><br>', 'stop-wp-emails-going-to-spam' ), $domain, $spf );
//			if ( strpos( $spf, $ip ) ) {
//				_e( '<p class="notice notice-success">Good!, this contains your server IP address</p>', 'stop-wp-emails-going-to-spam' );
//			} else {
//				printf( __( '<p class="notice notice-warning">Recommend you add +%s:%s to your SPF record</p>', 'stop-wp-emails-going-to-spam' ), ( $ip4 ) ? 'ip4' : 'ip6', esc_html( $ip ) );
//			}

		}
	}

	return ob_get_clean();
}

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

function amapress_get_plugin_desactivate_link( $plugin_slug ) {
	$installed_plugins = array_keys( get_plugins() );
	$installed_plugins = array_combine( array_map( function ( $v ) {
		$vv = explode( '/', $v );

		return $vv[0];
	}, $installed_plugins ), array_values( $installed_plugins ) );
	$plugin_slug       = isset( $installed_plugins[ $plugin_slug ] ) ? $installed_plugins[ $plugin_slug ] : $plugin_slug;
	$action            = 'deactivate';

	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $plugin_slug
			),
			admin_url( 'plugins.php' )
		),
		'deactivate-plugin_' . $plugin_slug
	);
}

function amapress_get_check_state( $state, $name, $message, $link, $values = null, $target_blank = true, $icon = '' ) {
	return array(
		'state'        => $state,
		'name'         => $name,
		'message'      => $message,
		'link'         => $link,
		'values'       => $values,
		'target_blank' => $target_blank,
		'icon'         => $icon,
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

function amapress_check_plugin_install(
	$plugin_slug, $plugin_name, $message_if_install_needed,
	$not_installed_level = 'warning', $installed_level = 'success',
	$values = null
) {
	if ( is_array( $plugin_slug ) ) {
		$is_active        = amapress_is_plugin_active( $plugin_slug['short_slug'] );
		$action_link      = amapress_install_plugin_from_github_url(
			$plugin_slug['slug'],
			$plugin_slug['name'],
			$plugin_slug['github_repo']
		);
		$plugin_info_link = Amapress::makeExternalLink( 'https://github.com/' . $plugin_slug['github_repo'],
			__( 'Voir le dépôt GitHub de l\'extension', 'amapress' ), true );
		$install_link     = $action_link;
		$activate_link    = $action_link;
	} else {
		$is_active = amapress_is_plugin_active( $plugin_slug );

		$plugin_info_link = '<span class="dashicons dashicons-wordpress-alt"></span>&nbsp;' .
		                    Amapress::makeLink( 'https://fr.wordpress.org/plugins/' . $plugin_slug,
			                    __( 'Fiche Infos Wordpress', 'amapress' ), true, true );
		$install_link     = amapress_get_plugin_install_link( $plugin_slug );
		$activate_link    = amapress_get_plugin_activate_link( $plugin_slug );
	}

	return amapress_get_check_state(
		$is_active == 'active' ? $installed_level : $not_installed_level,
		$plugin_name . ( $is_active != 'active' ? ' (<span class="dashicons dashicons-admin-plugins"></span> ' . ( $is_active == 'not-installed' ? __( 'installer', 'amapress' ) : __( 'activer', 'amapress' ) ) . ')' : ' (<span class="dashicons dashicons-plugins-checked"></span> ' . __( 'actif', 'amapress' ) . ')' ),
		$message_if_install_needed . '<br/>' . $plugin_info_link,
		$is_active == 'not-installed' ? $install_link : ( $is_active == 'installed' ? $activate_link : '' ),
		$values, true, false
	);
}

function amapress_check_plugin_not_active( $plugin_slug, $plugin_name, $message_if_active, $active_level = 'warning' ) {
	$is_active = amapress_is_plugin_active( $plugin_slug );

	return amapress_get_check_state(
		$is_active == 'active' ? $active_level : 'success',
		$plugin_name . ( $is_active == 'active' ? ' (désactiver)' : '' ),
		$message_if_active,
		$is_active == 'active' ? amapress_get_plugin_desactivate_link( $plugin_slug ) : ''
	);
}

function amapress_clean_state_transient() {
	global $amapress_import_demo;

	if ( $amapress_import_demo ) {
		return;
	}

	static $amapress_clean_state_transient = false;

	if ( ! $amapress_clean_state_transient ) {
		delete_transient( 'amapress_state_summary' );
		delete_transient( 'amapress_state_check_titles' );
		$amapress_clean_state_transient = true;
	}
}

function amapress_get_state() {
	amapress_clean_state_transient();

	$state                 = array();
	$state['01_plugins']   = array();
	$backup_status         = amapress_get_updraftplus_backup_status();
	$state['01_plugins'][] = amapress_check_plugin_install( 'updraftplus', __( 'UpdraftPlus WordPress Backup', 'amapress' ),
		sprintf( __( '<strong>Requis</strong> : Réalise la sauvegarde du site. 
<br/><strong>Etat actuel</strong>: sauvegarde %s (%s), %s
<br/><strong>Configuration minimale :</strong> sauvegarde quotidienne de la base de données, sauvegarde hebdomadaire des fichiers, stockage externe', 'amapress' ),
			$backup_status, amapress_get_updraftplus_backup_last_backup_date(), amapress_get_updraftplus_backup_intervals() ) .
		sprintf( __( '<br/>%s
<br/><span class="dashicons dashicons-admin-settings"></span> %s', 'amapress' ),
			Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/sauvegarde' ), Amapress::makeLink( admin_url( 'options-general.php?page=updraftplus' ), __( 'Configuration', 'amapress' ), true, true ) ),
		! defined( 'FREE_PAGES_PERSO' ) && ! defined( 'AMAPRESS_DEMO_MODE' ) ? 'error' : 'info',
		! defined( 'FREE_PAGES_PERSO' ) && ! defined( 'AMAPRESS_DEMO_MODE' ) ?
			( 'inactive' == $backup_status ? 'error' : ( 'local' == $backup_status ? 'warning' : 'success' ) ) : 'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'command-palette', __( 'Command Palette', 'amapress' ),
		__( '<strong>Recommandé</strong> : Permet une recherche complète dans le Tableau de bord, le titre des pages, les panneaux d\'administration, certains réglages...', 'amapress' ),
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'akismet', __( 'Akismet', 'amapress' ),
		__( '<strong>Recommandé</strong> : Protège le site du SPAM.', 'amapress' ),
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'block-bad-queries', __( 'Block Bad Queries', 'amapress' ),
		__( '<strong>Recommandé</strong> : Protège votre site contre les attaques par requêtes malveillantes', 'amapress' ),
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'new-user-approve', __( 'New User Approve', 'amapress' ),
		__( '<strong>Optionnel</strong> : Installer ce plugin si le paramètre « Création de compte sur le site » (Section 2 – configuration) est activé. Une inscription en ligne nécessitera une validation de l’utilisateur par un administrateur.', 'amapress' ),
		Amapress::userCanRegister() ? 'error' : 'info' );
	$state['01_plugins'][] = amapress_check_plugin_install(
		[
			'short_slug'  => 'google-sitemap-generator',
			'slug'        => 'google-sitemap-generator/sitemap.php',
			'name'        => __( 'Google XML Sitemaps (BlueChip fork)', 'amapress' ),
			'github_repo' => 'chesio/google-sitemap-generator',
		],
		__( 'Google XML Sitemaps (BlueChip fork)', 'amapress' ),
		sprintf( __( '<strong>Recommandé</strong> : Utilisation simple, améliore le référencement du site en générant un plan du site et en notifiant les moteurs de recherche des modifications du site. 
<br/>Après activation rendez-vous dans sa <a target="_blank" href="%s">configuration</a> (Section Contenu du sitemap/Autres types d\'article) et cocher les cases "Inclure les articles de type Produits/Recettes/Producteurs/Lieux de distribution/Productions"', 'amapress' ), admin_url( 'options-general.php?page=google-sitemap-generator%2Fsitemap.php#sm_includes' ) ),
		defined( 'AMAPRESS_DEMO_MODE' ) ? 'info' : 'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'unconfirmed', __( 'Unconfirmed', 'amapress' ),
		__( '<strong>Recommandé</strong> : Permet de gérer les inscriptions en cours, renvoyer le mail de bienvenue avec le lien pour activer le compte utilisateur.', 'amapress' ),
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'user-switching', __( 'User Switching', 'amapress' ),
		__( '<strong>Recommandé</strong> : Permet aux administrateurs de consulter Amapress avec un autre compte utilisateur. Ce plugin est à installer par un webmaster. ', 'amapress' ),
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'wp-maintenance', 'WP Maintenance',
		__( '<strong>Optionnel</strong> : Permet d\'indiquer aux visiteurs que le site de votre AMAP est en construction et d\'éviter l\'affichage de contenu non finalisé.', 'amapress' ),
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'uk-cookie-consent', 'Termly | GDPR/CCPA Cookie Consent Banner',
		__( '<strong>Recommandé</strong> : Affiche un bandeau de consentement à l\'utilisation des cookies sur votre site. Cela est nécessaire pour être en conformité avec la loi RGPD, par exemple, si vous faites des statistiques (ie, Google Analytics) sur les visiteurs.', 'amapress' ),
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'enable-media-replace', __( 'Enable Media Replace', 'amapress' ),
		__( '<strong>Recommandé</strong> : Permet de remplacer facilement une image ou un contrat Word dans la « Media Library » de Wordpress', 'amapress' ),
		'warning' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'contact-form-7', __( 'Contact Form 7', 'amapress' ),
		sprintf(
			__( '<strong>Optionnel</strong> : Permet de créer des formulaires de demande d\'adhésion à l’AMAP (%s), de contact les auteurs de recettes…', 'amapress' ),
			Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/contact_form' )
		),
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'really-simple-captcha', __( 'Really Simple CAPTCHA', 'amapress' ),
		__( '<strong>Optionnel</strong> : Permet de mettre des captcha dans les formulaires Contact Form 7 pour empêcher les bots de spams', 'amapress' ),
		'info' );
	$state['01_plugins'][] = amapress_check_plugin_install( 'contact-form-7-honeypot', __( 'Honeypot for Contact Form 7', 'amapress' ),
		__( '<strong>Optionnel</strong> : Permet de mettre des pièges à bots de spams dans les formulaires Contact Form 7 (sans impact sur les utilisateurs)', 'amapress' ),
		'info' );

	$state['02_plugins_not']   = array();
	$state['02_plugins_not'][] = amapress_check_plugin_not_active( 'aryo-activity-log', __( 'Activity Log', 'amapress' ),
		__( '<strong>Non recommandé</strong> : ce plugin peut entrainer des lenteurs du Tableau de Bord et du site en général; Permet de tracer l\'activité des utilisateurs dans votre AMAP (création, modification, suppression de contenu, pages, articles, utilisateurs...)', 'amapress' ),
		'warning' );

	$state['05_config'] = array();

	if ( version_compare( phpversion(), '7.0', '<' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'warning',
			sprintf( __( 'PHP 7 ou sup recommandée (actuellement %s)', 'amapress' ), phpversion() ),
			__( 'Voir la configuration de votre hébergement (par ex, pour <a href="https://docs.ovh.com/fr/hosting/configurer-le-php-sur-son-hebergement-web-mutu-2014/">OVH</a>). Utiliser la version 7 ou supérieur de PHP est recommandé pour obtenir des performances optimales pour WordPress et Amapress.', 'amapress' ),
			''
		);
	}

	if ( ! defined( 'FREE_PAGES_PERSO' ) ) {
		$github_updater = get_option( 'git_updater' );
		if ( is_multisite() ) {
			$github_updater = get_site_option( 'git_updater' );
		}
		if ( empty( $github_updater ) ) {
			$state['05_config'][] = amapress_get_check_state(
				'error',
				__( 'L\'extension Git Updater est requis pour la bonne mise à jour d\'Amapress', 'amapress' ),
				__( 'Veuillez utiliser l\'installateur automatique qui est affiché en haut du <a target="_blank" href="', 'amapress' ) . admin_url( 'index.php' ) . '">tableau de bord</a> ou suivre la <a target="_blank" href="https://github.com/afragen/git-updater/wiki/Installation">procédure d\'installation manuelle</a>',
				''
			);
		} elseif ( empty( $github_updater['github_access_token'] ) ) {
			$state['05_config'][] = amapress_get_check_state(
				'error',
				__( 'Un jeton d\'accès GitHub (Personal Access Token) pour l\'extension Git Updater est requis pour la bonne mise à jour d\'Amapress', 'amapress' ),
				__( 'Veuillez créer un Personal Access Token en suivant ce <a target="_blank" href="https://github.com/afragen/git-updater/wiki/Messages#personal-github-access-token">lien</a>', 'amapress' ),
				admin_url( 'options-general.php?page=git-updater&tab=git_updater_settings&subtab=github' )
			);
		}
	}

	if ( ! extension_loaded( 'zip' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'error',
			__( 'Extension PHP ZIP', 'amapress' ),
			__( 'L\'extension ZIP de PHP doit être activée pour le bon fonctionnement d\'Amapress', 'amapress' ),
			'https://www.php.net/manual/fr/zip.setup.php'
		);
	}
	if ( ! extension_loaded( 'curl' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'error',
			__( 'Extension PHP cURL', 'amapress' ),
			__( 'L\'extension cURL de PHP doit être activée pour le bon fonctionnement d\'Amapress', 'amapress' ),
			'https://www.php.net/manual/fr/curl.setup.php'
		);
	}

	if ( ! extension_loaded( 'imap' ) ) {
		$state['05_config'][] = amapress_get_check_state(
			'warning',
			__( 'Extension PHP IMAP', 'amapress' ),
			__( 'L\'extension IMAP de PHP doit être activée pour que les Emails groupés soient actifs', 'amapress' ),
			'https://www.php.net/manual/fr/imap.setup.php'
		);
	}

	if ( 'active' === amapress_is_plugin_active( 'akismet' ) ) {
		if ( ! amapress_has_akismet_api_key() ) {
			$state['05_config'][] = amapress_get_check_state(
				'warning',
				__( 'Clé API Akismet', 'amapress' ),
				__( 'Une clé API doit être configurée pour qu\'Akismet soit fonctionnel', 'amapress' ),
				admin_url( 'options-general.php?page=akismet-key-config' )
			);
		}
	}

	$state['05_config'][] = amapress_get_check_state(
		is_ssl() ? 'success' : 'warning',
		is_ssl() ? 'HTTPS Activé' : 'HTTPS Désactivé',
		__( 'Passer votre site en HTTPS améliore sa sécurité et son référencement.', 'amapress' )
		. ( ! is_ssl() ? '<br/>' . __( 'Pour activer le HTTPS simplement dans WordPress, voir plugin Really Simple SSL ci-dessous.', 'amapress' ) : '' )
		. ( is_ssl() && current_user_can( 'manage_options' ) ?
			'<br/><a href="' . esc_attr( add_query_arg( 'check_ssl', 'T' ) ) . '" target="_blank">' . __( 'Vérifier que le contenu du site de votre AMAP référence uniquement du contenu HTTPS', 'amapress' ) . '</a>'
			: '' ),
		''
	);

	if ( is_ssl() ) {
		$siteurl = get_option( 'siteurl' );
		if ( ! empty( $siteurl ) && 0 !== strpos( $siteurl, 'https:' ) ) {
			$state['05_config'][] = amapress_get_check_state(
				'error',
				__( 'Paramètre "Adresse web de WordPress (URL)" non HTTPS', 'amapress' ),
				sprintf( __( 'Devrait contenir "%s" au lieu de "%s"', 'amapress' ), str_replace( 'http:', 'https:', $siteurl ), $siteurl ),
				admin_url( 'options-general.php' )
			);
		}
		$home = get_option( 'home' );
		if ( ! empty( $home ) && 0 !== strpos( $home, 'https:' ) ) {
			$state['05_config'][] = amapress_get_check_state(
				'error',
				__( 'Paramètre "Adresse web du site (URL)" non HTTPS', 'amapress' ),
				sprintf( __( 'Devrait contenir "%s" au lieu de "%s"', 'amapress' ), str_replace( 'http:', 'https:', $home ), $home ),
				admin_url( 'options-general.php' )
			);
		}
	}
	$state['05_config'][] = amapress_check_plugin_install( 'really-simple-ssl', __( 'Really Simple SSL', 'amapress' ),
		__( '<strong>Recommandé</strong> : Aide à passer votre site en HTTPS.', 'amapress' ),
		is_ssl() ? 'info' : 'warning' );

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
			__( 'Réglage des permaliens', 'amapress' ),
			__( 'Le réglage des permaliens pour Free Pages Perso doit être "Structure personnalisée", commencer par /index.php/ suivi de "%postname%/" ou "%year%/%monthnum%/%postname%/" ou "%year%/%monthnum%/%day%/%postname%/"', 'amapress' ),
			admin_url( 'options-permalink.php' )
		);
	} else {
		$state['05_config'][] = amapress_get_check_state(
			'info',
			'DNS SPF record',
			amapress_check_spf(),
			''
		);
		$state['05_config'][] = amapress_get_check_state(
			empty( $permalink_structure )
			|| ! in_array( $permalink_structure,
				[
					'/%year%/%monthnum%/%day%/%postname%/',
					'/%year%/%monthnum%/%postname%/',
					'/%postname%/'
				] ) ? 'error' : 'success',
			__( 'Réglage des permaliens', 'amapress' ),
			__( 'Le réglage des permaliens doit suivre une des valeurs suivantes : Date et titre, Mois et titre ou Titre de la publication', 'amapress' ),
			admin_url( 'options-permalink.php' )
		);
	}

	$has_site_verif_codes = ! empty( Amapress::getOption( '' ) ) && ! empty( Amapress::getOption( '' ) );
	$state['05_config'][] = amapress_get_check_state(
		$has_site_verif_codes ? 'success' : 'warning',
		$has_site_verif_codes ? __( 'Code de vérification du site (Google/Bing) : OK', 'amapress' ) : __( 'Codes de vérification du site (Google/Bing) : non renseignés', 'amapress' ),
		__( 'Créer des codes de vérification du site depuis les Webmaster Tools pour <a href="https://www.google.com/webmasters/tools/dashboard?hl=fr" target="_blank">Google</a> et <a href="https://www.bing.com/toolbox/webmaster" target="_blank">Bing</a> permet d\'obtenir un meilleur référencement', 'amapress' ),
		admin_url( 'options-general.php?page=amapress_site_options_page&tab=amp_site_reference' )
	);

	if ( ! function_exists( 'get_filesystem_method' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$transport            = get_filesystem_method();
	$state['05_config'][] = amapress_get_check_state(
		'direct' == $transport ? 'success' : 'warning',
		sprintf( __( 'Méthode de mise à jour WordPress: %s', 'amapress' ), $transport ),
		'direct' == $transport ? __( 'Le mode de mise à jour actuel est direct. Vous pourrez effectuer les mises à jours sans problème.', 'amapress' ) : __( 'Le mode de mise à jour actuel n\'est pas direct. Vous pourrez rencontrer des difficultés à effectuer les mises à jours (<a href="https://codex.wordpress.org/fr:Modifier_wp-config.php#Les_Constantes_des_Mises_.C3.80_Jour_WordPress" target="_blank">voir les options de configuration de WordPress</a>).', 'amapress' ),
		''
	);

	$redir_test_url       = site_url( 'shouldredirect' );
	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Test de fonctionnement des redirections WordPress', 'amapress' ),
		sprintf( __( 'Cliquez sur le lien suivant : <a target="_blank" href="%s">%s</a>.<br/>Si vous voyez un message indiquant "Redirection réussie", tout va bien. Sinon vérifiez que le mod_rewrite est actif et que les htaccess ne sont désactivés.', 'amapress' ), $redir_test_url, $redir_test_url ),
		''
	);

	$htaccess_test_url    = wp_upload_dir()['baseurl'] . '/amapress-contrats/';
	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Test de fonctionnement de protection de dossier', 'amapress' ),
		sprintf( __( 'Cliquez sur le lien suivant : <a target="_blank" href="%s">%s</a>.<br/>Si vous voyez un message indiquant "Accès interdit", tout va bien. Sinon vérifiez que les htaccess ne sont désactivés.', 'amapress' ), $htaccess_test_url, $htaccess_test_url ),
		''
	);

	$admin_email          = get_bloginfo( 'admin_email' );
	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Adresse email de l\'administrateur', 'amapress' ),
		sprintf( __( 'L\'adresse email de l\'administrateur du site est actuellement : <strong>%s</strong>. L\'administrateur reçoit des emails sur l\'activité sur le site comme le changement de mot de passe)', 'amapress' ), esc_html( $admin_email ) ),
		admin_url( 'options-general.php' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Délai de suppression automatique des éléments dans les corbeilles', 'amapress' ),
		sprintf( __( 'Actuellement %d jour(s). Pour changer la valeur, ajouter <code>define(\'EMPTY_TRASH_DAYS\', <em>valeur_en_jour</em>);</code> dans votre <code>wp-config.php</code>.', 'amapress' ), esc_html( EMPTY_TRASH_DAYS ) ),
		''
	);

	$blog_desc            = get_bloginfo( 'description' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $blog_desc ) ? 'warning' : 'success',
		__( 'Description de l\'AMAP', 'amapress' ),
		__( 'Cette section permet le référencement dans les moteurs de recherche.
<br/>Remplir les champs <strong>Titre</strong> (Le nom de votre AMAP) et <strong>Slogan</strong> (Un sous titre pour votre AMAP. Vous pouvez ajouter la mention suivante "Construit avec Amapress, l\'outil pour les AMAP")', 'amapress' ),
		admin_url( 'customize.php?autofocus[section]=title_tagline' )
	);
	$site_icon            = get_option( 'site_icon' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $site_icon ) ? 'warning' : 'success',
		__( 'Icône de l\'AMAP', 'amapress' ),
		__( 'Ajouter une icône pour personnaliser l\'entête du navigateur et les signets/favoris.', 'amapress' ),
		admin_url( 'customize.php?autofocus[section]=title_tagline' )
	);
	$state['05_config'][] = amapress_get_check_state(
		! Amapress::userCanRegister() ? 'success' : ( 'active' != amapress_is_plugin_active( 'new-user-approve' ) ? 'error' : 'warning' ),
		__( 'Création de compte sur le site : ', 'amapress' ) . ( Amapress::userCanRegister() ? 'activée' : 'désactivée' ),
		__( '<strong>Non recommandé</strong> : Cette option permet aux nouveaux visiteurs de créer un compte utilisateur en direct. Sans cette option, seuls les responsables pourront créer des comptes utilisateurs. ', 'amapress' ),
		admin_url( 'options-general.php#users_can_register' )
	);
//    $blog_desc = get_theme_mod('custom_logo');
//    $state['05_config'][] = amapress_get_check_state(
//        empty($blog_desc) ? 'warning' : 'success',
//        __('Icone de l\'AMAP', 'amapress'),
//        __('Ajouter une icone pour l\'AMAP personnalise l\'entête du navigateur et les signets', 'amapress'),
//        admin_url('customize.php?autofocus[section]=title_tagline')
//    );

	$static_front_id      = get_option( 'page_on_front' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $static_front_id ) ? 'error' : 'success',
		__( 'Page d\'accueil statique', 'amapress' ),
		__( 'Vérifier que votre thème est configuré avec l’option « page d\'accueil statique ».<br/>Sélectionner votre page d’accueil existante, ou configurer une nouvelle page.', 'amapress' ),
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
		__( 'Contenu à la page d\'accueil', 'amapress' ),
		__( 'Ajouter le texte de présentation de votre Amap', 'amapress' ),
		admin_url( 'post.php?post=' . $static_front_id . '&action=edit' )
	);

	$static_blog_id       = get_option( 'page_for_posts' );
	$state['05_config'][] = amapress_get_check_state(
		empty( $static_blog_id ) ? 'error' : 'success',
		__( 'Page de blog/articles statique', 'amapress' ),
		__( 'Vérifier que votre thème est configuré avec l’option « page d\'accueil statique ».<br/>Sélectionner votre page de blog/articles existante, ou configurer une nouvelle page.', 'amapress' ),
		admin_url( 'customize.php?autofocus[section]=static_front_page' )
	);

	$contact_page         = Amapress::getContactInfos();
	$state['05_config'][] = amapress_get_check_state(
		empty( $contact_page ) || strpos( $contact_page, '[[' ) !== false ? 'warning' : 'success',
		__( 'Contenu de la page de contact', 'amapress' ),
		__( 'Ajouter les informations nécessaires pour contacter l’Amap pour une nouvelle inscription.', 'amapress' )
		. '<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/contact_form' ),
		admin_url( 'options-general.php?page=amapress_options_page&tab=amp_public_contacts_config' )
	);
	$state['05_config'][] = amapress_get_check_state(
		empty( $front_page_logo ) ? 'warning' : 'success',
		__( 'Logo de la page d\'accueil', 'amapress' ),
		__( 'Ajouter votre logo sur la page d\'accueil', 'amapress' ),
		admin_url( 'post.php?post=' . $static_front_id . '&action=edit' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Configuration de la liste d\'émargement', 'amapress' ),
		__( 'Personnaliser les infos affichées (téléphones, mails, instructions...) sur la liste d\'émargement et sa taille d\'impression.', 'amapress' ),
		admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_emargement_options_tab' )
	);

//    $contrat_anon = Amapress::getOption('contrat_info_anonymous');
//    $state['05_config'][] = amapress_get_check_state(
//        empty($contrat_anon) ? 'warning' : 'success',
//        __('Information sur les contrats', 'amapress'),
//        empty($contrat_anon) ?
//            __('Ajouter le texte d\'information sur les contrats', 'amapress') :
//            __('Cliquer sur le lien ci-dessus pour éditer le texte d\'information sur les contrats', 'amapress'),
//        admin_url('options-general.php?page=amapress_options_page&tab=contrats')
//    );

//    $menu_name = 'primary';
//    $locations = get_nav_menu_locations();

//    $state['05_config'][] = amapress_get_check_state(
//        empty($main_menu) || count($main_menu) == 0 ? 'error' : 'success',
//        __('Menu principal du site', 'amapress'),
//        empty($main_menu) || count($main_menu) == 0 ?
//            __('Remplir le menu principal du site', 'amapress') :
//            __('Cliquer sur le lien ci-dessus pour éditer le menu', 'amapress'),
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
		__( 'Entrée de menu - Page de blog', 'amapress' ),
		__( 'Créer une entrée dans le menu principal vers la page « Blog/Articles » (menu permettant l\'accès aux articles publiés sur le site).', 'amapress' ),
		admin_url( 'nav-menus.php' )
	);

	$state['05_config'][] = amapress_get_check_state(
		! $info_page_menu_item_found ? 'error' : 'success',
		__( 'Entrée de menu - Mes Infos', 'amapress' ),
		__( '<strong>Important</strong> : Créer obligatoirement une entrée dans le menu principal vers la page « Mes Infos » (menu permettant la connexion).', 'amapress' ),
		admin_url( 'nav-menus.php' )
	);

//    $state['05_config'][] = amapress_get_check_state(
//        empty($front_page_logo) ? 'warning' : 'success',
//        __('Logo de la page d\'accueil', 'amapress'),
//        empty($front_page_logo) ?
//            __('Ajouter un logo à la page d\'accueil', 'amapress') :
//            __('Cliquer sur le lien ci-dessus pour éditer la page d\'accueil et son logo', 'amapress'),
//        admin_url('post.php?post=' . $static_front_id . '&action=edit')
//    );

	$state['05_config'][] = amapress_get_check_state(
		'info',
		sprintf( __( 'Choix de la géolocalisation (actuellement %s) et de l\'affichage des cartes (actuellement %s)', 'amapress' ), Amapress::getOption( 'geocode_provider' ), Amapress::getOption( 'map_provider' ) ),
		__( 'Vous pouvez choisir entre Nominatim/Open Street Map et Google Maps pour la géolocalisation et l\'affichage des cartes', 'amapress' ),
		admin_url( 'options-general.php?page=amapress_options_page&tab=amp_google_api_config' )
	);

	if ( 'google' == Amapress::getOption( 'geocode_provider' ) || 'google' == Amapress::getOption( 'map_provider' ) ) {
		$google_key           = Amapress::getOption( 'google_map_key' );
		$state['05_config'][] = amapress_get_check_state(
			! empty( $google_key ) ? 'success' : 'error',
			__( 'Clé API Google', 'amapress' ),
			__( '<strong>Requis</strong> : Une clé Google API est nécessaire pour le bon fonctionnement de la géolocalisation ', 'amapress' ),
			admin_url( 'options-general.php?page=amapress_options_page&tab=amp_google_api_config' )
		);
	}

	if ( 'here' == Amapress::getOption( 'geocode_provider' ) ) {
		$here_map_app_id      = Amapress::getOption( 'here_map_app_id' );
		$google_key           = Amapress::getOption( 'here_map_app_code' );
		$state['05_config'][] = amapress_get_check_state(
			! empty( Amapress::getOption( 'here_map_app_id' ) )
			&& ! empty( Amapress::getOption( 'here_map_app_code' ) ) ? 'success' : 'error',
			'APP ID/APP CODE Here Maps',
			__( '<strong>Requis</strong> : des identifiants APP ID/APP CODE sont nécessaires pour le bon fonctionnement de la géolocalisation ', 'amapress' ),
			admin_url( 'options-general.php?page=amapress_options_page&tab=amp_google_api_config' )
		);
	}

	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Adresse mail du site', 'amapress' ),
		__( 'Configurer l\'adresse email du site (par défaut, "wordpress", actuellement "<strong>', 'amapress' ) . esc_html( Amapress::getOption( 'email_from_mail' ) ) . '</strong>") et son nom d\'affichage (par défaut, le nom du site). Pensez à configurer une redirection pour cette adresse dans la configuration de votre hébergement.',
		admin_url( 'options-general.php?page=amapress_site_options_page&tab=amp_site_mail_config' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Message sur la page de connexion', 'amapress' ),
		__( 'Personnaliser le message qui s\'affiche sur la page de connexion, par exemple, pour rappeler la procédure de récupération de son mot de passe.', 'amapress' ),
		admin_url( 'options-general.php?page=amapress_site_options_page&tab=amp_connection_config#amapress_below_login_message' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Email de bienvenue/demande de récupération mot de passe', 'amapress' ),
		__( 'Ajoutez et personnalisez le mail de bienvenue que chaque amapien reçoit à la création de son compte ou lorsqu\'il demande à récupérer son mot de passe', 'amapress' ),
		admin_url( 'options-general.php?page=amapress_site_options_page&tab=welcome_mail' )
	);

	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Configuration des mailing lists', 'amapress' ),
		sprintf( __( '<p>Si vous avez un accès au système de mailing list (Sympa), par ex Ouvaton, Sud Ouest ou autre fournisseur, 
configurer le mot de passe du listmaster et le domaine de liste <a href="%s">ici</a>.</p>
<p>Créez vos listes depuis l\'interface de Sympa chez votre fournisseur, puis <a href="%s">configurer les membres et modérateurs pour chaque liste</a></p>', 'amapress' ), admin_url( 'admin.php?page=amapress_mailinglist_options_page' ), admin_url( 'edit.php?post_type=amps_mailing' ) ),
		admin_url( 'edit.php?post_type=amps_mailing' )
	);

	$use_mail_queue = Amapress::getOption( 'mail_queue_use_queue' );
	$nb_mails       = __( '"pas de limite" (file désactivée)', 'amapress' );
	if ( $use_mail_queue ) {
		$mail_interval = Amapress::getOption( 'mail_queue_interval' );
		if ( empty( $mail_interval ) ) {
			$mail_interval = AMAPRESS_MAIL_QUEUE_DEFAULT_INTERVAL;
		}
		$mail_limite = Amapress::getOption( 'mail_queue_limit' );
		if ( empty( $mail_limite ) ) {
			$mail_limite = AMAPRESS_MAIL_QUEUE_DEFAULT_LIMIT;
		}
		$mails_hours = $mail_limite / (float) $mail_interval * 3600;
		$nb_mails    = sprintf( __( '%d (max %s emails toute les %ss)', 'amapress' ), $mails_hours, $mail_limite, $mail_interval );
	}
	$state['05_config'][] = amapress_get_check_state(
		$use_mail_queue ? 'success' : 'warning',
		__( 'Configuration de la file d\'envoi des emails sortants', 'amapress' ),
		sprintf( __( '<p>La plupart des hébergeurs ont une limite d\'envoi des emails sortants par heure. Actuellement le site est configuré pour envoyer au maximum %s emails par heure.
<br/>Par défaut, Amapress met les mails dans une file d\'attente avant de les envoyer pour éviter les blocages et rejets de l\'hébergeur. 
<br />Un autre bénéfice est le réessaie d\'envoi en cas d\'erreur temporaire et le logs des emails envoyés par le site pour traçage des activités (pour une durée configurable).</p>', 'amapress' ), $nb_mails ),
		admin_url( 'options-general.php?page=amapress_mailqueue_options_page&tab=amapress_mailqueue_options' )
	);

	$state['05_config'][] = amapress_check_plugin_install( 'pwa', __( 'Progressive Web App', 'amapress' ),
		__( '<strong>Recommandé</strong> : permet au site d\'être vu comme une application mobile et d\'ajouter un raccourci à l\'écran d\'accueil', 'amapress' ),
		'info' );

	$pwa_short_name       = Amapress::getOption( 'pwa_short_name' );
	$state['05_config'][] = amapress_get_check_state(
		'active' === amapress_is_plugin_active( 'pwa' ) ? ( ! empty( $pwa_short_name ) ? 'success' : 'warning' ) : 'info',
		__( 'Configuration Progressive Web App', 'amapress' ),
		__( 'Configurer un nom de raccourci (max 12 caractères), une couleur de thème et un type d\'affichage', 'amapress' ),
		admin_url( 'options-general.php?page=amapress_pwa_options_page' )
	);

	$state['05_config'][] = amapress_check_plugin_install( 'autoptimize', __( 'Autoptimize', 'amapress' ),
		__( '<strong>Recommandé</strong> : permet d\'optimiser la vitesse du site', 'amapress' ),
		'active' === amapress_is_plugin_active( 'pwa' ) ? 'warning' : 'info' );

	$state['05_config'][] = amapress_get_check_state(
		'info',
		__( 'Configuration Autoptimize', 'amapress' ),
		__( 'Configurer l\'optimisation du code Javascript, CSS et HTML', 'amapress' ),
		admin_url( 'options-general.php?page=autoptimize' )
	);

	$state['10_users'] = array();

	$users               = get_users( array( 'role' => 'responsable_amap' ) );
	$state['10_users'][] = amapress_get_check_state(
		count( $users ) == 0 ? 'warning' : 'success',
		__( 'Compte Responsable AMAP', 'amapress' ),
		__( 'Créer les comptes des Responsables de l\'AMAP', 'amapress' ),
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
		__( 'Compte Producteur', 'amapress' ),
		__( 'Créer les comptes des producteurs', 'amapress' ),
		admin_url( 'user-new.php?role=producteur' ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressUser::getBy( $u );
			$l  = admin_url( 'user-edit.php?user_id=' . $dn->getID() . '&wp_http_referer=%2Fwp-admin%2Fusers.php' );

			return "<a href='{$l}' target='_blank'>{$dn->getDisplayName()}</a>";
		}, $prod_users ) )
	);
	$users               = get_users( 'amapress_role=referent_producteur' );
	$state['10_users'][] = amapress_get_check_state(
		count( $users ) == 0 ? 'warning' : 'success',
		__( 'Compte Référent Producteur', 'amapress' ),
		__( 'Créer les comptes des Référents Producteurs', 'amapress' ),
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
		__( 'Création de rôle descriptif des membres du collectif', 'amapress' ),
		__( 'Créer des étiquettes pour les rôles spécifiques des membres du collectif.', 'amapress' ) .
		'<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/roles#roles_specifiques', __( 'Rôles spécifiques', 'amapress' ) ),
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
			'resp-distrib-gardiens-amap-role',
			'resp-visite-amap-role',
			'resp-intermittents-amap-role',
			'resp-amap_event-amap-role',
			Amapress::getOption( 'enable-gardiens-paniers' ) ? 'resp-distrib-gardien-amap-role' : ''
		] as $option
	) {
		if ( ! empty( $option ) && empty( Amapress::getOption( $option ) ) ) {
			$empty_resp_roles = true;
		}
	}
	$state['10_users'][] = amapress_get_check_state(
		count( $amap_roles ) == 0 || $empty_resp_roles ? 'warning' : 'success',
		__( 'Association de rôle descriptif des membres du collectif', 'amapress' ),
		__( 'Associer chaque membre du collectif au rôle spécifique qui lui est attribué dans son compte utilisateur', 'amapress' ) .
		'<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/roles#roles_specifiques', __( 'Rôles spécifiques', 'amapress' ) ),
		admin_url( 'users.php?page=amapress_collectif&tab=amp_amap_roles_config' )
	);

	/** @var WP_User[] $users_no_desc */
	$users_no_desc   = get_users( [
		'amapress_role' => 'collectif_no_amap_role',
	] );
	$members_no_desc = array_map( function ( $user ) {
		$amapien = AmapressUser::getBy( $user );

		return Amapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName() . ' (' . $amapien->getEmail() . ')[' . $amapien->getAmapRolesString() . ']', true, true );
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
			__( 'Membres du collectif sans rôle descriptif', 'amapress' ),
			sprintf( __( '<a target="_blank" href="%s">Associer</a> des rôles descriptifs aux utilisateurs ayant accès au backoffice. (<em>Les administrateurs n\'ont pas forcement besoin de rôle descriptif</em>)', 'amapress' ), admin_url( 'users.php?page=amapress_collectif&tab=amapress_edit_roles_collectif' ) ),
			admin_url( 'users.php?page=amapress_collectif&tab=amapress_edit_roles_collectif' ),
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
			__( 'Membres du collectif sans contrat', 'amapress' ),
			sprintf( __( '<a target="_blank" href="%s">Vérifier</a> les utilisateurs membres du collectif qui n\'ont pas de contrats', 'amapress' ), admin_url( 'users.php?page=amapress_collectif&tab=amapress_edit_roles_collectif' ) ),
			admin_url( 'users.php?amapress_contrat=no&amapress_role=collectif_no_prod' ),
			implode( ', ', $members_no_contrats )
		);
	}

	$state['15_posts'] = array();

	$state['15_posts'][] = amapress_get_check_state(
		'info',
		__( 'Configuration générale de votre AMAP', 'amapress' ),
		'',
		admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page' ),
		__( 'AMAP avec contrat obligatoire/principal', 'amapress' ) . __( ' : ', 'amapress' ) . ( ! Amapress::getOption( 'disable_principal' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Autoriser la co-adhésion partielle', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'allow_partial_coadh' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Rendre le numéro de téléphone mobile obligatoire', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'mob_phone_req' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/referent_producteur/co-panier' ) .
		'<br/>' . __( 'Maximum par défaut de membre(s) du foyer', 'amapress' ) . __( ' : ', 'amapress' ) . Amapress::getOption( 'def_max_cofoy' ) .
		'<br/>' . __( 'Maximum par défaut de co-adhérent(s)', 'amapress' ) . __( ' : ', 'amapress' ) . Amapress::getOption( 'def_max_coadh' ) .
		'<br/>' . __( 'Les co-adhérents doivent avoir une adhésion séparée', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'coadh_self_adh' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'L\'adhésion doit avoir été validée avant de pouvoir s\'inscrire aux contrats', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'check_adh_rcv' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'L\'adhésion ou une adhésion précédente doit avoir été validée avant de pouvoir s\'inscrire aux contrats', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'check_adh_rcv_p' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . sprintf( __( 'Durée de la période de renouvellement : %d jours', 'amapress' ),
			Amapress::getOption( 'renouv_days' ) ) .
		'<br/>' . sprintf( __( 'Décalage de la période d\'adhésion : %d semaine(s)', 'amapress' ),
			Amapress::getOption( 'adhesion_shift_weeks' ) ) .
		'<br/>' . sprintf( __( 'Clôture des inscriptions : %d heure(s) avant le jour de distribution', 'amapress' ),
			Amapress::getOption( 'before_close_hours' ) )

	);

	$lieux               = Amapress::get_lieux();
	$not_localized_lieux = array_filter( $lieux,
		function ( $lieu ) {
			/** @var AmapressLieu_distribution $lieu */
			return ! $lieu->isAdresseLocalized();
		} );
	$state['15_posts'][] = amapress_get_check_state(
		count( $lieux ) == 0 ? 'error' : ( ! empty( $not_localized_lieux ) ? 'warning' : 'success' ),
		__( 'Lieu de distribution', 'amapress' ),
		__( 'Créer au moins un lieu de distribution', 'amapress' ),
		admin_url( 'edit.php?post_type=' . AmapressLieu_distribution::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressLieu_distribution $dn */
			$l = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}' target='_blank'>{$dn->getTitle()}</a>";
		}, $lieux ) ) .
		( ! empty( $not_localized_lieux ) ? '<br /><strong>' . __( 'Lieux non localisés :', 'amapress' ) . '</strong> ' . implode( ', ', array_map( function ( $dn ) {
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
		__( 'Période d\'adhésion', 'amapress' ),
		__( 'Créer une période d\'adhésion pour les cotisations de l\'année en cours', 'amapress' ),
		admin_url( 'edit.php?post_type=' . AmapressAdhesionPeriod::INTERNAL_POST_TYPE ),
		( ! empty( $adh_period ) ? '<a href="' . esc_attr( $adh_period->getAdminEditLink() ) . '" target=\'_blank\'>' . esc_html( $adh_period->getTitle() ) . '</a>' : __( 'Aucune période d\'adhésion', 'amapress' ) )
	);

	$adh_period2 = AmapressAdhesionPeriod::getCurrent( $first_online_date );
	if ( ! $adh_period || ! $adh_period2 || $adh_period2->ID != $adh_period->ID ) {
		$state['15_posts'][] = amapress_get_check_state(
			empty( $adh_period2 ) ? 'error' : 'success',
			__( 'Période d\'adhésion', 'amapress' ),
			__( 'Créer une période d\'adhésion pour les cotisations du début des contrats en ligne', 'amapress' ),
			admin_url( 'edit.php?post_type=' . AmapressAdhesionPeriod::INTERNAL_POST_TYPE ),
			( ! empty( $adh_period2 ) ? '<a href="' . esc_attr( $adh_period2->getAdminEditLink() ) . '" target=\'_blank\'>' . esc_html( $adh_period2->getTitle() ) . '</a>' : __( 'Aucune période d\'adhésion', 'amapress' ) )
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
		__( 'Types de paiement des cotisations', 'amapress' ),
		sprintf( __( 'Créer des <a href="%s" target=\'_blank\'>types de paiement pour le bulletin d\'adhésion à l\'AMAP</a> (par ex "Don à l\'AMAP", "Panier solidaire").', 'amapress' ), admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ) ),
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
		__( 'Producteurs', 'amapress' ),
		__( 'Créer les Producteur correspondant à leur compte utilisateur', 'amapress' ),
		admin_url( 'edit.php?post_type=' . AmapressProducteur::INTERNAL_POST_TYPE ),
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
		__( 'Référents Producteurs', 'amapress' ),
		__( 'Associer le(s) référent(s) producteur pour chacun des producteurs ou productions', 'amapress' ),
		admin_url( 'edit.php?post_type=amps_producteur' ),
		implode( '<br/>', array_map( function ( $u ) {
			$dn = AmapressProducteur::getBy( $u );
			$l  = $dn->getAdminEditLink();

			$no_ref_lieu = [];
			$refs        = [];
			foreach ( $dn->getContrats() as $contrat ) {
				foreach ( Amapress::get_lieux() as $lieu ) {
					$lieu_ref_ids = $contrat->getReferentsIds( $lieu->ID );
					foreach ( $lieu_ref_ids as $referents_id ) {
						$user   = AmapressUser::getBy( $referents_id );
						$refs[] = sprintf( count( $dn->getContrats() ) == 1 ? '%1$s (%3$s)' : '%1$s (%2$s/%3$s)',
							Amapress::makeLink( $user->getEditLink(), $user->getDisplayName(), true, true ),
							Amapress::makeLink( $contrat->getAdminEditLink(), $contrat->getTitle(), true, true ),
							Amapress::makeLink( $lieu->getAdminEditLink(), $lieu->getShortName(), true, true ) );
					}
					if ( empty( $lieu_ref_ids ) ) {
						$no_ref_lieu[] = $lieu;
					}
				}
			}
			$refs = array_unique( $refs );
			if ( empty( $refs ) ) {
				$refs[] = '<strong>' . __( 'Pas de référent', 'amapress' ) . '</strong>';
			}

			$refs = '(' . implode( ', ', $refs );
			if ( ! empty( $no_ref_lieu ) ) {
				$refs .= ' ; ' . implode( ' ; ', array_map( function ( $lieu ) {
						/** @var AmapressLieu_distribution $lieu */
						return '<em>' . sprintf( __( 'Pas de référent à %s', 'amapress' ), Amapress::makeLink( $lieu->getAdminEditLink(), $lieu->getTitle() ) ) . '</em>';
					}, $no_ref_lieu ) );
			}
			$refs .= ')';

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
		__( 'Présentation des productions', 'amapress' ),
		__( 'Créer au moins une production par producteur pour présenter son/ses offre(s)', 'amapress' ),
		admin_url( 'edit.php?post_type=' . AmapressContrat::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $u ) {
			$dn = AmapressContrat::getBy( $u );
			$l  = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );

			return "<a href='{$l}' target='_blank'>{$dn->getTitle()}</a>";
		}, $contrat_types ) ) .
		( ! empty( $not_subscribable_contrat_types ) ? '<p>' . __( '<strong>Les producteurs suivants n\'ont pas de production</strong> : ', 'amapress' ) .
		                                               implode( ', ', array_map( function ( $dn ) {

			                                               $l = admin_url( 'post.php?post=' . $dn->ID . '&action=edit' );
			                                               $t = esc_html( $dn->post_title );

			                                               return "<a href='{$l}' target='_blank'>{$t}</a>";
		                                               }, $not_subscribable_contrat_types ) ) . '</p>' : '' )
	);

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


	$state['15_posts'][] = amapress_get_check_state(
		count( $subscribable_contrat_instances ) == 0 ? 'warning' : ( count( $subscribable_contrat_instances ) < count( $contrat_types ) ? 'warning' : 'success' ),
		__( 'Modèles de contrats', 'amapress' ),
		__( 'Créer au moins un modèle de contrat par contrat pour permettre aux amapiens d\'adhérer', 'amapress' ),
		admin_url( 'edit.php?post_type=' . AmapressContrat_instance::INTERNAL_POST_TYPE ),
		implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressContrat_instance $dn */
			$l      = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
			$tit    = esc_html( $dn->getTitle() );
			$status = AmapressContrats::contratStatus( $dn->getID(), 'span' );

			return "<a href='{$l}' target='_blank'>{$tit}</a> {$status}";
		}, $subscribable_contrat_instances ) ) .
		( ! empty( $not_subscribable_contrat_instances ) ? '<p>' . __( '<strong>Les contrats suivants n\'ont pas de modèles actifs (selon date ouverture/clôture)</strong> : ', 'amapress' ) .
		                                                   implode( ', ', array_map( function ( $dn ) {

			                                                   $l = admin_url( 'post.php?post=' . $dn->ID . '&action=edit' );
			                                                   $t = esc_html( $dn->post_title );

			                                                   return "<a href='{$l}' target='_blank'>{$t}</a>";
		                                                   }, $not_subscribable_contrat_instances ) ) . '</p>' : '' ) .
		( ! empty( $not_active_contrat_instances ) ? '<p>' . __( '<strong>Les contrats suivants n\'ont pas de modèles en cours (selon les dates début/fin)</strong> : ', 'amapress' ) .
		                                             implode( ', ', array_map( function ( $dn ) {

			                                             $l = admin_url( 'post.php?post=' . $dn->ID . '&action=edit' );
			                                             $t = esc_html( $dn->post_title );

			                                             return "<a href='{$l}' target='_blank'>{$t}</a>";
		                                             }, $not_active_contrat_instances ) ) . '</p>' : '' )
	);

	$contrat_to_renew = get_posts( 'post_type=amps_contrat_inst&amapress_date=renew' );
	if ( ! empty( $contrat_to_renew ) ) {
		$state['15_posts'][] = amapress_get_check_state(
			'warning',
			__( 'Contrats à renouveler/clôturer', 'amapress' ),
			__( 'Les contrats suivants sont à renouveler/clôturer pour la saison suivante', 'amapress' ),
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
		Amapress::get_producteurs() as $prod
	) {
		if ( empty( $prod->getUser() ) ) {
			$state['15_posts'][] = amapress_get_check_state(
				'error',
				__( 'Producteur invalide', 'amapress' ),
				sprintf( __( 'Le producteur %s n\'est pas associé à un utilisateur.', 'amapress' ), $prod->getTitle() ),
				$prod->getAdminEditLink()
			);
		}
	}
	foreach (
		AmapressContrats::get_contrats() as $contrat
	) {
		if ( empty( $contrat->getProducteur() ) ) {
			$state['15_posts'][] = amapress_get_check_state(
				'error',
				__( 'Production invalide', 'amapress' ),
				__( 'La production ', 'amapress' ) . $contrat->getTitle() . ' n\'est pas associée à un producteur.',
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
				__( 'Modèle de contrat invalide', 'amapress' ),
				sprintf( __( 'Le modèle de contrat %s n\'est pas associé à une production.', 'amapress' ), $contrat_instance->getTitle() ),
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
			__( 'Producteurs non localisés', 'amapress' ),
			__( 'Les producteurs suivants ne sont pas localisés', 'amapress' ),
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
			__( 'Lieux de distribution non localisés', 'amapress' ),
			__( 'Les lieux de distribution suivants ne sont pas localisés', 'amapress' ),
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
			__( 'Amapiens non localisés', 'amapress' ),
			sprintf( __( '%s amapien(s) ne sont pas localisés', 'amapress' ), $not_localized_amapiens_count ),
			admin_url( 'users.php?amapress_info=address_unk&amapress_contrat=active' )
		);
	}

	$state['15_posts'][] = amapress_get_check_state(
		Amapress::isIntermittenceEnabled() ? 'success' : 'info',
		__( 'Espace intermittents', 'amapress' ),
		'L\'espace intermittent permet aux utilisateurs qui le souhaitent de s\'organiser pour récupérer occasionnellement des paniers des amapiens absents.',
		admin_url( 'admin.php?page=amapress_intermit_conf_opt_page' ),
		__( 'Espace intermittents activé', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::isIntermittenceEnabled() ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Autoriser les amapiens à inscrire des intermittents', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'intermit_self_inscr' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Adhésion obligatoire pour les intermittents', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'intermit_adhesion_req' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Autoriser les la cession partielle de paniers', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'allow_partial_exchange' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . sprintf( __( 'Les cessions de paniers sont clôturées %d heures avant la distribution', 'amapress' ),
			Amapress::getOption( 'close-subscribe-intermit-hours' ) ) .
		'<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/amapien/intermittents' )
	);

	$resp_roles = [];
	for ( $role_ix = 1; $role_ix < 10; $role_ix ++ ) {
		$role_name = Amapress::getOption( "resp_role_$role_ix-name" );
		if ( ! empty( $role_name ) ) {
			$resp_roles[] = sprintf( 'Responsable %d : %s', $role_ix, $role_name );
		}
	}
	if ( empty( $resp_roles ) ) {
		$resp_roles = [ __( 'aucune ou spécifique par lieu de distribution', 'amapress' ) ];
	}
	$state['15_posts'][] = amapress_get_check_state(
		'info',
		__( 'Responsables de distributions', 'amapress' ),
		'',
		admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_tab_role_resp_distrib' ),
		Amapress::makeLink( admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_tab_role_resp_distrib' ), __( 'Tâches des Responsables de distribution', 'amapress' ) ) .
		__( ' : ', 'amapress' ) . implode( ', ', $resp_roles ) .
		'<br/>' . __( 'Autoriser un amapien à s\'inscrire plusieurs fois comme responsable de distribution', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'inscr-distrib-allow-multi' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Autoriser l\'inscription des co-adhérents par l\'adhérent principal', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'inscr-distrib-co-adh' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Autoriser l\'inscription des membres du foyer par l\'adhérent principal', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'inscr-distrib-co-foyer' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_inscr_distrib_options_tab' ), 'Autres configuration pour les responsables de distribution' )
	);

	$state['15_posts'][] = amapress_get_check_state(
		'info',
		__( 'Système de garde de paniers', 'amapress' ),
		'',
		admin_url( 'admin.php?page=amapress_distribs_conf_opt_page&tab=amp_tab_gardiens_paniers_distrib' ),
		__( 'Activer le système de garde de paniers', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'enable-gardiens-paniers' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) ) .
		'<br/>' . __( 'Autoriser les amapiens à choisir directement leur gardien de paniers', 'amapress' ) . __( ' : ', 'amapress' ) . ( Amapress::getOption( 'allow-affect-gardiens' ) ? __( 'oui', 'amapress' ) : __( 'non', 'amapress' ) )
	);

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
		__( 'Pages particulières', 'amapress' ),
		__( 'Configuration des pages particulières (Mes infos, espace intermittents...)', 'amapress' ),
		admin_url( 'options-general.php?page=amapress_options_page&tab=amp_pages_config' )
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
									__( 'Information à compléter', 'amapress' ),
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
							__( 'Information [[à compléter]]', 'amapress' ) . ( ! empty( $option['desc'] ) ? ' : ' . $option['desc'] : '' ),
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
				__( 'Information [[à compléter]] sur la page ', 'amapress' ) . $page->post_title,
				admin_url( 'post.php?post=' . $page->ID . '&action=edit' )
			);
		}
		if ( empty( trim( strip_tags( $page->post_content ) ) ) ) {
			$state['20_content'][] = amapress_get_check_state(
				'warning',
				$page->post_title,
				sprintf( __( 'Compléter le contenu de la page %s', 'amapress' ), $page->post_title ),
				admin_url( 'post.php?post=' . $page->ID . '&action=edit' )
			);
		}
		if ( 'page' != $page->post_type ) {
			$thumb_id = get_post_meta( $page->ID, '_thumbnail_id', true );
			if ( empty( $thumb_id ) ) {
				$state['20_content'][] = amapress_get_check_state(
					'warning',
					$page->post_title,
					sprintf( __( 'Ajouter un logo/image dans "L\'image à la une" de la page %s', 'amapress' ), $page->post_title ),
					admin_url( 'post.php?post=' . $page->ID . '&action=edit' )
				);
			}
		}
	}

	$front_page_edit_href                       = $static_front_id ? admin_url( 'post.php?post=' . $static_front_id . '&action=edit' ) : '';
	$amapien_mes_infos_edit_href                = admin_url( 'post.php?post=' . Amapress::getOption( 'mes-infos-page' ) . '&action=edit' );
	$amapien_mes_paniers_edit_href              = admin_url( 'post.php?post=' . Amapress::getOption( 'mes-paniers-intermittents-page' ) . '&action=edit' );
	$amapien_les_paniers_edit_href              = admin_url( 'post.php?post=' . Amapress::getOption( 'paniers-intermittents-page' ) . '&action=edit' );
	$new_page_href                              = admin_url( 'post-new.php?post_type=page' );
	$new_private_page_href                      = admin_url( 'post-new.php?post_type=page&amps_lo=1' );
	$needed_shortcodes                          = [
		'trombinoscope'                  => [
			'desc'  => __( 'Ajouter une page privée avec le shortcode %s pour afficher le trombinoscope des amapiens', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '3/ Info utiles', 'amapress' ),
		],
		'recettes'                       => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour afficher les recettes', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '1/ Site public', 'amapress' ),
		],
		'produits'                       => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour afficher les produits', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '1/ Site public', 'amapress' ),
		],
		'inscription-distrib'            => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour permettre aux amapiens de s\'inscrire comme responsable de distribution', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '4/ Gestion AMAP', 'amapress' ),
		],
		'echanger-paniers-list'          => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour permettre aux amapiens de proposer leurs paniers en cas d\'absence', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '5/ Espace intermittents', 'amapress' ),
		],
		'intermittents-inscription'      => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour permettre aux amapiens d\'inscrire des intermittents', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '5/ Espace intermittents', 'amapress' ),
		],
		'intermittents-desinscription'   => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour permettre aux intermittents de se désinscrire', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '5/ Espace intermittents', 'amapress' ),
		],
		'amapress-post-its'              => [
			'desc'  => __( 'Ajouter le shortcode %s à la page d\'Accueil pour afficher les post-its de gestion de l\'AMAP', 'amapress' ),
			'href'  => $front_page_edit_href,
			'categ' => __( '2/ Page Accueil - Infos utiles', 'amapress' ),
		],
		'amapien-edit-infos'             => [
			'desc'  => __( 'Ajouter le shortcode %s à la page "Mes infos" pour permettre aux amapiens d\'éditer leur profil', 'amapress' ),
			'href'  => $amapien_mes_infos_edit_href,
			'categ' => __( '4/ Profil amapien', 'amapress' ),
		],
		'mes-contrats'                   => [
			'desc'  => __( 'Ajouter le shortcode %s à une page "Mes contrats" pour permettre aux amapiens de voir leurs inscriptions, de télécharger leurs contrats Word ou de s\'inscrire à de nouveaux contrats en cours de saison', 'amapress' ),
			'href'  => $amapien_mes_infos_edit_href,
			'categ' => __( '4/ Profil amapien', 'amapress' ),
		],
		'amapien-paniers-intermittents'  => [
			'desc'  => __( 'Ajouter le shortcode %s à la page Mes paniers échangés pour afficher "Les paniers que j\'ai proposé"', 'amapress' ),
			'href'  => $amapien_mes_paniers_edit_href,
			'categ' => __( '5/ Espace intermittents', 'amapress' ),
		],
		'les-paniers-intermittents'      => [
			'desc'  => __( 'Ajouter le shortcode %s à la page "Intermittent - Réserver un panier" pour permettre aux intermittents de réserver des paniers', 'amapress' ),
			'href'  => $amapien_les_paniers_edit_href,
			'categ' => __( '5/ Espace intermittents', 'amapress' ),
		],
		'intermittent-paniers'           => [
			'desc'  => __( 'Ajouter le shortcode %s à la page Mes paniers échangés pour afficher "Les paniers que j\'ai réservé"', 'amapress' ),
			'href'  => $amapien_mes_paniers_edit_href,
			'categ' => __( '5/ Espace intermittents', 'amapress' ),
		],
		'amapiens-map'                   => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour afficher la carte des amapiens', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '3/ Info utiles', 'amapress' ),
		],
		'amapiens-role-list'             => [
			'desc'  => __( 'Ajouter une page avec le shortcode %s pour afficher la liste des membres du collectif', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '3/ Info utiles', 'amapress' ),
		],
		'agenda-url'                     => [
			'desc'  => __( 'Ajouter le shortcode %s (ou [agenda-url-button]) à la page Mes infos pour permettre aux amapiens d\'ajouter leur calendrier à leur agenda', 'amapress' ),
			'href'  => $amapien_mes_infos_edit_href,
			'categ' => __( '4/ Profil amapien', 'amapress' ),
		],
		'amapress-amapien-agenda-viewer' => [
			'desc'  => __( 'Ajouter le shortcode %s à une page pour permettre aux amapiens de voir leur calendrier de livraisons/évènements de l\'amap', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '3/ Info utiles', 'amapress' ),
		],
		'nous-contacter'                 => [
			'desc'  => __( 'Ajouter une page Contact avec le shortcode %s', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '1/ Site public', 'amapress' ),
		],
		'front_next_events'              => [
			'desc'  => __( 'Ajouter le shortcode %s à la page d\'Accueil pour afficher le calendrier', 'amapress' ),
			'href'  => $front_page_edit_href,
			'categ' => __( '2/ Page Accueil - Infos utiles', 'amapress' ),
		],
		'front_produits'                 => [
			'desc'  => __( 'Ajouter le shortcode %s à la page d\'Accueil pour afficher les contrats', 'amapress' ),
			'href'  => $front_page_edit_href,
			'categ' => __( '2/ Page Accueil - Infos utiles', 'amapress' ),
		],
		'front_nous_trouver'             => [
			'desc'  => __( 'Ajouter le shortcode %s à la page d\'Accueil pour afficher la carte des lieux de distribution', 'amapress' ),
			'href'  => $front_page_edit_href,
			'categ' => __( '2/ Page Accueil - Infos utiles', 'amapress' ),
		],
		'front_default_grid'             => [
			'desc'  => __( 'Ajouter le shortcode %s à la page d\'Accueil pour afficher le calendrier, les contrats et la carte des lieux de distribution', 'amapress' ),
			'href'  => $front_page_edit_href,
			'categ' => __( '2/ Page Accueil - Infos utiles', 'amapress' ),
		],
		'inscription-en-ligne'           => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page pour permettre aux amapiens de s\'inscrire en ligne aux contrats', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '6/ Inscriptions en ligne', 'amapress' ),
		],
		'amapien-details-paiements'      => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page pour permettre aux amapiens de suivre les règlements attendus et reçus', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '6/ Inscriptions en ligne', 'amapress' ),
		],
		'listes-diffusions'              => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page protégée pour permettre aux amapiens ou au collectif de connaitre les listes de diffusions configurées de votre AMAP', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '3/ Info utiles', 'amapress' ),
		],
		'inscription-visite'             => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page protégée pour permettre aux amapiens de s\'inscrires aux visites aux producteurs', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '8/ Inscriptions', 'amapress' ),
		],
		'amapress-latest-posts'          => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page pour permettre d\'afficher une liste des derniers articles publiés sur le site', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '3/ Info utiles', 'amapress' ),
		],
		'producteur-map'                 => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page pour permettre d\'afficher la carte des producteurs', 'amapress' ),
			'href'  => $new_page_href,
			'categ' => __( '3/ Info utiles', 'amapress' ),
		],
		'resp-distrib-contacts'          => [
			'desc'  => __( 'Ajouter le shortcode %s à la page d\'Accueil pour permettre d\'afficher les contacts des responsables de distribution de la semaine', 'amapress' ),
			'href'  => $front_page_edit_href,
			'categ' => __( '2/ Page Accueil - Infos utiles', 'amapress' ),
		],
		'anon-inscription-distrib'       => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page protégée pour permettre d\'afficher une liste des derniers articles publiés sur le site', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '8/ Inscriptions', 'amapress' ),
		],
		'inscription-amap-event'         => [
			'desc'  => __( 'Ajouter le shortcode %s sur une page protégée pour permettre d\'afficher la page d\'inscription aux évènements', 'amapress' ),
			'href'  => $new_private_page_href,
			'categ' => __( '8/ Inscriptions', 'amapress' ),
		],
	];
	$needed_shortcodes['docspace-responsables'] = [
		'desc'  => __( 'Ajouter le shortcode %s sur une page protégée pour permettre au collectif de partager des fichiers entre les membres du collectif', 'amapress' ),
		'href'  => $new_page_href,
		'categ' => __( '7/ Stockage', 'amapress' ),
	];
	$subfolders                                 = Amapress::getOption( 'docspace_resps_folders' );
	if ( ! empty( $subfolders ) ) {
		$subfolders = trim( str_replace( ' ', '', $subfolders ) );
		foreach ( explode( ',', $subfolders ) as $subfolder ) {
			$needed_shortcodes[ 'docspace-responsables-' . $subfolder ] = [
				'desc'  => sprintf( __( 'Ajouter le shortcode %%s sur une page protégée pour permettre au collectif de partager un sous-dossier "%s" de fichiers entre les membres du collectif', 'amapress' ), $subfolder ),
				'href'  => $new_page_href,
				'categ' => __( '7/ Stockage', 'amapress' ),
			];
		}
	}
	$needed_shortcodes['docspace-amapiens'] = [
		'desc'  => __( 'Ajouter le shortcode %s sur une page protégée pour permettre au collectif de partager des fichiers entre les membres du collectif', 'amapress' ),
		'href'  => $new_page_href,
		'categ' => __( '7/ Stockage', 'amapress' ),
	];
	$subfolders                             = Amapress::getOption( 'docspace_amapiens_folders' );
	if ( ! empty( $subfolders ) ) {
		$subfolders = trim( str_replace( ' ', '', $subfolders ) );
		foreach ( explode( ',', $subfolders ) as $subfolder ) {
			$needed_shortcodes[ 'docspace-amapiens-' . $subfolder ] = [
				'desc'  => sprintf( __( 'Ajouter le shortcode %%s sur une page protégée pour permettre au collectif de partager un sous-dossier "%s" de  fichiers entre les membres du collectif', 'amapress' ), $subfolder ),
				'href'  => $new_page_href,
				'categ' => __( '7/ Stockage', 'amapress' ),
			];
		}
	}
	$needed_shortcodes['docspace-public'] = [
		'desc'  => __( 'Ajouter le shortcode %s sur une page non protégée pour permettre au collectif de partager des fichiers publiquement', 'amapress' ),
		'href'  => $new_page_href,
		'categ' => __( '7/ Stockage', 'amapress' ),
	];
	$subfolders                           = Amapress::getOption( 'docspace_public_folders' );
	if ( ! empty( $subfolders ) ) {
		$subfolders = trim( str_replace( ' ', '', $subfolders ) );
		foreach ( explode( ',', $subfolders ) as $subfolder ) {
			$needed_shortcodes[ 'docspace-public-' . $subfolder ] = [
				'desc'  => sprintf( __( 'Ajouter le shortcode %%s sur une page non protégée pour permettre au collectif de partager un sous-dossier "%s" de fichiers publiquement', 'amapress' ), $subfolder ),
				'href'  => $new_page_href,
				'categ' => __( '7/ Stockage', 'amapress' ),
			];
		}
	}
	$found_shortcodes      = [];
	$found_shortcodes_desc = [];
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
		__( 'Modèles de contrats accessibles en ligne', 'amapress' ),
		__( 'Activer l\'inscription en ligne pour au moins un contrat pour permettre aux amapiens d\'adhérer', 'amapress' ),
		admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ),
		'<strong>' . __( 'Contrats accessibles en ligne :', 'amapress' ) . '</strong> ' . ( count( $online_contrats ) == 0 ? __( 'aucun', 'amapress' ) : implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressContrat_instance $dn */
			$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
			$tit = esc_html( $dn->getTitle() );

			return "<a href='{$l}' target='_blank'>{$tit}</a>";
		}, $online_contrats ) ) ) .
		( count( $not_online_contrats ) > 0 ? '<br /><strong>' . __( 'Contrats non accessibles en ligne :', 'amapress' ) . '</strong> ' . implode( ', ', array_map( function ( $dn ) {
				/** @var AmapressContrat_instance $dn */
				$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
				$tit = esc_html( $dn->getTitle() );

				return "<a href='{$l}' target='_blank'>{$tit}</a>";
			}, $not_online_contrats ) ) : '' )
	);
	$with_word_contrats         = array_filter( $subscribable_contrat_instances, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->canSelfSubscribe() && ! empty( $c->getContratModelDocFileName() );
	} );
	$with_word_contrats_invalid = array_filter( $subscribable_contrat_instances, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->canSelfSubscribe() && ! empty( $c->getContratModelDocFileName() ) && is_array( $c->getContratModelDocStatus() );
	} );
	$without_word_contrats      = array_filter( $subscribable_contrat_instances, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->canSelfSubscribe() && ! $c->getContratWordModelId();
	} );
	$state['26_online_inscr'][] = amapress_get_check_state(
		empty( $with_word_contrats ) ? 'warning' : ( ! empty( $with_word_contrats_invalid ) ? 'error' : 'success' ),
		__( 'Modèles de contrats avec contrat DOCX (Word) associé', 'amapress' ),
		sprintf( __( 'Préparer un contrat papier personnalisé (DOCX) <a target="_blank" href="%s">générique pour tous les contrats de votre AMAP</a> (un pour les contrats à livraison récurrentes et un pour les contrats paniers modulable) ou par modèle de contrat pour permettre aux amapiens d\'imprimer et signer directement leur contrat lors de leur inscription en ligne. <br/>Plusieurs modèles génériques sont téléchargeables <a target="_blank" href="%s">ici</a>. Vous aurez principalement à personnaliser le logo de votre AMAP et les engagements.', 'amapress' ), admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ), esc_attr( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_default_contrat_docx' ) ) ),
		admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ),
		'<strong>' . __( 'Contrats avec Word attaché :', 'amapress' ) . '</strong> ' . ( count( $online_contrats ) == 0 ? __( 'aucun', 'amapress' ) : implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressContrat_instance $dn */
			$l           = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
			$tit         = esc_html( $dn->getTitle() );
			$status      = $dn->getContratModelDocStatus();
			$status_text = '';
			if ( is_array( $status ) ) {
				$status_text = ' (<span class="' . $status['status'] . '">' . esc_html( $status['message'] ) . '</span>)';
			}

			return "<a href='{$l}' target='_blank'>{$tit}{$status_text}</a>";
		}, $with_word_contrats ) ) ) .
		( count( $without_word_contrats ) > 0 ? '<br /><strong>' . __( 'Contrats sans Word attaché :', 'amapress' ) . '</strong> ' . implode( ', ', array_map( function ( $dn ) {
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
		__( 'Configuration du webservice de conversion DOCX vers PDF (et autres services)', 'amapress' ),
		__( 'Un webservice de conversion DOCX vers PDF est nécessaire afin que les amapiens recoivent leur contrat en PDF et non en DOCX.<br/>Vous pouvez faire une <a href="mailto:contact.amapress@gmail.com">demande de code d\'accès</a> au webservice mis en place par l\'équipe Amapress. Ce WebService pourra également fournir d\'autres services, tels que la réduction de poids de PDF.', 'amapress' ),
		admin_url( 'options-general.php?page=amapress_options_page&tab=amp_convertws_config' )
	);


	$stripe_online_contrats     = array_filter( $online_contrats, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return $c->getAllow_Stripe();
	} );
	$not_stripe_online_contrats = array_filter( $online_contrats, function ( $c ) {
		/** @var AmapressContrat_instance $c */
		return ! $c->getAllow_Stripe();
	} );
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		__( 'Modèles de contrats avec paiement en ligne', 'amapress' ),
		__( 'Le paiement en ligne via Stripe permet aux amapiens de régler leurs inscriptions avec suivi automatique du paiement', 'amapress' ),
		admin_url( 'edit.php?post_type=amps_contrat_inst&amapress_date=active' ),
		'<strong>' . __( 'Contrats avec paiement en ligne actif :', 'amapress' ) . '</strong> ' . ( count( $stripe_online_contrats ) == 0 ? __( 'aucun', 'amapress' ) : implode( ', ', array_map( function ( $dn ) {
			/** @var AmapressContrat_instance $dn */
			$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
			$tit = esc_html( $dn->getTitle() );

			return "<a href='{$l}' target='_blank'>{$tit}</a>";
		}, $online_contrats ) ) ) .
		( count( $not_stripe_online_contrats ) > 0 ? '<br /><strong>' . __( 'Contrats sans paiement en ligne :', 'amapress' ) . '</strong> ' . implode( ', ', array_map( function ( $dn ) {
				/** @var AmapressContrat_instance $dn */
				$l   = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
				$tit = esc_html( $dn->getTitle() );

				return "<a href='{$l}' target='_blank'>{$tit}</a>";
			}, $not_stripe_online_contrats ) ) : '' )
	);

	$adh_period  = AmapressAdhesionPeriod::getCurrent( $first_online_date );
	$status      = $adh_period ? $adh_period->getModelDocStatus() : true;
	$status_text = '';
	if ( true !== $status ) {
		$status_text = ' (<span class="ph-check-' . $status['status'] . '">' . esc_html( $status['message'] ) . '</span>)';
	}
	$state['26_online_inscr'][] = amapress_get_check_state(
		empty( $adh_period ) ? 'error' : ( ! defined( 'AMAPRESS_DEMO_MODE' ) && ! $adh_period->getWordModelId() ? 'warning' :
			( ! empty( $status_text ) ? 'error' : 'success' ) ),
		__( 'Période d\'adhésion', 'amapress' ),
		sprintf( __( 'Créer une période d\'adhésion au %s pour les adhésions en ligne et attaché lui un bulletin d\'adhésion en Word', 'amapress' ), date_i18n( 'd/m/Y', $first_online_date ) ),
		$adh_period ? $adh_period->getAdminEditLink() : admin_url( 'edit.php?post_type=' . AmapressAdhesionPeriod::INTERNAL_POST_TYPE ),
		( ! empty( $adh_period ) ? '<a href="' . esc_attr( $adh_period->getAdminEditLink() ) . '" target=\'_blank\'>' . esc_html( $adh_period->getTitle() ) . $status_text . '</a>' : __( 'Aucune période d\'adhésion', 'amapress' ) )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		$adh_period && ! empty( $adh_period->getHelloAssoFormUrl() ) ? 'success' : 'info',
		'Formulaire HelloAsso - Adhésion avec paiement ligne',
		$adh_period && ! empty( $adh_period->getHelloAssoFormUrl() ) ?
			sprintf( 'Vos adhésions pour la période "%s" sont effectuées via le formulaire HelloAsso suivant : <a href="%s">%s</a>',
				$adh_period->getTitle(), $adh_period->getHelloAssoFormUrl(), $adh_period->getHelloAssoFormUrl() ) :
			'Effectuer vos adhésions avec paiement en ligne via un formulaire d\'adhésion HelloAsso. ',
		$adh_period ? $adh_period->getAdminEditLink() : admin_url( 'edit.php?post_type=' . AmapressAdhesionPeriod::INTERNAL_POST_TYPE ),
		Amapress::makeInternalLink( admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=amp_helloasso_config' ), 'Configuration de l\'intégration HelloAsso' ) .
		'<br/>' . Amapress::makeWikiLink( 'https://wiki.amapress.fr/admin/helloasso' )
	);
	$type_paiements             = get_categories( array(
		'orderby'    => 'name',
		'order'      => 'ASC',
		'taxonomy'   => 'amps_paiement_category',
		'hide_empty' => false,
	) );
	$amap_term_id               = intval( Amapress::getOption( 'adhesion_amap_term' ) );
	$amap_term                  = null;
	$reseau_amap_term_id        = intval( Amapress::getOption( 'adhesion_reseau_amap_term' ) );
	$reseau_amap_term           = null;
	foreach ( $type_paiements as $term ) {
		if ( $term->term_id == $amap_term_id ) {
			$amap_term = $term;
		}
		if ( $term->term_id == $reseau_amap_term_id ) {
			$reseau_amap_term = $term;
		}
	}
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		__( 'Types de cotisation/paiement', 'amapress' ),
		__( 'Créer des types de cotisations : adhésion à l\'AMAP, adhésion au réseau des AMAP, panier solidaire, don...', 'amapress' ),
		admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ),
		implode( ', ', array_map( function ( $t ) {
			/** @var WP_Term $t */
			$ret = '';
			if ( ! empty( $t->description ) ) {
				$ret = "{$t->name} ({$t->description})";
			} else {
				$ret = $t->name;
			}

			return Amapress::makeLink( admin_url( 'edit-tags.php?taxonomy=amps_paiement_category' ), $ret );
		}, $type_paiements ) )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		$amap_term ? 'success' : 'warning',
		__( 'Types de cotisation : adhésion à l\'AMAP', 'amapress' ),
		__( 'Associer un type de cotisation pour l\'adhésion à l\'AMAP', 'amapress' ),
		admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=amp_paiements_config' ),
		$adh_period && $amap_term ? sprintf( __( 'Pour %s, le montant \'%s\' est de %s€', 'amapress' ), $adh_period->getTitle(), $amap_term->name, Amapress::formatPrice( $adh_period->getMontantAmap() ) ) : __( 'Pas de période d\'adhésion en cours', 'amapress' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		$reseau_amap_term ? 'success' : 'warning',
		__( 'Types de cotisation : adhésion au réseau AMAP', 'amapress' ),
		__( 'Associer un type de cotisation pour l\'adhésion au réseau AMAP', 'amapress' ),
		admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=amp_paiements_config' ),
		$adh_period && $reseau_amap_term ? sprintf( __( 'Pour %s, le montant \'%s\' est de %s€', 'amapress' ), $adh_period->getTitle(), $reseau_amap_term->name, Amapress::formatPrice( $adh_period->getMontantReseau() ) ) : __( 'Pas de période d\'adhésion en cours', 'amapress' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		isset( $needed_shortcodes['inscription-en-ligne'] ) ? 'warning' : 'success',
		__( 'Ajouter le shortcode [inscription-en-ligne] pour permettre aux amapiens de s\'inscrire en ligne.', 'amapress' ),
		__( 'Ce shortcode nécessite une clé de sécurité afin que seule les personnes à qui vous avez transmis le lien puissent s\'inscrire', 'amapress' ),
		isset( $needed_shortcodes['inscription-en-ligne'] ) ? admin_url( 'post-new.php?post_type=page' ) : admin_url( 'post.php?post=' . $found_shortcodes['inscription-en-ligne']->ID . '&action=edit' ),
		sprintf( __( 'Par exemple : [inscription-en-ligne key=%s%s email=contact@%s]', 'amapress' ), uniqid(), uniqid(), Amapress::getSiteDomainName( true ) )
	);
	$assistant_inscr_conf_url   = admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' );
	$assistant_adh_conf_url     = admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_messages' );
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		__( 'Réglage de l\'étape "Réglement AMAP" et autres réglages de l\'assistant adhésion/inscription en ligne', 'amapress' ),
		__( 'Si vous souhaitez inclure une étape "Règlement de l\'AMAP" préalable à l\'inscription aux contrats, saisir le titre de l\'étape et le règlement <a href="', 'amapress' ) . $assistant_adh_conf_url . '" target="_blank">ici</a>, puis ajouter "agreement=true dans le shortcode [inscription-en-ligne] ou [adhesion-en-ligne]".',
		$assistant_adh_conf_url
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		__( 'Autres réglages de l\'assistant adhésions en ligne', 'amapress' ),
		__( 'Vous pouvez y configurer les messages de certaines étapes.', 'amapress' ),
		$assistant_adh_conf_url
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		__( 'Autres réglages de l\'assistant inscription en ligne', 'amapress' ),
		__( 'Vous pouvez y configurer les messages de certaines étapes.', 'amapress' ),
		$assistant_inscr_conf_url
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		__( 'Emails envoyés par l\'assistant adhésion en ligne', 'amapress' ),
		__( 'Vous pouvez y configurer les mails de confirmation.', 'amapress' ),
		admin_url( 'admin.php?page=amapress_gest_adhesions_conf_opt_page&tab=config_online_adhesions_mails' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		'info',
		__( 'Emails envoyés par l\'assistant inscription en ligne', 'amapress' ),
		__( 'Vous pouvez y configurer les mails de confirmation.', 'amapress' ),
		admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_mails' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		isset( $needed_shortcodes['amapien-edit-infos'] ) ? 'warning' : 'success',
		__( 'Ajouter le shortcode [amapien-edit-infos] pour permettre aux amapiens de modifier leurs coordonnées.', 'amapress' ),
		__( 'Typiquement sur la page Mes infos', 'amapress' ),
		isset( $needed_shortcodes['amapien-edit-infos'] ) ? $amapien_mes_infos_edit_href : admin_url( 'post.php?post=' . $found_shortcodes['amapien-edit-infos']->ID . '&action=edit' )
	);
	$state['26_online_inscr'][] = amapress_get_check_state(
		isset( $needed_shortcodes['mes-contrats'] ) ? 'warning' : 'success',
		__( 'Ajouter le shortcode [mes-contrats] pour permettre aux amapiens de voir leurs inscriptions.', 'amapress' ),
		__( 'Ce shortcode permet aussi aux amapiens de s\'inscrire à d\'autres contrats en cours d\'année', 'amapress' ),
		isset( $needed_shortcodes['mes-contrats'] ) ? admin_url( 'post-new.php?post_type=page' ) : admin_url( 'post.php?post=' . $found_shortcodes['mes-contrats']->ID . '&action=edit' ),
		sprintf( __( 'Par exemple : [mes-contrats email=contact@%s]', 'amapress' ), Amapress::getSiteDomainName( true ) )
	);

	$state['30_recalls'] = array();
	$adm_menu            = AmapressEntities::getMenu();
	foreach ( AmapressEntities::getMenu() as $item ) {
		if ( ! empty( $item['subpages'] ) ) {
			$adm_menu = array_merge( $adm_menu, $item['subpages'] );
		}
	}
	foreach ( $adm_menu as $item ) {
		if ( ! empty( $item['settings']['name'] ) && isset( $item['id'] ) ) {
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
		__( 'Amapiens', 'amapress' ),
		__( 'Importer des amapiens à partir d\'un fichier Excel.', 'amapress' ),
		admin_url( 'admin.php?page=amapress_import_page&tab=import_users_tab' )
	);
	$state['35_import'][] = amapress_get_check_state(
		count( $active_contrat_instances ) == 0 ? 'error' : 'do',
		__( 'Adhésions', 'amapress' ),
		count( $active_contrat_instances ) == 0 ? __( 'Vous devez créer au moins un modèle de contrat pour importer les inscriptions', 'amapress' ) : __( 'Importer des inscriptions à partir d\'un fichier Excel.', 'amapress' ),
		admin_url( 'admin.php?page=amapress_import_page&tab=import_adhesions_tab' )
	);

	$state['36_mailing']   = array();
	$state['36_mailing'][] = amapress_get_check_state(
		'do',
		__( 'Emails groupés - Gestion interne de listes de diffusions basées sur des comptes emails de l\'hébergement', 'amapress' ),
		__( 'Créez des comptes emails sur votre hébergement et configurez les en tant que <a target="_blank" href="https://wiki.amapress.fr/admin/email_groupe">listes de diffusions gérées par le site</a>. Configurez et gérez leurs modérations, leurs membres directement depuis le Tableau de bord.<br/> 
NB : ne pas récupérer les emails reçus sur ces comptes sans quoi le système de gestion ne les verrait pas.', 'amapress' ),
		admin_url( 'admin.php?page=amapress_gestion_mailinggroup_page' ),
		implode( ', ', array_map( function ( $ml ) {
			/** @var AmapressMailingGroup $ml */
			return Amapress::makeLink( $ml->getAdminEditLink(), $ml->getName(), true, true );
		}, AmapressMailingGroup::getAll() ) )
	);
	$should_use_smtp       = array_filter( AmapressMailingGroup::getAll(), function ( $ml ) {
		/* @var AmapressMailingGroup $ml */
		return $ml->shouldUseSmtp();
	} );
	if ( ! empty( $should_use_smtp ) ) {
		$state['36_mailing'][] = amapress_get_check_state(
			'warning',
			__( 'Emails groupés - SMTP recommandé', 'amapress' ),
			__( 'Ces Emails groupés devraient être configuré pour utiliser le SMTP du compte IMAP qu\'ils relayent', 'amapress' ),
			admin_url( 'admin.php?page=amapress_gestion_mailinggroup_page' ),
			implode( ', ', array_map( function ( $ml ) {
				/** @var AmapressMailingGroup $ml */
				return Amapress::makeLink( $ml->getAdminEditLink(), $ml->getName(), true, true );
			}, $should_use_smtp ) )
		);
	}
	$state['36_mailing'][] = amapress_get_check_state(
		'do',
		__( 'Liste de diffusions - Gestion externe sur un service Sympa (Sud-Ouest2.org, Ouvaton...) ', 'amapress' ),
		__( 'Configurez vos différentes listes Sympa, leurs modérations et leurs membres depuis le Tableau de bord', 'amapress' ),
		admin_url( 'edit.php?post_type=amps_mailing' ),
		implode( ', ', array_map( function ( $ml ) {
			/** @var Amapress_MailingListConfiguration $ml */
			return Amapress::makeLink( $ml->getAdminEditLink(), $ml->getName(), true, true );
		}, Amapress_MailingListConfiguration::getAll() ) )
	);

	$state['37_plugins_add']   = array();
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'tinymce-advanced', __( 'Advanced Editor Tools (previously TinyMCE Advanced)', 'amapress' ),
		__( '<strong>Recommandé</strong> : Enrichi l\'éditeur de texte intégré de Wordpress afin de faciliter la création de contenu sur le site', 'amapress' ),
		'warning' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'block-options', __( 'Block Options/Gutenberg Page Building Toolkit – EditorsKit', 'amapress' ),
		__( '<strong>Optionnel</strong> :  permet d\'ajouter des <a href="https://wordpress.org/plugins/block-options/" target="_blank">fonctionnalités</a> (enrichissements, markdown, visibilité des blocs, temps de lecture...) dans <a href="https://wpformation.com/gutenberg-wordpress-mode-emploi/" target="_blank">l\'éditeur des articles et pages (Gutenberg)</a>', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'count-per-day', __( 'Count Per Day', 'amapress' ),
		__( '<strong>Optionnel</strong> : Permet d\'obtenir des statistiques de visites journalières simples sans recourir à des moteurs de statistiques externes.', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'icalendrier', 'iCalendrier',
		__( '<strong>Optionnel</strong> : Affiche la date du jour avec la fête du jour et les phases de la lune', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'latest-post-shortcode', __( 'Latest Post Shortcode', 'amapress' ),
		__( '<strong>Optionnel</strong> : Permet de créér une gallerie des articles récents (par ex, pour donner des nouvelles de l\'AMAP sur la page d\'Acceuil', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'feed-them-social', __( 'Feed Them Social', 'amapress' ),
		__( '<strong>Optionnel</strong> : Permet d\'afficher le flux d\'actualité d\'une page Facebook/Twitter/Instagram..., par exemple, la page Facebook de votre AMAP.', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'external-media', __( 'External Media', 'amapress' ),
		__( '<strong>Optionnel</strong> : Permet de référencer des documents accessibles sur GoogleDrive, OneDrive, DropBox sans les importer via la «Media Library » de Wordpress', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'bbpress', 'bbPress',
		__( '<strong>Optionnel</strong> : Permet de gérer un forum (avec toutes ses fonctionnalités) sur le site.', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'email-subscribers', __( 'Email Subscribers & Newsletters', 'amapress' ),
		__( '<strong>Optionnel</strong> : permet aux amapiens d\'être notifiés des nouveaux articles ; permet de générer une newsletter avec le contenu récemment mis à jour', 'amapress' ),
		'info' );
	$state['37_plugins_add'][] = amapress_check_plugin_install( 'ml-slider', __( 'Meta Slider', 'amapress' ),
		__( '<strong>Optionnel</strong> : permet de générer un carrousel/slider de contenu sur votre site, par exemple avec les dernières news sur la page d\'accueil', 'amapress' ),
		'info' );

	$state['38_plugins_adv']   = array();
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'wp-mail-smtp', __( 'WP Mail SMTP', 'amapress' ),
		__( '<strong>Envoi de mail</strong> : Utilisation avancée, permet une configuration avancée de l\'envoi de mails par le site (Gmail OAuth, SendGrid..)', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'wordpress-seo', __( 'Yoast SEO', 'amapress' ),
		__( '<strong>SEO Avancé</strong> : Utilisation avancée, améliore le référencement du site. Ce plugin ajoute de nombreuse options dans le back-office, à installer par un webmaster.', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'gdpr', 'GDPR Avancé/Professionel',
		__( '<strong>GPRD Avancée</strong> : Utilisation avancée, suite d\'outils relatifs à la réglementation européenne RGPD sur la protection des données.', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'imsanity', __( 'Imsanity', 'amapress' ),
		__( '<strong>Optimisation</strong> : permet d’optimiser le poids des images dans la « Media Library » de Wordpress. Ce plugin est à installer par un webmaster. ', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'wp-sweep', 'WP Sweep',
		__( '<strong>Optimisation</strong> : permet de nettoyer et optimiser la base de données de votre site. <strong>Pensez à faire une sauvegarde avant son utilisation.</strong>', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'stops-core-theme-and-plugin-updates', __( 'Easy Updates Manager', 'amapress' ),
		__( '<strong>Optimisation</strong> : permet de mettre à jour Wordpress, les Extensions et les Thèmes de manière automatique (avec lancement d\'Updraft Plus au préalable)', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'media-cleaner', __( 'Media Cleaner', 'amapress' ),
		__( '<strong>Optimisation</strong> : permet de nettoyer les fichiers média orphelins pour libérer de l\'espace sur votre hébergement. <strong>Pensez à faire une sauvegarde avant son utilisation.</strong>', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'classic-editor', __( 'Classic Editor', 'amapress' ),
		__( '<strong>Avancé</strong> : permet de restaurer <a href="https://wordpress.org/plugins/classic-editor/" target="_blank">l\'éditeur classique</a> de Wordpress, remplacé par <a href="https://wpformation.com/gutenberg-wordpress-mode-emploi/" target="_blank">l\'éditeur Gutenberg</a> depuis la version 5', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'error-log-monitor', __( 'Error Log Monitor', 'amapress' ),
		__( '<strong>Dev/Debug</strong> : Permet de logger les erreurs PHP/Wordpress et de les envoyer automatiquement au support Amapress pour aider à son développement', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'query-monitor', __( 'Query Monitor', 'amapress' ),
		__( '<strong>Dev/Debug</strong> : permet d\'analyser les performances du site pour aider Amapress à son développement', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'wp-crontrol', __( 'Wp Crontrol', 'amapress' ),
		__( '<strong>Dev/Debug</strong> : permet de voir et lancer manuellement les tâches planifiées de WordPress', 'amapress' ),
		'info' );
	$state['38_plugins_adv'][] = amapress_check_plugin_install( 'secupress', __( 'SecuPress Free', 'amapress' ),
		__( '<strong>Avancé</strong> : permet de scanner, vérifier et corriger la sécurité de votre installation WordPress', 'amapress' ),
		'info' );

	$clean_messages = '';
	if ( isset( $_REQUEST['clean'] ) ) {
		if ( 'orphans' == $_REQUEST['clean'] ) {
			$clean_messages = implode( '<br />', [
				AmapressAmapien_paiement::cleanOrphans(),
				AmapressContrat_quantite::cleanOrphans(),
				AmapressDistribution::cleanOrphans(),
			] );
		}
	}
	$state['40_clean']   = array();
	$state['40_clean'][] = amapress_get_check_state(
		'do',
		__( 'Nettoyer les éléments orphelins', 'amapress' ),
		'<p>' . __( 'Permet de nettoyer la base de donnée', 'amapress' ) . '</p>
<p style="font-family: monospace">' . $clean_messages . '</p>',
		admin_url( 'admin.php?page=amapress_state&clean=orphans' ),
		null,
		false
	);

	return $state;
}

function amapress_get_updraftplus_backup_last_backup_date() {
	$updraft_last_backup = maybe_unserialize( get_option( 'updraft_last_backup' ) );
	if ( ! empty( $updraft_last_backup ) ) {
		if ( is_array( $updraft_last_backup ) && ! empty( $updraft_last_backup['backup_time'] ) ) {
			return date_i18n( 'd/m/Y H:i:s', $updraft_last_backup['backup_time'] );
		} else {
			return __( 'Date inconnue', 'amapress' );
		}
	}

	return __( 'Jamais', 'amapress' );
}

function amapress_get_updraftplus_backup_intervals() {
	$updraft_interval          = get_option( 'updraft_interval' );
	$updraft_interval_database = get_option( 'updraft_interval_database' );

	return sprintf( __( 'Fichiers: %s ; DB: %s', 'amapress' ), ! empty( $updraft_interval ) ? $updraft_interval : 'manuel', ! empty( $updraft_interval_database ) ? $updraft_interval_database : 'manuel' );
}

function amapress_get_updraftplus_backup_status() {
	if ( 'active' === amapress_is_plugin_active( 'updraftplus' ) ) {
		$updraft_interval          = get_option( 'updraft_interval' );
		$updraft_interval_database = get_option( 'updraft_interval_database' );
		if ( empty( $updraft_interval ) || empty( $updraft_interval_database ) ) {
			return 'inactive';
		}
		$updraft_service = maybe_unserialize( get_option( 'updraft_service' ) );
		if ( empty( $updraft_service ) ) {
			return 'local';
		} else {
			return is_array( $updraft_service ) ? implode( ',', $updraft_service ) : $updraft_service;
		}
	} else {
		return 'inactive';
	}
}

function amapress_has_akismet_api_key() {
	if ( is_callable( array( __( 'Akismet', 'amapress' ), 'get_api_key' ) ) ) {
		// Akismet v3.0+
		return (bool) Akismet::get_api_key();
	}
	if ( function_exists( 'akismet_get_key' ) ) {
		return (bool) akismet_get_key();
	}

	return false;
}

function amapress_check_ssl_in_content() {
	include AMAPRESS__PLUGIN_DIR . '/utils/srdb.class.php';

	global $wpdb;
	$host    = $wpdb->parse_db_host( DB_HOST );
	$siteurl = get_option( 'siteurl' );

	$tables = $wpdb->get_col( 'SHOW TABLES' );
	$tables = array_filter( $tables, function ( $t ) use ( $wpdb ) {
		return 0 === strpos( $t, $wpdb->prefix );
	} );
	if ( empty( $tables ) ) {
		echo '<p style="color: red">' . __( 'Impossible de trouver les tables Wordpress', 'amapress' ) . '</p>';

		return;
	}

	$http_siteurl  = str_replace( 'https://', 'http://', $siteurl );
	$https_siteurl = str_replace( 'http://', 'https://', $siteurl );
	$args          = [
		'name'         => DB_NAME,
		'user'         => DB_USER,
		'pass'         => DB_PASSWORD,
		'host'         => $host[0],
		'port'         => $host[1],
		'search'       => $http_siteurl,
		'replace'      => $https_siteurl,
		'tables'       => $tables,
		'exclude_cols' => 'guid',
		'dry_run'      => true,
	];
	$srdb          = new amps_icit_srdb( $args );
	$changes       = $srdb->report['change'];


	echo '<h4>' . __( 'Changement de contenu de la base de donnée pour passer entièrement en HTTPS', 'amapress' ) . '</h4>';

	if ( 0 == $changes ) {
		echo '<p style="color:green">' . sprintf( __( 'Tous le contenu de votre site référence déjà "%s"', 'amapress' ), $https_siteurl ) . '</p>';
	} else {
		echo '<p>' . sprintf( __( 'Pour passer totalement l\'adresse de votre site de "%s" à <strong>"%s"</strong>, <strong>%d</strong> changement(s) sont nécessaires dans le contenu de la base de données Wordpress.', 'amapress' ),
				$http_siteurl, $https_siteurl, $changes ) . '</p>';
		$replace_tables = array_filter( $srdb->report['table_reports'], function ( $t ) {
			return $t['change'] > 0;
		} );
		echo '<p>' . sprintf( __( 'Tables concernées: %s', 'amapress' ),
				implode( ', ', array_map( function ( $k, $v ) {
					return sprintf( __( '%s <strong>(%d)</strong>', 'amapress' ), $k, $v['change'] );
				}, array_keys( $replace_tables ), array_values( $replace_tables ) ) ) ) . '</p>';

		$link = ( 'active' == amapress_is_plugin_active( 'updraftplus' ) ? admin_url( 'options-general.php?page=updraftplus' ) : '' );
		if ( empty( $link ) ) {
			$link = __( 'sauvegarde (UpdraftPlus n\'est pas installé !)', 'amapress' );
		} else {
			$link = Amapress::makeLink( $link, __( 'sauvergarde UpdraftPlus', 'amapress' ), true, true );
		}
		echo '<p style="color: red; font-weight: bold">' . sprintf( __( 'Veuillez effectuer une %s de la base de donnée de Wordpress avant d\'effectuer le remplacement de contenu !', 'amapress' ), $link ) . '</p>';

		echo '<p>' . Amapress::makeButtonLink( wp_nonce_url( add_query_arg( 'action', 'update_siteurl' ), 'update_siteurl' ),
				sprintf( __( 'Mettre à jour les liens %s en <strong>%s</strong>', 'amapress' ), $http_siteurl, $https_siteurl ), false ) . '</p>';
	}
	if ( isset( $_GET['action'] ) && 'update_siteurl' == $_GET['action'] ) {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'update_siteurl' ) ) {
			die( 'Invalid nonce' );
		}

		$args['dry_run'] = false;
		$srdb            = new amps_icit_srdb( $args );
		$changes         = $srdb->report['change'];
		$updates         = $srdb->report['updates'];
		$errors          = $srdb->report['errors'];

		echo '<p>' . sprintf( __( 'Toutes les références à "%s" ont été passées en <strong>"%s"</strong>, <strong>%d</strong> mises à jour sur <strong>%d</strong> changements ont été effectués', 'amapress' ),
				$http_siteurl, $https_siteurl, $updates, $changes ) . '</p>';

		if ( ! empty( $errors ) ) {
			echo '<p style="color:red">' . __( 'Des erreurs sont survenues:', 'amapress' ) . '<br/>' . amapress_dump( $errors ) . '</p>';
		}

		echo '<p>' . Amapress::makeButtonLink( remove_query_arg( [ 'action', '_wpnonce' ] ),
				__( 'Revérifier', 'amapress' ) ) . '</p>';
	}
}

if ( defined( 'AMAPRESS_DEMO_MODE' ) ) {
	function amapress_parse_bounce_mail() {
		require_once AMAPRESS__PLUGIN_DIR . 'modules/bounceparser/BounceStatus.php';
		require_once AMAPRESS__PLUGIN_DIR . 'modules/bounceparser/BounceHandler.php';

		if ( ! isset( $_REQUEST['raw'] ) ) {
			echo '<form method="post">
<input name="bounce_parser" type="hidden" value="T" />
<label>' . __( 'Mail de bounce:', 'amapress' ) . ' <textarea name="raw" cols="80" rows="100"></textarea></label>
<br/>
<input type="submit" value="' . __( 'Parse', 'amapress' ) . '" />
</form>';
		} else {
			$bounce_handler      = new rambomst\PHPBounceHandler\BounceHandler();
			$raw_email           = wp_unslash( $_REQUEST['raw'] );
			$raw_email           = preg_replace( '/(?<!\r)\n/', "\r\n", $raw_email );
			$parsed_bounce_email = $bounce_handler->parseEmail( $raw_email );

			amapress_dump( $parsed_bounce_email );
		}
	}
}
function amapress_embedded_phpinfo() {
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

### Function:Get MYSQL Query Cache Size
function amapress_get_mysql_query_cache_size() {
	global $wpdb;
	$query_cache_size_query = $wpdb->get_row( "SHOW VARIABLES LIKE 'query_cache_size'" );

	return $query_cache_size_query->Value;
}

### Function: Get MYSQL Version
function amapress_get_mysql_version() {
	global $wpdb;

	return $wpdb->get_var( "SELECT VERSION() AS version" );
}

### Function: Get MYSQL Data Usage
function amapress_get_mysql_data_usage() {
	global $wpdb;
	$data_usage   = 0;
	$tablesstatus = amapress_get_results_cached( "SHOW TABLE STATUS" );
	foreach ( $tablesstatus as $tablestatus ) {
		$data_usage += $tablestatus->Data_length;
	}

	return $data_usage;
}

### Function: Get MYSQL Index Usage
function amapress_get_mysql_index_usage() {
	global $wpdb;
	$index_usage  = 0;
	$tablesstatus = amapress_get_results_cached( "SHOW TABLE STATUS" );
	foreach ( $tablesstatus as $tablestatus ) {
		$index_usage += $tablestatus->Index_length;
	}

	return $index_usage;
}

### Function: PHP Memory Limit
function amapress_get_php_memory_limit() {
	if ( ini_get( 'memory_limit' ) ) {
		$memory_limit = ini_get( 'memory_limit' );
	} else {
		$memory_limit = __( 'N/A', 'amapress' );
	}

	return $memory_limit;
}

### Function: PHP Maximum Execution Time
function amapress_get_php_max_execution() {
	if ( ini_get( 'max_execution_time' ) ) {
		$max_execute = ini_get( 'max_execution_time' );
	} else {
		$max_execute = __( 'N/A', 'amapress' );
	}

	return $max_execute;
}

### Function: Get PHP Max Post Size
function amapress_get_php_post_max() {
	if ( ini_get( 'post_max_size' ) ) {
		$post_max = ini_get( 'post_max_size' );
	} else {
		$post_max = __( 'N/A', 'amapress' );
	}

	return $post_max;
}

### Function: Get PHP Max Upload Size
function amapress_get_php_upload_max() {
	if ( ini_get( 'upload_max_filesize' ) ) {
		$upload_max = ini_get( 'upload_max_filesize' );
	} else {
		$upload_max = __( 'N/A', 'amapress' );
	}

	return $upload_max;
}

### Function: Format Bytes Into TiB/GiB/MiB/KiB/Bytes
function amapress_format_filesize( $rawSize ) {
	if ( $rawSize / 1099511627776 > 1 ) {
		return number_format_i18n( $rawSize / 1099511627776, 1 ) . ' ' . __( 'TB', 'amapress' );
	} elseif ( $rawSize / 1073741824 > 1 ) {
		return number_format_i18n( $rawSize / 1073741824, 1 ) . ' ' . __( 'GB', 'amapress' );
	} elseif ( $rawSize / 1048576 > 1 ) {
		return number_format_i18n( $rawSize / 1048576, 1 ) . ' ' . __( 'MB', 'amapress' );
	} elseif ( $rawSize / 1024 > 1 ) {
		return number_format_i18n( $rawSize / 1024, 1 ) . ' ' . __( 'KB', 'amapress' );
	} elseif ( $rawSize > 1 ) {
		return number_format_i18n( $rawSize, 0 ) . ' ' . __( 'B', 'amapress' );
	} else {
		return __( 'unknown', 'amapress' );
	}
}

### Function: Convert PHP Size Format to Localized
function amapress_format_php_size( $size ) {
	if ( ! is_numeric( $size ) ) {
		if ( strpos( $size, 'M' ) !== false ) {
			$size = intval( $size ) * 1024 * 1024;
		} elseif ( strpos( $size, 'K' ) !== false ) {
			$size = intval( $size ) * 1024;
		} elseif ( strpos( $size, 'G' ) !== false ) {
			$size = intval( $size ) * 1024 * 1024 * 1024;
		}
	}

	return is_numeric( $size ) ? amapress_format_filesize( $size ) : $size;
}

function amapress_wp_db_stats() {
	global $wpdb;

	$posts_count    = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts" );
	$postmeta_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta" );
	$users_count    = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" );
	$usermeta_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta" );

	echo '<h2>Tables rows count</h2>';
	echo "<p>Table '$wpdb->posts' rows: $posts_count</p>";
	echo "<p>Table '$wpdb->postmeta' rows: $postmeta_count</p>";
	echo "<p>Table '$wpdb->users' rows: $users_count</p>";
	echo "<p>Table '$wpdb->usermeta' rows: $usermeta_count</p>";

//	echo '<h2>Posts count by type</h2>';
//	$results = $wpdb->get_results( "SELECT post_type, COUNT(*) as post_count FROM $wpdb->posts GROUP BY post_type", ARRAY_A );
//	foreach ( $results as &$r ) {
//		$post_type = $r['post_type'];
//		$pt        = get_post_type_object( $post_type );
//		if ( $pt ) {
//			$r['post_type'] = "{$pt->label} ($post_type)";
//		}
//	}
//	amapress_echo_datatable( 'posts_count_per_type',
//		[
//			[ 'title' => __('Post type', 'amapress'), 'data' => 'post_type' ],
//			[ 'title' => __('Posts count', 'amapress'), 'data' => 'post_count' ],
//		], $results );
	echo '<h2>Posts meta stats by type</h2>';
	$results = $wpdb->get_results( "SELECT post_type, COUNT(distinct p.ID) as post_count, 
COUNT(*) as post_meta_count 
FROM $wpdb->posts as p INNER JOIN $wpdb->postmeta as pm ON p.ID = pm.post_id GROUP BY p.post_type", ARRAY_A );
	foreach ( $results as &$r ) {
		$post_type = $r['post_type'];
		$pt        = get_post_type_object( $post_type );
		if ( $pt ) {
			$r['post_type'] = "{$pt->label} ($post_type)";
		}
		$r['post_meta_avg'] = (int) ( $r['post_meta_count'] / $r['post_count'] );
	}
	amapress_echo_datatable( 'avg_postmeta_per_type',
		[
			[ 'title' => 'Post type', 'data' => 'post_type' ],
			[ 'title' => 'Posts count', 'data' => 'post_count' ],
			[ 'title' => 'Postmeta count', 'data' => 'post_meta_count' ],
			[ 'title' => 'Avg postmeta', 'data' => 'post_meta_avg' ],
		], $results );

	echo '<h2>All options</h2>';
	$results = [];
	foreach ( wp_load_alloptions() as $k => $v ) {
		$results[] = [
			'name'  => $k,
			'value' => '<pre>' . var_export( maybe_unserialize( $v ), true ) . '</pre>'
		];
	}
	amapress_echo_datatable( 'amps_all_options',
		[
			[ 'title' => 'Name', 'data' => 'name' ],
			[ 'title' => 'Value', 'data' => 'value' ],
		], $results,
		array(
			'paging'     => true,
			'sorting'    => false,
			'searching'  => true,
			'responsive' => false,
		) );
}

function amapress_state_labels() {
	$labels = array(
		'01_plugins'      => __( 'Extensions - Recommandées', 'amapress' ),
		'02_plugins_not'  => __( 'Extensions - Non Recommandées', 'amapress' ),
		'05_config'       => __( 'Configuration', 'amapress' ),
		'10_users'        => __( 'Comptes utilisateurs', 'amapress' ),
		'15_posts'        => __( 'Votre AMAP', 'amapress' ),
		'20_content'      => __( 'Contenus complémentaires', 'amapress' ),
		'24_shortcodes'   => __( 'Shortcodes configurés', 'amapress' ),
		'25_shortcodes'   => __( 'Shortcodes à configurer', 'amapress' ),
		'26_online_inscr' => __( 'Inscriptions en ligne', 'amapress' ),
		'30_recalls'      => __( 'Rappels', 'amapress' ),
		'35_import'       => __( 'Import CSV', 'amapress' ),
		'36_mailing'      => __( 'Listes de diffusions', 'amapress' ),
		'37_plugins_add'  => __( 'Extensions - Fonctionnalités supplémentaires', 'amapress' ),
		'38_plugins_adv'  => __( 'Extensions - Utilitaires/Avancés', 'amapress' ),
		'40_clean'        => __( 'Nettoyage', 'amapress' ),
	);

	$i = 1;
	foreach ( $labels as $k => $v ) {
		$labels[ $k ] = "$i/ $v";
		$i            += 1;
	}

	return $labels;
}

function amapress_echo_and_check_amapress_state_page() {
	if ( current_user_can( 'update_core' ) ) {
		if ( isset( $_GET['generate_amap_options'] ) ) {
			$options_to_generate = [
				'resp_role_1-name',
				'resp_role_2-name',
				'resp_role_3-name',
				'resp_role_4-name',
				'resp_role_5-name',
				'resp_role_6-name',
				'resp_role_7-name',
				'resp_role_8-name',
				'resp_role_1-desc',
				'resp_role_2-desc',
				'resp_role_3-desc',
				'resp_role_4-desc',
				'resp_role_5-desc',
				'resp_role_6-desc',
				'resp_role_7-desc',
				'resp_role_8-desc',
				'pwa_short_name',
				'allow_partial_coadh',
				'disable_principal',
				'online_new_user_quest1',
				'online_new_user_quest2',
				'online_norenew_message',
				'online_principal_user_message',
				'online_coadh_user_message',
				'online_adhesion_coadh_message',
				'online_subscription_agreement',
				'online_subscription_greating_adhesion',
				'online_contrats_step_message',
				'online_contrats_end_step_message',
				'online_contrats_end_step_edit_message',
				'online_final_step_message',
				'allow_partial_exchange',
				'intermit_self_inscr',
				'intermit_adhesion_req'
			];
			$generated_value     = [];
			foreach ( $options_to_generate as $k ) {
				$v = Amapress::getOption( $k );
				if ( ! empty( $v ) ) {
					$generated_value[ $k ] = wp_unslash( $v );
				}
			}
			$code = '$options_values = ' . var_export( $generated_value, true ) . ";\n";
			$code .= 'foreach ($options_values as $k => $v) {' . "\n";
			$code .= '    Amapress::setOption($k, $v);' . "\n";
			$code .= '}';
			echo '<textarea cols="80" rows="100" style="width: 100%; font-family: monospace">';
			echo esc_textarea( wp_kses_decode_entities( wp_specialchars_decode( preg_replace( '/\<\/?pre\>/', '',
				$code ), ENT_QUOTES ) ) );
			echo '</textarea>';
		}
		if ( isset( $_GET['generate_full_amap'] ) ) {
			echo '<p>' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_state&generate_amap_options=T' ), __( 'Générer les options', 'amapress' ) ) . '</p>';
			echo '<textarea cols="80" rows="100" style="width: 100%; font-family: monospace">';
			echo esc_textarea( wp_kses_decode_entities( wp_specialchars_decode( preg_replace( '/\<\/?pre\>/', '',
				Amapress::generate_full_amap( ! isset( $_REQUEST['no_anonymize'] ) ) ), ENT_QUOTES ) ) );
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
			if ( 'active' === amapress_is_plugin_active( 'query-monitor' ) ) {
				wp_die( __( 'Query Monitor est actif, merci de le désactiver avant import (risque de dépassement de mémoire)', 'amapress' ) );
			}

			if ( 'active' === amapress_is_plugin_active( 'new-user-approve' ) ) {
				wp_die( __( 'New User Apprive est actif, merci de le désactiver avant import (pour éviter envoi massif de mails d\'approbation )', 'amapress' ) );
			}

			set_time_limit( 0 );

			require_once 'demos/AmapDemoBase.php';
			$demo_file = $_SERVER['DOCUMENT_ROOT'] . '/../demos/' . $_GET['import_amap'];
			if ( ! file_exists( $demo_file ) ) {
				$demo_file = AMAPRESS__PLUGIN_DIR . '/demos/' . $_GET['import_amap'];
			}
			require_once $demo_file;

			if ( ! defined( 'ALLOW_UNFILTERED_UPLOADS' ) ) {
				define( 'ALLOW_UNFILTERED_UPLOADS', true );
			}

			global $amapress_import_demo;

			TitanFrameworkMetaBox::$allow_save_options = false;
			$amapress_import_demo                      = true;

			/** @var AmapDemoBase $amap_class */
			global $amap_class;
			$cnt = AmapDemoBase::deleteAutoGeneratedPosts();
			echo '<p class="notice notice-info">' . $cnt . ' deleted posts</p>';
			$amap_class->createAMAP( isset( $_GET['shift_weeks'] ) ? intval( $_GET['shift_weeks'] ) : 0 );

			$demo_params_file = $_SERVER['DOCUMENT_ROOT'] . '/../demos/demo_params.php';
			if ( ! file_exists( $demo_params_file ) ) {
				$demo_params_file = AMAPRESS__PLUGIN_DIR . '/demos/demo_params.php';
			}
			if ( file_exists( $demo_params_file ) ) {
				require_once $demo_params_file;

				if ( function_exists( 'amap_demo_import_def_params' ) ) {
					amap_demo_import_def_params();
				}
			}

			echo '<p class="notice notice-info">AMAP Created</p>';

			$amapress_import_demo = false;

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
				echo '<p style="color:red">' . __( 'Addresse non localisée', 'amapress' ) . '</p>';
			}
		}

		if ( isset( $_GET['phpinfo'] ) ) {
			amapress_embedded_phpinfo();

			return;
		}

		if ( isset( $_GET['wp_db_stats'] ) ) {
			amapress_wp_db_stats();

			return;
		}

		if ( defined( 'AMAPRESS_DEMO_MODE' ) ) {
			if ( isset( $_REQUEST['bounce_parser'] ) ) {
				amapress_parse_bounce_mail();

				return;
			}
		}

		if ( isset( $_REQUEST['check_ssl'] ) ) {
			amapress_check_ssl_in_content();

			return;
		}
	}

	$labels = amapress_state_labels();
	$state  = amapress_get_state();

	if ( current_user_can( 'update_core' ) ) {
		if ( defined( 'AMAPRESS_DEMO_MODE' ) ) {
			echo '<h2>DEMO MODE Administrative section</h2>';
			echo '<p><a href="' . esc_attr( add_query_arg( 'clean_amap', 'T', admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">' . __( 'Nettoyer les custom posts', 'amapress' ) . '</a></p>';
			echo '<p><a href="' . esc_attr( add_query_arg( 'clean_partial_amap', 'T', admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">' . __( 'Nettoyer les générables', 'amapress' ) . '</a></p>';
			echo '<p><a href="' . esc_attr( add_query_arg( 'generate_full_amap', 'T', admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">' . __( 'Générer le code de démo', 'amapress' ) . '</a></p>';
			echo '<p><a href="' . esc_attr( add_query_arg( [
					'generate_full_amap' => 'T',
					'no_anonymize'       => 'T'
				], admin_url( 'admin.php?page=amapress_state' ) ) ) . '" target="_blank">' . __( 'Générer le code de démo (sans anonymisation)', 'amapress' ) . '</a></p>';
			echo '<form method="post">
<input type="hidden" name="rand_addr" />
<label>' . __( 'Adresse à anonymiser: ', 'amapress' ) . '<input type="text" name="address"/></label>
<br/>
<label>' . __( 'Dans un rayon de: ', 'amapress' ) . '<input type="number" step="100" name="around" value="2000"/></label>
<input type="submit" value="' . esc_attr__( 'Générer', 'amapress' ) . '" />
</form>';
			echo '<hr/>';
			echo '<h3>' . __( 'Modèles d\'AMAP', 'amapress' ) . '</h3>';
			$demo_dir = $_SERVER['DOCUMENT_ROOT'] . '/../demos';
			if ( ! file_exists( $demo_dir ) ) {
				$demo_dir = AMAPRESS__PLUGIN_DIR . '/demos';
			}
			if ( $handle = opendir( $demo_dir ) ) {
				while ( false !== ( $entry = readdir( $handle ) ) ) {
					if ( $entry != '.' && $entry != '..' && $entry != 'AmapDemoBase.php' ) {
						echo '<a target="_blank" href="' . esc_attr( add_query_arg(
								[ 'import_amap' => $entry, 'shift_weeks' => 0 ],
								admin_url( 'admin.php?page=amapress_state' )
							) ) . '">' . esc_html( $entry ) . '</a><br/>';
					}
				}
				closedir( $handle );
			}
			echo '<hr/>';
		}


		echo '<p><a href="' . esc_attr( amapress_get_github_updater_url() ) . '" target="_blank">' . __( 'Rafraichir le cache Git Updater', 'amapress' ) . '</a> / <a href="' . esc_attr( admin_url( 'plugins.php' ) ) . '" target="_blank">' . __( 'Voir les extensions installées', 'amapress' ) . '</a></p>';
		echo '<p><a href="' . esc_attr( add_query_arg( 'phpinfo', 'T' ) ) . '" target="_blank">' . __( 'Afficher PHP Infos', 'amapress' ) . '</a></p>';
		echo '<p><a href="' . esc_attr( add_query_arg( 'wp_db_stats', 'T' ) ) . '" target="_blank">' . __( 'Afficher Stats WP_DB', 'amapress' ) . '</a></p>';
	}

	if ( defined( 'FREE_PAGES_PERSO' ) && FREE_PAGES_PERSO ) {
		echo '<p><strong>' . __( 'Fonctionnement Free Pages Perso: actif', 'amapress' ) . '</strong></p>';
	}
	if ( defined( 'SEND_EMAILS_AS_PLAIN_TEXT' ) ) {
		echo '<p><strong>' . __( 'Envoi des mails en texte brut: actif', 'amapress' ) . '</strong></p>';
	}

	global $wp_version;
	echo '<p><strong>' . __( 'Version PHP : ', 'amapress' ) . PHP_VERSION . ' (' . PHP_OS . ' / ' . $_SERVER["SERVER_SOFTWARE"] . ')' . '</strong></p>';
	echo '<p><strong>' . __( 'Version Wordpress : ', 'amapress' ) . $wp_version . '</strong></p>';
	echo '<p><strong>' . __( 'Version d\'Amapress : ', 'amapress' ) . AMAPRESS_VERSION . '</strong></p>';
	echo '<p><strong>' . __( 'Version MySQL : ', 'amapress' ) . amapress_get_mysql_version() .
	     ' (Data ' . amapress_format_filesize( amapress_get_mysql_data_usage() ) .
	     ' ; Index ' . amapress_format_filesize( amapress_get_mysql_index_usage() ) .
	     ' ; Cache ' . amapress_format_filesize( amapress_get_mysql_query_cache_size() ) . ')</strong></p>';
	echo '<p>' . __( 'Hébergement : ', 'amapress' ) . implode( ' / ', [
			$_SERVER['SERVER_NAME'],
			$_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'],
			__( 'Root: ', 'amapress' ) . $_SERVER['DOCUMENT_ROOT']
		] ) . '</p>';
	echo '<p>' . __( 'Limite mémoire/durée exécution : ', 'amapress' ) . amapress_format_php_size( amapress_get_php_memory_limit() ) . '</p>';
	echo '<p>' . __( 'Limite durée d\'exécution : ', 'amapress' ) . amapress_get_php_max_execution() . 's</p>';
	echo '<p>' . __( 'Limite upload/post : ', 'amapress' ) . amapress_format_php_size( amapress_get_php_upload_max() ) . '/' . amapress_format_php_size( amapress_get_php_post_max() ) . '</p>';

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

			$icon = "<span class='dashicons dashicons-external'></span>";
			if ( false === $check['icon'] ) {
				$icon = '';
			} elseif ( ! empty( $check['icon'] ) ) {
				$icon = $check['icon'];
			}

			echo "<div class='amapress-check'>";
			if ( empty( $link ) ) {
				echo "<p class='check-item state {$state}'>{$title}</p>";
			} else {
				echo "<p class='check-item state {$state}'><a href='$link' $target>{$title}</a>$icon</p>";
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
		    //set localStorage for current index on activate event
		    activate: function(event, ui) {        
		        localStorage.setItem("amps_state_idx", $(this).accordion("option", "active"));
		    },
		    active: (typeof localStorage !== \'undefined\' ? parseInt(localStorage.getItem("amps_state_idx")) : "none")
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
	echo '<p><a href="' . esc_attr( amapress_get_github_updater_url() ) . '" target="_blank">' . __( 'Rafraichir le cache Git Updater', 'amapress' ) . '</a></p>';
} );

add_action( 'admin_init', function ( $plugins ) {
	global $pagenow;
	if ( 'update-core.php' == $pagenow ) {
		amapress_add_admin_notice(
			'<a href="' . esc_attr( amapress_get_github_updater_url() ) . '" target="_blank">' . __( 'Rafraichir le cache Git Updater', 'amapress' ) . '</a>',
			'info', false, false
		);
	}
} );

add_action( 'activate_plugin', 'amapress_clean_state_transient' );
add_action( 'save_post', 'amapress_clean_state_transient' );
add_action( 'tf_save_options_amapress', 'amapress_clean_state_transient' );