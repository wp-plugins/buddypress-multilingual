
jQuery(document).ready(function() {
    jQuery('.bpml-translation-original-toggle a').click(function(){
        jQuery(this).parent().next().toggle();
        return false;
    });
    bpmlAdminToggleCollectedActivities();
    jQuery('.bpml-show-collected-activities').click(function(){
        if (jQuery(this).val() == 'store') {
            jQuery('.bpml-activities-hide-cache-option, .bpml-activities-hide-titlecontent-option').fadeIn();
        } else {
            jQuery('.bpml-activities-hide-cache-option').fadeOut();
            jQuery('.bpml-activities-hide-titlecontent-option').fadeIn();
        }
    });
    jQuery('.bpml-hide-collected-activities').click(function(){
        jQuery('.bpml-activities-hide-cache-option, .bpml-activities-hide-titlecontent-option').fadeOut();
    });
    jQuery('#bpml-activities-select-all').click(function(){
        jQuery('#bpml-collected-activities input').attr('checked', 1);
        jQuery('#bpml-collected-activities select').val(-1);
    });
    jQuery('#bpml-activities-clear-all').click(function(){
        jQuery('#bpml-collected-activities input').attr('checked', 0);
        jQuery('#bpml-collected-activities select').val(0);
    });
    jQuery('.bpml-activity-assign-language').live('submit', function(){
        var form = jQuery(this);
        jQuery.ajax({
            url: jQuery(this).attr('action'),
            type: 'post',
            data: jQuery(this).serialize(jQuery(this))+'&action=bpml_ajax',
            dataType: 'json',
            cache: false,
            beforeSend: function(){
                form.children('.bmp-ajax-update').html('Processing...').show();
            },
            success: function(data){
                if (data.output != 'undefined') {
                    form.children('.bmp-ajax-update').html(data.output).delay(2000).fadeOut();
                } else if (data.error != 'undefined') {
                    form.children('.bmp-ajax-update').html(data.error).delay(2000).fadeOut();
                }
            },
            error: function() {
            }
        });
        return false;
    });
});

function bpmlAdminToggleCollectedActivities() {
    var val = jQuery("input:radio[name='bpml[activities][enable_google_translation]']:checked").val();
    if (val != 0) {
        if (val == 'store') {
            jQuery('.bpml-activities-hide-cache-option, .bpml-activities-hide-titlecontent-option').show();
        } else {
            jQuery('.bpml-activities-hide-cache-option').hide();
            jQuery('.bpml-activities-hide-titlecontent-option').show();
        }
    } else {
        jQuery('.bpml-activities-hide-cache-option, .bpml-activities-hide-titlecontent-option').hide();
    }
    jQuery('#bpml-collected-activities').slideDown();
}