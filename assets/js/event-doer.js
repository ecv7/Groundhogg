jQuery(function($) {
    setInterval( function(){
        $.ajax({
            type: "post",
            url: wpgh_ajax_object.ajax_url,
            data: {action: 'wpgh_event_queue_start'},
            success: function( events_complete ){
                console.log( events_complete )
            }
        });
    }, 30 * 1000 );
});