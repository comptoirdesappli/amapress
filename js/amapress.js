jQuery(function ($) {
    $('.wp-not-current-submenu').each(function () {
        if ($('.current', this).length > 0) {
            $(this).addClass('wp-has-current-submenu').removeClass('wp-not-current-submenu');
        }
    });
    $.widget("custom.datahtmlselect", $.ui.selectmenu, {
        _renderItem: function (ul, item) {
            var li = $("<li>");
            if (item.disabled) {
                li.addClass("ui-state-disabled");
            }
            var html = item.element.attr("data-html") || item.element.text();
            return li.append($('<span class="paiement-info">' + html + '</span>').children()).appendTo(ul);
        }
    });
    var $paiement_total = $('#paiement-amount-total');
    var check_sum_fn = function () {
        var sum = 0;
        $('.paiement-report-val').each(function () {
            sum += parseFloat($(this).val());
        });
        $('.paiement-amount-val').each(function () {
            sum += parseFloat($(this).val());
        });
        var check_sum = $paiement_total.data('sum');
        var isOK = sum == parseFloat(check_sum);
        $paiement_total.text(sum.toFixed(2) + (!isOK ? ' (' + check_sum + ')' : '')).removeClass('ok').removeClass('nok').addClass(isOK ? 'ok' : 'nok');
    };
    $('.paiement-amount-val').change(check_sum_fn);
    check_sum_fn();
    $('select.paiements_details').datahtmlselect({width: 200});
    $('.panier-tr').each(function () {
        var parent = this;
        var totalCol = $('.panier-total-col .total-val', parent);
        var updateSum = function () {
            var sum = 0;
            $('.panier-abo-col input', parent).each(function () {
                try {
                    sum += parseFloat(this.value) * parseFloat($(this).data('count'));
                } catch (e) {
                }
            });
            if (!isNaN(sum)) {
                totalCol.val(sum);
                $('.panier-lieu-col', parent).each(function () {
                    $(this).text((sum * parseFloat($(this).data('factor'))).toFixed(2));
                });
            } else
                totalCol.val('');
        };

        var applyQuantsUnit = function () {
            var tot = parseFloat($('#' + $(this).attr('id').replace('_btn', '')).val());
            if (isNaN(tot)) return false;
            $('.panier-abo-col input', parent).each(function () {
                //
                $(this).val((tot * parseFloat($(this).data('factor'))).toFixed(2));
            });
            updateSum();

            return false;
        };
        var applyQuantsTotal = function () {
            var tot = parseFloat($('#' + $(this).attr('id').replace('_btn', '')).val());
            if (isNaN(tot)) return false;
            var sum = 0;
            $('.panier-abo-col input', parent).each(function () {
                //
                sum += parseFloat($(this).data('count'));
            });
            if (sum > 0) tot = tot / sum;
            $('.panier-abo-col input', parent).each(function () {
                $(this).val((tot * parseFloat($(this).data('factor'))).toFixed(2));
            });
            updateSum();

            return false;
        };
        $('.panier-abo-col input', parent).each(function () {
            $(this).change(updateSum);
        });
        $('.amapress_base_btn', parent).each(function () {
            $(this).click(applyQuantsUnit);
        });
        $('.amapress_total_btn', parent).each(function () {
            $(this).click(applyQuantsTotal);
        });
        updateSum();
    });
    if (typeof $.fn.DataTable !== "undefined") {
        $(".placeholders-help").DataTable({
            "paging": false,
            "info": false,
            "ordering": false,
            //'responsive': true,
            "searching": true,
        });
    }
});
