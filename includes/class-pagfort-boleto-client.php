<?php

if ( !defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Boleto Admin.
 */
class Pagfort_Boleto_Client {

        /**
         * Initialize the client.
         */
        public function __construct() {

                $this->gateway = new Pagfort_Boleto_Gateway();
                $this->prefix = Pagfort_Boleto_Gateway::PAGFORT_PREFIX;
                $this->prefix_id = Pagfort_Boleto_Gateway::PAGFORT_PREFIX_ID;
                $this->sandbox_enable = "yes" == $this->gateway->settings[ 'sandbox_enable' ] ? true : false;

                add_action( 'woocommerce_thankyou_' . $this->prefix_id, array( $this, 'thankyou_page' ) );
                add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 2 );
                add_action( 'init', array( $this, 'pagfort_enqueue_client_script' ) );
                add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_details_ticket_account_details_on_view_order' ), 5, 1 );

        }

        /**
         * Enqueue client scripts
         */
        public function pagfort_enqueue_client_script() {
                wp_enqueue_style( 'checkout_styles', plugins_url( $this->prefix . '/assets/css/' . $this->prefix_id . '_styles.css' ) );

                wp_register_script( 'clipboard', plugins_url( 'assets/js/clipboard.min.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), '2.0.4', true );
                wp_enqueue_script( 'clipboard' );

                $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

                wp_register_script( $this->prefix . '-client', plugins_url( 'assets/js/' . $this->prefix_id . '_client' . $min . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), Pagfort_Boleto::VERSION, true );
                wp_register_script( 'boleto-codebar', plugins_url( 'assets/js/' . $this->prefix_id . '_codebar' . $min . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), Pagfort_Boleto::VERSION, true );
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'boleto-codebar' );
                wp_localize_script( $this->prefix . '-client', 'data', array() );
        }

        /**
         * Redirect to thankyou page when finnaly payment
         * @param int $order_id
         */
        public function thankyou_page( $order_id ) {
                $postmeta = get_post_meta( $order_id, $this->prefix_id . '_data', true );
                $this->pagfort_thankyou_template( $postmeta );
        }

        /**
         * Template for thankyou page
         * @param array $postmeta
         */
        public function pagfort_thankyou_template( $postmeta ) {
                $wc_templates_path = Pagfort_Boleto::get_plugin_path() . '/templates/woocommerce/';

                wc_get_template( 'checkout/' . $this->prefix . '/bank-slip/thankyou.php', array(
                    'prefix_id'       => $this->prefix_id,
                    'prefix'          => $this->prefix,
                    'sandbox_enable'  => $this->sandbox_enable,
                    'vencimento'      => implode( '/', array_reverse( explode( '-', $postmeta[ 'vencimento' ] ) ) ),
                    'url_pagamento'   => $postmeta[ 'url_pagamento' ],
                    'linha_digitavel' => $postmeta[ 'linha_digitavel' ],
                    'barcode_style'   => true, //"yes" == $this->gateway->settings[ 'barcode_style' ] ? true : false,
                    'barcode'         => $this->generate_barcode( $postmeta[ 'linha_digitavel' ] )
                        ), '', $wc_templates_path );
        }

        /**
         * Template for order email
         * @param object $order
         * @param boolean $sent_to_admin
         * @return string
         */
        public function email_instructions( $order, $sent_to_admin ) {
                if ( $sent_to_admin || 'on-hold' !== $order->get_status() || $this->prefix_id !== $order->get_payment_method() ) {
                        error_log( print_r( "EMAIL_INSTRUCTIONS CLIENT FAIL", true ) );
                        return;
                }


                $wc_templates_path = Pagfort_Boleto::get_plugin_path() . '/templates/woocommerce/';
                $ticket_data = get_post_meta( $order->get_order_number(), $this->prefix_id . '_data', true );
                $linha_digitavel = '';

                if ( $ticket_data ) {
                        $linha_digitavel = $ticket_data[ 'linha_digitavel' ];

                        wc_get_template( 'checkout/' . $this->prefix . '/bank-slip/email.php', array(
                            'prefix'          => $this->prefix,
                            'sandbox_enable'  => $this->sandbox_enable,
                            'data_emissao'    => implode( '/', array_reverse( explode( '-', $ticket_data[ 'data_emissao' ] ) ) ),
                            'vencimento'      => implode( '/', array_reverse( explode( '-', $ticket_data[ 'vencimento' ] ) ) ),
                            'cod_status'      => $ticket_data[ 'cod_status' ],
                            'status'          => $ticket_data[ 'status' ],
                            'url_pagamento'   => $ticket_data[ 'url_pagamento' ],
                            'linha_digitavel' => $ticket_data[ 'linha_digitavel' ],
                            'barcode_style'   => true, //"yes" == $this->gateway->settings[ 'barcode_style' ] ? true : false,
                            'barcode'         => $this->generate_barcode( $ticket_data[ 'linha_digitavel' ] )
                                ), '', $wc_templates_path );
                }
        }

        /**
         * Show the details of the bank slip on the customer's order page
         * @param type $order
         */
        public function display_details_ticket_account_details_on_view_order( $order ) {
                $wc_templates_path = Pagfort_Boleto::get_plugin_path() . '/templates/woocommerce/';
                $ticket_data = get_post_meta( $order->get_order_number(), $this->prefix_id . '_data', true );
                $linha_digitavel = '';
                if ( $order->get_payment_method() == $this->prefix_id ) {

                        if ( $ticket_data ) {
                                $linha_digitavel = $ticket_data[ 'linha_digitavel' ];

                                wc_get_template( 'checkout/' . $this->prefix . '/details-ticket/details.php', array(
                                    'prefix_id'       => $this->prefix_id,
                                    'prefix'          => $this->prefix,
                                    'sandbox_enable'  => $this->sandbox_enable,
                                    'data_emissao'    => implode( '/', array_reverse( explode( '-', $ticket_data[ 'data_emissao' ] ) ) ),
                                    'vencimento'      => implode( '/', array_reverse( explode( '-', $ticket_data[ 'vencimento' ] ) ) ),
                                    'cod_status'      => $ticket_data[ 'cod_status' ],
                                    'status'          => $ticket_data[ 'status' ],
                                    'url_pagamento'   => $ticket_data[ 'url_pagamento' ],
                                    'linha_digitavel' => $ticket_data[ 'linha_digitavel' ],
                                    'barcode_style'   => true, //"yes" == $this->gateway->settings[ 'barcode_style' ] ? true : false,
                                    'barcode'         => $this->generate_barcode( $ticket_data[ 'linha_digitavel' ] )
                                        ), '', $wc_templates_path );
                        }
                }

                wp_enqueue_script( $this->prefix . '-client' );
                wp_localize_script( $this->prefix . '-client', 'data', array(
                    'linha_digitavel' => $linha_digitavel,
                    'wp_nonce'        => wp_create_nonce( $this->prefix )
                ) );
        }

        /**
         * Generate barcode from digitable line
         * @param string $barcode
         * @param int $widthFactor
         * @param int $totalHeight
         * @param string $color
         * @return type
         */
        private function generate_barcode( $barcode, $widthFactor = 2, $totalHeight = 70, $color = 'black' ) {
                $code = preg_replace( '/[^0-9]/', '', $barcode );
                $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
                return $generator->getBarcode( $code, $generator::TYPE_INTERLEAVED_2_5, $widthFactor, $totalHeight, $color );
        }

}

new Pagfort_Boleto_Client();
