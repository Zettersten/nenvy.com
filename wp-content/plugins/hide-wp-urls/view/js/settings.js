jQuery(document).ready(function () {

    var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));

    elems.forEach(function (html) {
        new Switchery(html, {color: 'green'});
    });

    jQuery('#hmu_settings').find('select[name=hmu_mode]').on('change', function () {
        jQuery('#hmu_settings').find('.tab-panel').hide();
        jQuery('#hmu_settings').find('.hmu_' + jQuery(this).val()).show();
    });

});

