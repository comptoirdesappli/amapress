jQuery(document).ready(function ($) {
    jQuery('.dist-inscrire-button').click(function () {
        var $this = jQuery(this);
        var data = {
            'action': 'inscrire_distrib_action',
            'dist': $this.data('dist')
        };
        var $parentForm = $this.parent('form');
        if ($parentForm.length > 0) {
            $parentForm.validate({
                errorPlacement: function (error, element) {
                    error.insertBefore(element.nextAll('button'));
                }
            });
            if (!$parentForm.valid()) return;
            data.user = $('select[name=user]', $parentForm).val();
        }

        $this.prop("disabled", true);
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(inscriptions.ajax_url, data, function (response) {
            if (response == 'error') {
                alert('Erreur');
                $this.prop("disabled", false);
                return;
            }
            $this.replaceWith(response);
        });
        return false;
    });
    jQuery('.dist-desinscrire-button').click(function () {
        var $this = jQuery(this);
        var data = {
            'action': 'desinscrire_distrib_action',
            'dist': $this.data('dist'),
            'user': $this.data('user')
        };
        //var $parentForm = $this.parent('form');
        //if ($parentForm.length > 0) {
        //    if (!$parentForm.validate()) return;
        //    data.user = $('select[name=user]', $parentForm).val();
        //}

        $this.prop("disabled", true);
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(inscriptions.ajax_url, data, function (response) {
            if (response == 'error') {
                alert('Erreur');
                $this.prop("disabled", false);
                return;
            }
            $this.parent().html(response);
        });
        return false;
    });
    jQuery('.event-inscrire-button').click(function () {
        var $this = jQuery(this);
        var data = {
            'action': 'inscrire_amap_event_action',
            'event': $this.data('event')
        };

        $this.prop("disabled", true);
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(inscriptions.ajax_url, data, function (response) {
            if (response == 'error') {
                alert('Erreur');
                $this.prop("disabled", false);
                return;
            }
            $this.replaceWith(response);
        });
        return false;
    });
    jQuery('.visite-inscrire-button').click(function () {
        var $this = jQuery(this);
        var data = {
            'action': 'inscrire_visite_action',
            'visite': $this.data('visite')
        };

        $this.prop("disabled", true);
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(inscriptions.ajax_url, data, function (response) {
            if (response == 'error') {
                alert('Erreur');
                $this.prop("disabled", false);
                return;
            }
            $this.replaceWith(response);
        });
        return false;
    });
    // jQuery('.echanger-panier-button').click(function () {
    //     var $this=jQuery(this);
    //     var data = {
    //         'action': 'echanger_panier',
    //         'dist': $this.data('dist'),
    //         'user': $this.data('user')
    //     };
    //
    //     $this.prop( "disabled", true );
    //     // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    //     jQuery.post(inscriptions.ajax_url, data, function (response) {
    //         if (response == 'error') {
    //             alert('Erreur');
    //             $this.prop( "disabled", false );
    //             return;
    //         }
    //         $this.replaceWith(response);
    //     });
    //     return false;
    // });
});