<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Enable network-first caching strategy for navigation requests (i.e. clicking around the site).
add_filter(
	'wp_service_worker_navigation_caching_strategy',
	function () {
		return \WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST;
	}
);

//// Hold on to a certain number of navigated pages in the cache.
//	add_filter(
//		'wp_service_worker_navigation_caching_strategy_args',
//		function ( $args ) {
//			$args['cacheName'] = 'pages';
//
//			$args['plugins']['expiration']['maxEntries'] = 20;
//
//			return $args;
//		}
//	);

// Cache theme assets with runtime network-first caching strategy. This includes both the parent theme and child theme.
add_action(
	'wp_front_service_worker',
	function ( $scripts ) {
		/** @var WP_Service_Worker_Scripts $scripts */
		$theme_directory_uri_patterns = [
			preg_quote( trailingslashit( get_template_directory_uri() ), '/' ),
		];
		if ( get_template() !== get_stylesheet() ) {
			$theme_directory_uri_patterns[] = preg_quote( trailingslashit( get_stylesheet_directory_uri() ), '/' );
		}

		$scripts->caching_routes()->register(
			'^(' . implode( '|', $theme_directory_uri_patterns ) . ').*',
			array(
				'strategy'  => \WP_Service_Worker_Caching_Routes::STRATEGY_NETWORK_FIRST,
				'cacheName' => 'theme-assets',
				'plugins'   => array(
					'expiration' => array(
						'maxEntries' => 25,
						// Limit the cached entries to the number of files loaded over network, e.g. JS, CSS, and PNG.
					),
				),
			)
		);
	}
);

// Add caching for uploaded images.
add_action(
	'wp_front_service_worker',
	function ( $scripts ) {
		/** @var WP_Service_Worker_Scripts $scripts */
		$upload_dir = wp_get_upload_dir();
		$scripts->caching_routes()->register(
			'^(' . preg_quote( $upload_dir['baseurl'], '/' ) . ').*\.(png|gif|jpg|jpeg|svg|webp)(\?.*)?$',
			array(
				'strategy'  => \WP_Service_Worker_Caching_Routes::STRATEGY_CACHE_FIRST,
				'cacheName' => 'uploads',
				'plugins'   => array(
					'expiration' => array(
						'maxAgeSeconds' => MONTH_IN_SECONDS,
					),
				),
			)
		);
	}
);

add_action( 'wp_head', function () {
	if ( wp_is_mobile() ) {
		?>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name="apple-mobile-web-app-status-bar-style" content="default"/>
		<?php
		if ( is_front_page() ) {
			?>
            <style>
                .ad2hs-prompt {
                    background-color: rgb(59, 134, 196); /* Blue */
                    border: none;
                    display: none;
                    color: white;
                    padding: 15px 32px;
                    text-align: center;
                    text-decoration: none;
                    font-size: 16px;

                    position: absolute;
                    margin: 0 1rem 0.3rem;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    width: calc(100% - 32px);
                }

                .ios-prompt {
                    background-color: #fcfcfc;
                    border: 1px solid #666;
                    display: none;
                    padding: 0.8rem 1rem 0 0.5rem;
                    text-decoration: none;
                    font-size: 16px;
                    color: #555;

                    position: absolute;
                    margin: 0 auto 0.3rem;
                    left: 1rem;
                    right: 1rem;
                    bottom: 0;
                }
            </style>
			<?php
		}
	}
} );

add_action( 'wp_footer', function () {
	if ( wp_is_mobile() && is_front_page() && ( ! Amapress::getOption( 'pwa_prompt_logged' ) || amapress_is_user_logged_in() ) ) {
		?>
        <button type="button"
                class="ad2hs-prompt"><?php echo esc_html( wp_unslash( Amapress::getOption( 'pwa_android_btn_text' ) ) ); ?></button>
        <div class="ios-prompt">
            <span style="color: rgb(187, 187, 187); float: right; margin-top: -14px; margin-right: -11px;">&times;</span>
            <img src="<?php echo esc_attr( AMAPRESS__PLUGIN_URL . '/images/add2home.svg' ) ?>"
                 style="float: left; height: 80px; width: auto; margin-top: -8px; margin-right: 1rem;"
                 alt="<?php echo esc_attr__( 'Ajouter à l\'écran d\'accueil', 'amapress' ) ?>"/>
            <p style="margin-top: -3px; line-height: 1.3rem;">
				<?php
				echo sprintf( 'Pour installer cette application sur votre iPhone/iPad appuyez sur %s puis sur Ajouter à l\'écran d\'accueil.',
					'<img
                            src="' . AMAPRESS__PLUGIN_URL . '/images/share.svg' . '"
                            style="display: inline-block; margin-top: 4px; margin-bottom: -4px; height: 20px; width: auto;"
                            alt="' . __( 'Partager', 'amapress' ) . '"/>'
				) ?>
            </p>
        </div>

        <script type="text/javascript">
            jQuery(function ($) {
                var discardPopups = function () {
                    $(".ios-prompt").fadeOut();
                    $(".ad2hs-prompt").fadeOut();
                };
                $(window).scroll(function () {
                    discardPopups();
                });
				<?php if ( intval( Amapress::getOption( 'pwa_prompt_discard' ) ) >= 0) { ?>
                setTimeout(discardPopups, <?php echo intval( Amapress::getOption( 'pwa_prompt_discard' ) ) * 1000; ?>);
				<?php } ?>
            });
			<?php
			if ( Amapress::getOption( 'pwa_android_prompt' ) ) {
			?>
            function addToHomeScreen() {
                let a2hsBtn = document.querySelector(".ad2hs-prompt");  // hide our user interface that shows our A2HS button
                a2hsBtn.style.display = 'none';  // Show the prompt
                deferredPrompt.prompt();  // Wait for the user to respond to the prompt
                deferredPrompt.userChoice
                    .then(function (choiceResult) {

                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the A2HS prompt');
                        } else {
                            console.log('User dismissed the A2HS prompt');
                        }

                        deferredPrompt = null;

                    });
            }

            function showAddToHomeScreen() {

                let a2hsBtn = document.querySelector(".ad2hs-prompt");
                a2hsBtn.style.display = "block";
                a2hsBtn.addEventListener("click", addToHomeScreen);

            }

            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', function (e) {
                // Prevent Chrome 67 and earlier from automatically showing the prompt
                e.preventDefault();
                // Stash the event so it can be triggered later.
                deferredPrompt = e;

                showAddToHomeScreen();
            });
			<?php
			}
			if ( Amapress::getOption( 'pwa_ios_prompt' ) ) {
			?>
            function showIosInstall() {
                let iosPrompt = document.querySelector(".ios-prompt");
                iosPrompt.style.display = "block";
                iosPrompt.addEventListener("click", () => {
                    iosPrompt.style.display = "none";
                });
            }

            // Detects if device is on iOS
            const isIos = () => {
                const userAgent = window.navigator.userAgent.toLowerCase();
                return /iphone|ipad|ipod/.test(userAgent);
            }
            // Detects if device is in standalone mode
            const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);
            // Checks if should display install popup notification:
            if (isIos() && !isInStandaloneMode()) {
                // this.setState({ showInstallMessage: true });
                showIosInstall();
            }
			<?php
			}
			?>
        </script>
		<?php
	}
} );

add_filter( 'web_app_manifest', function ( $manifest ) {
	$pwa_short_name = Amapress::getOption( 'pwa_short_name' );
	if ( ! empty( $pwa_short_name ) ) {
		$manifest['short_name'] = $pwa_short_name;
	}
	$pwa_theme_color = Amapress::getOption( 'pwa_theme_color' );
	if ( ! empty( $pwa_theme_color ) ) {
		$manifest['theme_color'] = $pwa_theme_color;
	}
	$pwa_display = Amapress::getOption( 'pwa_display' );
	if ( ! empty( $pwa_display ) ) {
		$manifest['display'] = $pwa_display;
	}

	return $manifest;
} );