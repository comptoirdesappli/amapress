function amapress_paiement_status() {
    jQuery('.paiement-status-button').click(function () {
        var data = {
            'action': 'update_paiement_status_action',
            'paiement': jQuery(this).data('paiement')
        };

        paiement_status = jQuery('.paiement-status', jQuery(this).closest('.status'));
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(update_paiement_status.ajax_url, data, function (response) {
            if (response == 'error') {
                alert('Erreur');
                return;
            }
            paiement_status.replaceWith(response);
        });
        return false;
    });
}

jQuery(document).ready(function ($) {
    amapress_paiement_status();
});