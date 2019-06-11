(function (gh, $) {
    $.extend( gh, {
        leadSource: 'gh_referer',
        refID: 'gh_ref_id',
        setCookie: function(cname, cvalue, exdays){
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = "expires="+ d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        },
        getCookie: function( cname ){
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return null;
        },
        pageView : function(){
            var self = this;

            $.ajax({
                type: "post",
                url: self.page_view_endpoint,
                data: { ref: window.location.href, _ghnonce: self._ghnonce },
                beforeSend: function ( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', self._wpnonce );
                },
                success: function( response ){},
                error: function(){}
            });
        },
        logFormImpressions : function() {
            var self = this;
            var forms = $( '.gh-form' );
            $.each( forms, function ( i, e ) {
                var fId = $(e).find( 'input[name="gh_submit_form"]' ).val();
                self.formImpression( fId );
            });
        },
        formImpression : function( id ){
            var self = this;
            $.ajax({
                type: "post",
                url: self.form_impression_endpoint,
                dataType: 'json',
                data: { ref: window.location.href, form_id: id, _ghnonce: self._ghnonce },
                beforeSend: function ( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', self._wpnonce );
                },
                success: function( response ){
                    if( typeof response.ref_id !== 'undefined' ) {
                        self.setCookie( self.refID, response.ref_id, 30 );
                    }
                },
                error: function(){}
            });
        },
        init: function(){
            var referer = this.getCookie( this.leadSource );
            if ( ! referer ){
                this.setCookie( this.leadSource, document.referrer, 3 )
            }
            this.pageView();
            this.logFormImpressions();
        }
    } );

    $(function(){
        gh.init();
    });
})(Groundhogg, jQuery);

