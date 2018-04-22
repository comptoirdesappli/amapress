/**
 * Created by guillaume.legrand on 01/06/2016.
 */
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function (searchString, position) {
        position = position || 0;
        return this.substr(position, searchString.length) === searchString;
    };
}

function amapress_handle_front_end_ajax_button_click(e) {
    var $this = jQuery(this);
    if ($this.data('confirm')) {
        if (!confirm($this.data('confirm'))) return false;
    }
    // Only perform one ajax at a time
    if (typeof this.doingAjax === 'undefined') {
        this.doingAjax = false;
    }
    e.preventDefault();
    if (this.doingAjax) {
        return false;
    }
    this.doingAjax = true;

    var data = {
        "action": $this.data('action'),
    };


    var $this_data = $this.data();
    for (var i in $this_data) {
        if ('success-callback' === i) continue;
        if ('confirm' === i) continue;
        if ('post-data' === i) continue;
        // if (i.startsWith('par'))
        var value = $this_data[i];
        if (typeof value === 'string' && value.startsWith("val:")) {
            value = jQuery(value.substr(4)).val();
        }
        data[i] = value;
    }

    if ($this.data('post-data') !== '') {
        if (typeof window[$this.data('post-data')] !== 'undefined') {
            data = window[$this.data('post-data')](data);
        }
    }
    $this.prop("disabled", true);
    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.post(amapress.ajax_url, data, function (response) {
        if (response === "error") {
            alert("Erreur");
            $this.prop("disabled", false);
            return;
        }
        // Call the error callback
        if ($this.data('success-callback')) {
            if (typeof window[$this.data('success-callback')] !== 'undefined') {
                window[$this.data('success-callback')](response);
            }
        } else {
            var $parent = $this.closest('.amapress-ajax-parent');
            if ($parent.length > 0) {
                $parent.replaceWith(response);
            } else {
                $this.replaceWith(response);
            }
        }
        this.doingAjax = false;
    }.bind(this));
    return false;
}

function amapress_init_front_end_ajax_buttons() {
    jQuery(".amapress-ajax-button")
        .unbind("click", amapress_handle_front_end_ajax_button_click)
        .bind("click", amapress_handle_front_end_ajax_button_click);
}

jQuery(function ($) {
    var maxHeight = 0;
    var initSelect2 = function () {
        $('select.autocomplete').select2({
            allowClear: true,
            escapeMarkup: function (markup) {
                return markup;
            },
            templateResult: function (data) {
                return jQuery("<span>" + data.text + "</span>");
            },
            templateSelection: function (data) {
                return jQuery("<span>" + data.text + "</span>");
            },
            width: 'auto'
        });
    };
    $(".tab-content .tab-pane").each(function () {
        $(this).addClass("active");
        var height = $(this).height();
        maxHeight = height > maxHeight ? height : maxHeight;
        $(this).removeClass("active");
    });
    $('.slick-gallery').slick();
    $(".tab-content .tab-pane:first").addClass("active");
    $(".tab-content").height(maxHeight);
    $(".adhesion input").prop('disabled', true);
    $(".active input[type=radio]").prop('checked', true);
    $(".paiement input[type=radio]").prop('disabled', true);
    $(".contrat-public input[type=radio]").prop('disabled', true);
    $('.table thead th[colspan]').wrapInner('<span class="dt-th-colspan"/>').append('&nbsp;');
    $(".table").DataTable(
        {
            'paging': false,
            'info': false,
            'ordering': false,
            //'responsive': true,
            'searching': false,
            'fnInitComplete': function () {
                initSelect2();
            }
        }
    ).on('responsive-display', function (e, datatable, row, showHide, update) {
        initSelect2();
    });
    initSelect2();
    //contrat-public
    $("li.quantite > .radio > input[type=radio]").change(function () {
        $(".paiement input[type=radio]").prop('disabled', true);
        if ($(this).is(':checked')) {
            $(".paiement input[type=radio]", $(this).parent().parent()).prop('disabled', false);
        }
    });
    if ('undefined' !== typeof(fakewaffle))
        fakewaffle.responsiveTabs(['xs', 'sm']);
    amapress_init_front_end_ajax_buttons();
});