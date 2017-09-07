function amapress_init_likebox() {
    jQuery('.produit-like-button, .produit-unlike-button').click(function () {
        var data = {
            'action': 'user_likebox_action',
            'produit': jQuery(this).data('produit'),
            'like': jQuery(this).data('like')
        };

        produit_likebox = jQuery(this).closest('.produit-likebox');
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(user_produit_likebox.ajax_url, data, function (response) {
            if (response == 'error') {
                alert('Votre vote n\'a pas pu �tre pris en compte.');
                return;
            }
            produit_likebox.replaceWith(response);
            amapress_init_likebox();
        });
    });
}

jQuery(document).ready(function ($) {
    amapress_init_likebox();
});