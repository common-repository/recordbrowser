jQuery(document).ready(function() {


    var limit = jQuery("#reload_recordbrowser_limit").val();
    var rb_url = jQuery("#reload_recordbrowser_url").val();
    var rb_pre_url = jQuery("#reload_recordbrowser_pre_url").val();



    jQuery(".reload_recordbrowser_random").click(function(){

        jQuery.post(
            RandomRecord.ajaxurl,
            {
                'action': 'recordbrowser_slider_reload',
                'data':   'foobarid',
                'limit' : limit,
                'rb_url' : rb_url,
                'rb_pre_url' : rb_pre_url,
            },
            function(response){
                jQuery("#randomrecordbrowsersidebarwidget").hide().append(response).fadeIn(4000);
                jQuery('html, body').animate({ scrollTop: jQuery(".reload_recordbrowser_random").offset().top }, 4000);
            }
        );


    });

});/**
 * Created by clodia on 16/04/17.
 */



