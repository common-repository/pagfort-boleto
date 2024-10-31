jQuery( document ).ready( function ( $ ) {

        $( window ).load( function () {


                $( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function () {
                        $( 'body' ).trigger( 'update_checkout' );
                } );

                if ( data ) {
                        var number = data.linha_digitavel;
                        if ( number ) {
                                var svg = new Boleto( number ).toSVG();
                                container = document.getElementById( "barcode_img" );
                                container.innerHTML = svg;
                        }

                }
                
                var clipboard = new ClipboardJS( '.button-copy-barcode', {
                        text: function ( trigger ) {
                                return trigger.getAttribute( 'aria-label' ).replace( /[^0-9]/g, "" );
                        }
                } );

                clipboard.on( 'success', function ( e ) {
                        e.clearSelection();
                } );
        } );
} );


