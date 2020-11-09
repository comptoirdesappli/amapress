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
            alert("Error");
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

    $('body').on('click', '.amapress-ajax-button', amapress_handle_front_end_ajax_button_click);

    setTimeout(function () {
        $('.iso-gallery').isotope({
            itemSelector: '.iso-gallery-item',
            layoutMode: 'fitRows',
            percentPosition: true,
            fitRows: {
                columnWidth: '.iso-gallery-sizer'
            },
            getSortData: {
                sort: '[data-sort]',
            },
            sortBy: 'sort',
            filter: function () {
                var $this = $(this);
                var $gallery = $this.closest('.iso-gallery');
                var qsRegex = $gallery.data('regex');
                var searchResult = qsRegex ? $this.text().match(qsRegex) : true;
                var buttonFilter = $gallery.data('buttonFilter');
                var buttonResult = buttonFilter ? $this.is(buttonFilter) : true;
                return searchResult && buttonResult;
            },
        });
        // use value of search field to filter
        $('.iso-gallery-search').keyup(debounce(function () {
            var $this = $(this);
            var $gallery = $('#' + $this.data('gallery'));
            $gallery.data('regex', new RegExp($this.val(), 'gi'));
            $gallery.isotope();
        }, 200));

        $('.iso-gallery-filters').on('click', '.iso-gallery-filter', function () {
            var $this = $(this);
            // get group key
            var $buttonGroup = $this.parents('.iso-gallery-filters-group');
            var filterGroup = $buttonGroup.attr('data-filter-group');
            var $gallery = $('#' + $this.data('gallery'));
            var buttonFilters = $gallery.data('buttonFilters');
            if ('undefined' === typeof buttonFilters)
                buttonFilters = {};
            var buttonFilter = $gallery.data('buttonFilter');
            // set filter for group
            buttonFilters[filterGroup] = $this.attr('data-filter');
            // combine filters
            buttonFilter = concatValues(buttonFilters);
            $gallery.data('buttonFilters', buttonFilters).data('buttonFilter', buttonFilter);
            // Isotope arrange
            $gallery.isotope();
        });

        // change is-checked class on buttons
        $('.iso-gallery-filters-group').each(function (i, buttonGroup) {
            var $buttonGroup = $(buttonGroup);
            $buttonGroup.on('click', 'button', function () {
                $buttonGroup.find('.is-checked').removeClass('is-checked');
                $(this).addClass('is-checked');
            });
        });
    }, 100);

    // flatten object by concatting values
    function concatValues(obj) {
        var value = '';
        for (var prop in obj) {
            value += obj[prop];
        }
        return value;
    }

    // debounce so filtering doesn't happen every millisecond
    function debounce(fn, threshold) {
        var timeout;
        threshold = threshold || 100;
        return function debounced() {
            clearTimeout(timeout);
            var args = arguments;
            var _this = this;

            function delayed() {
                fn.apply(_this, args);
            }

            timeout = setTimeout(delayed, threshold);
        };
    }

    $('.amp-years-since').each(function () {
        var $this = $(this);
        $.post(amapress.ajax_url, {
            action: 'get_years_from',
            year: $this.data('year'),
        }, function (response) {
            $this.text(response);
        });
    });
});