jQuery( document ).ready( function ( $ ) {

        const extId = "#woocommerce_" + data_enqueue.prefix_id;

        $( `${extId}_btn_test_connection` ).click( function () {
                show_message( '', data_enqueue.await_notice );
                var data = {
                        action: 'pagfort_post_test_connection',
                        security: data_enqueue.pagfort_nonce
                };

                $.ajax( {
                        type: 'POST',
                        url: data_enqueue.url,
                        data: data,
                        success: function ( data ) {
                                handle_response( data );
                        },
                        error: function ( data ) {
                                handle_response( false );

                        }
                } );

                function handle_response( data ) {
                        if ( data )
                                show_message( 'notice-success', data_enqueue.success_notice );
//                                $( $( `${extId}_connection_message` ) ).html( `<div class="notice notice-success is-dismissible"><p>${data_enqueue.success_notice}</p></div>` );
                        else
                                show_message( 'notice-error', data_enqueue.error_notice );
//                                $( $( `${extId}_connection_message` ) ).html( `<div class="notice notice-error is-dismissible"><p>${data_enqueue.error_notice}</p></div>` );
                }

                function show_message( type, msg ) {
                        $( $( `${extId}_connection_message` ) ).html( `<div class="notice ${type} is-dismissible"><p>${msg}</p></div>` );
                }

        } );

        $( 'button.save_order' ).on( 'click', function ( e ) {

                var post = $( '#post' ).serializeArray().reduce( function ( obj, item ) {
                        obj[item.name] = item.value;
                        return obj;
                }, { } );

                if ( data_enqueue.prefix_id == post._payment_method ) {
                        wp_block();

                        e.preventDefault();

                        var data = {
                                post: post,
                                action: 'pagfort_post_order_validate',
                                security: data_enqueue.pagfort_nonce
                        };

                        $.ajax( {
                                url: data_enqueue.url,
                                data: data,
                                type: 'POST',
                                success: function ( resp ) {
                                        handle_response( resp );
                                },
                                error: function ( error ) {
                                        console.log( "ERROR" );
                                        console.log( error );
                                },
                                complete: function () {
                                        $( '#wpbody' ).unblock();
                                }
                        } );

                }

                function handle_response( resp ) {
                        if ( !resp.valid ) {
                                var msg = '';
                                resp.msg.map( function ( num ) {
                                        msg += num + "\n";
                                } );
                                alert( msg );
                        } else {
                                $( 'form#post' ).submit();
                        }
                }

        } );

        $( 'select#_payment_method' ).on( 'change', function () {
                var sel = $( this );
                if ( 'pagfort_boleto' === sel.val() ) {
                        wp_block();
                        var data = {
                                action: 'pagfort_post_add_discount',
                                security: data_enqueue.pagfort_nonce
                        };

                        $.post( data_enqueue.url, data, function ( response ) {
                                $( '#wpbody' ).unblock();
                                var result = jQuery.parseJSON( response );
                                if ( result )
                                        sel.prev().append( "<span id='span_discount'><strong> " + result + '% de desconto</strong></span>' );

                        } );
                        return false;
                } else
                        $( 'span#span_discount' ).remove();


        } );


        $( '#_billing_persontype' ).on( 'change', function () {
                var selected = $( this ).val();
                if ( '0' !== selected ) {
                        '1' === selected ? $( '#_billing_cpf' ).mask( '000.000.000-00' ) : $( '#_billing_cnpj' ).mask( '00.000.000/0000-00' );
                }
        } );


        $( 'a#update_order_pagfort' ).on( 'click', function () {
                var status = $( this ).attr( 'status' );
                var value = $( this ).attr( 'value' );
                if ( window.confirm( data_enqueue.confirm + status + "?" ) ) {
                        jQuery( "#order_status" ).val( value ).change();
                        $( 'form#post' ).submit();
                }
        } );

        function wp_block() {
                $( '#wpbody' ).block( {
                        message: null,
                        overlayCSS: {
                                background: '#fff',
                                opacity: 0.6
                        }
                } );
        }

} );



