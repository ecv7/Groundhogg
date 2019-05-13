
var TextBlock = {};

( function( $, editor, block ) {

    $.extend( block, {

        blockType: 'text',
        pFont: null,
        pSize: null,
        h1Font: null,
        h1Size: null,
        h2Font: null,
        h2Size: null,
        richText: null,

        init : function () {

            var self = this;

            this.pFont  = $( '#p-font' );
            this.pFont.on( 'change', function ( e ) {
                editor.getActive().find('.simple-editor-content').css('font-family', $(this).val() );
            });

            this.pSize  = $( '#p-size' );
            this.pSize.on( 'change', function ( e ) {
                editor.getActive().find('.simple-editor-content').css('font-size', $(this).val() + 'px' );
            });

            this.h1Font = $( '#h1-font' );
            this.h1Font.on( 'change', function ( e ) {
                editor.getActive().find('h1').css('font-family', $(this).val() );
            });

            this.h1Size = $( '#h1-size' );
            this.h1Size.on( 'change', function ( e ) {
                editor.getActive().find('h1').css('font-size', $(this).val() + 'px' );
            });

            this.h2Font = $( '#h2-font' );
            this.h2Font.on( 'change', function ( e ) {
                editor.getActive().find('h2').css('font-family', $(this).val() );
            });

            this.h2Size = $( '#h2-size' );
            this.h2Size.on( 'change', function ( e ) {
                editor.getActive().find('h2').css('font-size', $(this).val() + 'px' );
            });

            $(document).on( 'madeActive', function (e, block, blockType ) {

                self.destroyEditor();
                if ( self.blockType === blockType ){
                    self.parse( block );
                }

            });

            $(document).on( 'madeInactive', function ( e ) {self.destroyEditor();});
            $(document).on( 'duplicateBlock', function ( e ) {self.destroyEditor();});

        },

        createEditor: function (){

            this.richText = editor.getActive().find('.content-wrapper');
            this.richText.simpleEditor({
                defaultParagraphSeparator: 'p',
                actions: ["bold", "italic", "underline", "color", "strikethrough", "responsiveAlign", "alignLeft", "alignCenter", "alignRight", "alignJustify", "responsiveHeadings", "heading1", "heading2", "olist", "ulist", "paragraph", "link", "unlink"]
            });

            this.placeActionBar();

            $(document).scroll( function (e) {
                block.placeActionBar();
            } );

        },

        placeActionBar: function(){
            var $actionBAr = $( '.simple-editor-actionbar' );
            $actionBAr.width( $( '#email-body' ).width() );

            var yoffset;
            var xoffset;

            if ( ! editor.inFrame() ){
                yoffset = 32;
                xoffset = 160;
            } else {
                xoffset = 0;
                yoffset = 0;
            }

            // $actionBAr.css( 'top', $( '#editor' ).offset().top - offset );
            if ( window.pageYOffset > $( '#editor' ).offset().top - ( 48 + yoffset ) ){
                $actionBAr.css( 'position', 'fixed' );
                $actionBAr.css( 'top',  ( 46 + yoffset ) + 'px');
                $actionBAr.css( 'left', xoffset + 'px' );
            } else {
                $actionBAr.css( 'position', 'absolute' );
                $actionBAr.css( 'top', $( '#editor' ).offset().top - yoffset );
                $actionBAr.css( 'left', 0 );
            }

        },

        destroyEditor: function(){
            if ( this.richText ){
                this.richText.simpleEditor().destroy();
                this.richText = null;
            }
        },

        /**
         * A jquery implement block.
         *
         * @param block $
         */
        parse: function ( block ) {

            this.createEditor();

            this.pFont.val( block.find('.simple-editor-content').css( 'font-family' ).replace(/"/g, '') );
            this.pSize.val( block.find('.simple-editor-content').css( 'font-size' ).replace('px', '') );
            try{ this.h1Font.val( block.find('h1').css( 'font-family' ).replace(/"/g, '') ); } catch (e){}
            try{ this.h1Size.val( block.find('h1').css( 'font-size' ).replace('px', '') ); } catch (e){}
            try{ this.h2Font.val( block.find('h2').css( 'font-family' ).replace(/"/g, '') ); } catch (e){}
            try{ this.h2Size.val( block.find('h2').css( 'font-size' ).replace('px', '') ); } catch (e){}

        }


    } );

    $(function(){
        block.init();
    })

})( jQuery, EmailEditor, TextBlock );