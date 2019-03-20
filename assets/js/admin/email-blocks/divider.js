wpghEmailEditor = wpghEmailEditor || {};

var wpghDividerBlock;
( function( $, editor ) {

    wpghDividerBlock = {

        blockType: 'divider',

        height: null,
        color: null,
        thickness: null,

        init : function () {

            this.height  = $( '#divider-width' );
            this.height.on( 'change input', function ( e ) {
                editor.getActive().find('hr').css('width', $(this).val() + '%' );
            });

            this.thickness  = $( '#divider-height' );
            this.thickness.on( 'change input', function ( e ) {
                editor.getActive().find('hr').css('border-top-width', $(this).val() + 'px' );
            });

            this.color = $( '#divider-color' );
            this.color.wpColorPicker({
                change: function (event, ui) {
                    editor.getActive().find('hr').css('border-top-color', wpghDividerBlock.color.val() );}
            });

            $(document).on( 'madeActive', function (e, block, blockType ) {

                if ( wpghDividerBlock.blockType === blockType ){
                    wpghDividerBlock.parse( block );
                }

            });
        },

        /**
         * A jquery implement block.
         *
         * @param block $
         */
        parse: function ( block ) {

            this.height.val( Math.ceil( ( editor.getActive().find('hr').width() / editor.getActive().find('hr').closest('div').width() ) * 100 ) );
            this.thickness.val( parseInt( editor.getActive().find('hr').css( 'border-top-width' ) ) );

        }

    };

    $(function(){
        wpghDividerBlock.init();
    })

})( jQuery, wpghEmailEditor );