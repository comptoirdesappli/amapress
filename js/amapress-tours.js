(function ($) {

    var tour = new Tour({
        backdrop: true,
        steps: [
            {
                element: "#wp-admin-bar-amapress",
                title: "Menu Amapress 1",
                content: "Ce menu permet d'accéder aux fonctions les plus importantes d'Amapress",
            },
            {
                element: "#wp-admin-bar-amapress-default",
                title: "Menu Amapress 2",
                content: "Ce menu permet d'accéder aux fonctions les plus importantes d'Amapress",
                onShow: function () {
                    $('#wp-admin-bar-amapress').addClass('hover');
                },
                onHide: function () {
                    $('#wp-admin-bar-amapress').removeClass('hover');
                }
            },
            {
                element: "#toplevel_page_amapress_state",
                title: "Etat d'Amapress",
                content: "Contient l'Etat en cours de la configuration d'Amapress"
            },
            {
                element: "#toplevel_page_amapress_gestion_amapiens_page",
                title: "Gestion Contrats",
                content: "Gestion des contrats et des inscriptions"
            },
            {
                path: "admin.php?page=amapress_gestion_amapiens_page",
                element: "#adminmenu .wp-submenu li.current",
                title: "Gestion Contrats",
                content: "Gestion des contrats et des inscriptions"
            },
            {
                path: "edit.php?post_type=amps_contrat_inst&amapress_date=active",
                oprhan: true,
                title: "Contrats",
                content: "Gestion des contrats et des inscriptions"
            },
        ]
    });

    tour.init();

    // This will load on each page load or refresh.
    // You may want to change this behaviour according to your need.
    // e.g. show the tour on a click even of a custom notice or button
    // within admin panel dashboard.
    $(window).load(function () {
        //tour.start( true );
    });
})(jQuery);