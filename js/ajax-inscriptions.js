jQuery(document).ready(function ($) {
    jQuery('body').on('click', '.dist-inscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }

        var data = {
            'action': 'inscrire_distrib_action',
            'dist': $this.data('dist'),
            'role': $this.data('role'),
            'user': $this.data('user'),
            'gardien': $this.data('gardien'),
            'key': $this.data('key'),
            'not_member': $this.data('not_member'),
            'inscr-key': $this.data('inscr-key'),
            'message': $this.data('message') ? $($this.data('message').substr(4)).val() : '',
        };
        var $parentForm = $this.parent('form');
        var $parentDiv = $this.parent('.inscription-other-user');
        if ($parentForm.length > 0) {
            $parentForm.validate({
                errorPlacement: function (error, element) {
                    error.insertBefore(element.nextAll('button'));
                }
            });
            if (!$parentForm.valid()) return;
        }
        if ($parentDiv.length > 0)
            data.user = $('select[name=user]', $parentDiv).val();

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
    }).on('click', '.dist-desinscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }
        var data = {
            'action': 'desinscrire_distrib_action',
            'dist': $this.data('dist'),
            'user': $this.data('user'),
            'gardien': $this.data('gardien'),
            'key': $this.data('key'),
            'inscr-key': $this.data('inscr-key'),
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
    }).on('click', '.event-inscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }
        var data = {
            'action': 'inscrire_amap_event_action',
            'event': $this.data('event')
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
            if (data.user) {
                $(response).insertAfter($this);
                $this.prop("disabled", false);
            } else {
                $this.replaceWith(response);
            }
        });
        return false;
    }).on('click', '.visite-inscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }
        var data = {
            'action': 'inscrire_visite_action',
            'visite': $this.data('visite')
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
            if (data.user) {
                $(response).insertAfter($this);
                $this.prop("disabled", false);
            } else {
                $this.replaceWith(response);
            }
        });
        return false;
    }).on('click', '.assemblee-inscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }
        var data = {
            'action': 'inscrire_assemblee_action',
            'event': $this.data('event')
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
            if (data.user) {
                $(response).insertAfter($this);
                $this.prop("disabled", false);
            } else {
                $this.replaceWith(response);
            }
        });
        return false;
    }).on('click', '.visite-desinscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }
        var data = {
            'action': 'desinscrire_visite_action',
            'visite': $this.data('visite'),
            'user': $this.data('user'),
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
            $this.replaceWith(response);
        });
        return false;
    }).on('click', '.event-desinscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }
        var data = {
            'action': 'desinscrire_amap_event_action',
            'event': $this.data('event'),
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
            $this.replaceWith(response);
        });
        return false;
    }).on('click', '.assemblee-desinscrire-button', function () {
        var $this = jQuery(this);
        if ($this.data('confirm') && !confirm($this.data('confirm'))) {
            return;
        }
        var data = {
            'action': 'desinscrire_assemblee_action',
            'event': $this.data('event'),
            'user': $this.data('user')
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