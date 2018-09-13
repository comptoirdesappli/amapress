function amapress_contrat_status() {
    jQuery('body').on('click', '.contrat-status-button', function () {
        var $this = jQuery(this);
        var data = {
            'action': 'update_contrat_status_action',
            'contrat_instance': $this.data('contrat-instance')
        };

        var contrat_status = jQuery(this).closest('.status');
        $this.prop("disabled", true);
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(update_contrat_status.ajax_url, data, function (response) {
            if (response == 'error') {
                alert('Erreur');
                $this.prop("disabled", false);
                return;
            }
            contrat_status.replaceWith(response);
        });
        return false;
    });
}

jQuery(document).ready(function ($) {
    amapress_contrat_status();
});