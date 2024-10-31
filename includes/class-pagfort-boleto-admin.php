<?php

if ( !defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Boleto Admin.
 */
class Pagfort_Boleto_Admin {

        /**
         * Initialize admin.
         */
        public function __construct() {

                $this->prefix = Pagfort_Boleto_Gateway::PAGFORT_PREFIX;
                $this->prefix_id = Pagfort_Boleto_Gateway::PAGFORT_PREFIX_ID;
                $this->pagfort_version = Pagfort_Boleto::VERSION;
                $this->pagfort_nonce = $this->pagfort_get_nonce();
                $this->gateway = new Pagfort_Boleto_Gateway();
                $this->sandbox_enable = $this->get_sandbox_status();
                $this->ws_api = new Pagfort_Ws_Api();
                $this->pagfort_init_actions();
        }

        /**
         * Init actions admin
         */
        private function pagfort_init_actions() {
                add_action( 'admin_enqueue_scripts', array( $this, 'pagfort_enqueue_admin_script' ) );
                add_action( 'wp_ajax_pagfort_post_test_connection', array( $this, 'pagfort_post_test_connection' ) );
                add_action( 'wp_ajax_pagfort_post_add_discount', array( $this, 'pagfort_post_add_discount' ) );
                add_action( 'add_meta_boxes', array( $this, 'pagfort_register_metabox' ) );
                add_action( 'wp_ajax_pagfort_post_order_validate', array( $this, 'pagfort_post_order_validate' ) );
        }

        /**
         * Register metabox where show info bank slip.
         */
        public function pagfort_register_metabox() {
                add_meta_box(
                        $this->prefix, __( 'Pagfort Banking slip', $this->prefix ), array( $this, 'pagfort_update_metabox_content' ), 'shop_order', 'side', 'default' );
        }

        /**
         * Prepared data to metabox content
         * @param array $post
         */
        public function pagfort_update_metabox_content( $post ) {
                $postmeta = get_post_meta( $post->ID, $this->prefix_id . '_data', true );
                if ( $postmeta ) {
                        $data_ws = $this->ws_api->pagfort_get_order_ws( $postmeta[ 'n_do_documento' ] );
                        if ( $data_ws ) {
                                $postmeta[ 'cod_status' ] = $data_ws[ 'cod_status' ];
                                $postmeta[ 'status' ] = $data_ws[ 'status' ];
                                if ( isset($data_ws[ 'valor_pago' ]))
                                        $postmeta[ 'valor_pago' ] = $data_ws[ 'valor_pago' ];
                                $this->pagfort_check_status_ws( $data_ws, $postmeta );
                                update_post_meta( $post->ID, $this->prefix_id . '_data', $postmeta );
                        }
                }

                $this->pagfort_show_metabox_content( $post->ID );
        }

        /**
         * Check status bank slip in api before show metabox
         * @param array $data_ws
         * @param array $postmeta
         */
        private function pagfort_check_status_ws( $data_ws, $postmeta ) {
                $ndoc = explode( '#', $data_ws[ 'n_do_documento' ] );
                $order = wc_get_order( $ndoc[ 0 ] );
                $status_woo = '';
                $status = '';

                if ( $order->get_status() == 'on-hold' ) {

                        if ( $postmeta[ 'cod_status' ] != 1 ) {
                                $status_woo = 'wc-cancelled';
                                $status = __( 'Cancelled', $this->prefix );
                        }

                        if ( $postmeta[ 'cod_status' ] == 31 ) {
                                $status_woo = 'wc-processing';
                                $status = __( 'Processing', $this->prefix );
                        }
                } elseif ( in_array( $order->get_status(), array( 'cancelled', 'failed', 'refunded', 'pending' ) ) ) {

                        if ( $postmeta[ 'cod_status' ] != 33 ) {
                                $status_woo = 'wc-cancelled';
                                $status = __( 'Cancelled', $this->prefix );
                        }

                        if ( in_array( $postmeta[ 'cod_status' ], array( 31 ) ) ) {
                                $status_woo = 'wc-processing';
                                $status = __( 'Processing', $this->prefix );
                        }
                }
                $message = '<ul class="order_notes">
				<li class="note system-note">
                                        <div class="note_content" style="border-radius:2px">
                                                <p><strong>' . $postmeta[ 'status' ] . '</strong></p>
                                                <p>' . __( "Order status not updated, please update the order to:", $this->prefix ) . ' <strong>' . $status . '</strong>.</p>
                                        </div>
                                        <p class="meta">
                                                <abbr class="" title="">' . date_i18n( 'd/m/Y H:i:s' ) . '</abbr>
                                                        <a href="#" id="update_order_pagfort" status="' . $status . '" value="' . $status_woo . '" role="button">' . __( 'Update order', $this->prefix ) . '</a>
                                        </p>
                                </li>		
			</ul><hr/>';
                echo $status ? $message : '';

                $this->pagfort_check_value_pay( $data_ws, $postmeta );
        }

        /**
         * Check if values are differents and show message for admin
         * @param array $data_ws
         * @param array $postmeta
         */
        private function pagfort_check_value_pay( $data_ws, $postmeta ) {
                if ( $postmeta[ 'cod_status' ] == 31 ) {
                        if ( $data_ws[ 'valor_pago' ] != $postmeta[ 'valor_numerico' ] ) {
                                $message = 
                        '<ul class="order_notes">
				<li class="note system-note">
                                        <div class="note_content" style="border-radius:2px">
                                                <p><strong>' . __( "ATTENTION", $this->prefix ) . '</strong></p>
                                                <p>' . __( "The payment and the value are different! The payment was", $this->prefix ) . '
                                                <strong>R$ ' . number_format_i18n( $postmeta[ 'valor_pago' ], 2 ) . '</strong> 
                                                ' . __( "instead of ", $this->prefix ) . '
                                                <strong>R$ ' . number_format_i18n( $postmeta[ 'valor_numerico' ], 2 ) . '.</strong>      
                                                </p>
                                        </div>
                                        <p class="meta">
                                                <abbr class="" title="">' . date_i18n( 'd/m/Y H:i:s' ) . '</abbr>
                                        </p>
                                </li>		
			</ul><hr/>';
                                echo $message;
                        }
                }
        }

        /**
         * Show metabox content
         * @param int $order_id
         */
        private function pagfort_show_metabox_content( $order_id ) {
                $order = wc_get_order( $order_id );
                $html = $link = '';
                $postmeta = get_post_meta( $order_id, $this->prefix_id . '_data', true );
                $vlr_pago = "0,00";
                
                if ( $this->prefix_id == $order->get_payment_method() ) {
                        if(isset($postmeta[ 'valor_pago' ]))
                                $vlr_pago = number_format_i18n( $postmeta[ 'valor_pago' ], 2 );
                        if ( $postmeta ) {
                                $html .= '<p><strong>' . __( 'Payment: ', $this->prefix ) . '</strong> ' . $postmeta[ 'status' ] . '</p>';
                                $html .= '<p><strong>' . __( 'Value: ', $this->prefix ) . '</strong>R$ ' . number_format_i18n( $postmeta[ 'valor_numerico' ], 2 ) . '</p>';
                                $html .= '<p><strong>' . __( 'Paid: ', $this->prefix ) . '</strong>R$ ' . $vlr_pago . '</p>';
                                $html .= '<p><strong>' . __( 'Issued on: ', $this->prefix ) . '</strong> ' . implode( '/', array_reverse( explode( '-', $postmeta[ 'data_emissao' ] ) ) ) . '</p>';
                                $html .= '<p><strong>' . __( 'Expiration on: ', $this->prefix ) . '</strong> ' . implode( '/', array_reverse( explode( '-', $postmeta[ 'vencimento' ] ) ) ) . '</p>';
                                $html .= '<p><strong>' . __( 'Code: ', $this->prefix ) . '</strong> ' . $postmeta[ 'cod_documento' ] . '</p>';
                                $html .= '<p><strong>' . __( 'Number: ', $this->prefix ) . '</strong> ' . $postmeta[ 'n_do_documento' ] . '</p>';
                                $html .= '<p style="border-top: 1px solid #e9e9e9;"></p>';
                                $url = esc_url( $postmeta[ 'url_pagamento' ] );
                                $link = '<p><strong>' . __( 'Link:', $this->prefix ) . '</strong> <a target="_blank" href="' . $url . '">' . __( 'Print bank slip', $this->prefix ) . '</a></p>';

                                if ( $this->sandbox_enable ) {
                                        if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
                                                $link = '<p><strong>' . __( 'SANDBOX ENVIROMENT', $this->prefix ) . '</strong> </p>';
                                        }
                                }
                                $html .= $link;
                        }
                } else {
                        $html = '<p>' . __( 'This order was not placed or paid by bank slip.', $this->prefix ) . '</p>';
                        $html .= '<style>#woocommerce-boleto.postbox {display: block;}</style>';
                }

                echo $html;
        }

        /**
         * Enqueue script for admin page
         * @param string $hook
         */
        public function pagfort_enqueue_admin_script( $hook ) {
                $screen = get_current_screen();
                if ( in_array( $hook, array( 'woocommerce_page_wc-settings', 'woocommerce_page_woocommerce_settings' ) ) && ( isset( $_GET[ 'section' ] ) && $this->prefix_id == strtolower( $_GET[ 'section' ] ) ) || ( 'shop_order' === $screen->id ) ) {
                        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min'; //.min
                        wp_enqueue_style( $this->prefix . '-admin-styles', plugins_url( 'assets/css/' . $this->prefix_id . '_admin.css', plugin_dir_path( __FILE__ ) ), array(), $this->pagfort_version, true );
                        wp_enqueue_script( 'jquery-mask', plugins_url( 'assets/js/jquery.mask.min.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), '1.14.16', true );
                        wp_register_script( $this->prefix . '-admin-js', plugins_url( 'assets/js/' . $this->prefix_id . '_admin' . $min . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), $this->pagfort_version, true );

                        $data = array(
                            'url'            => admin_url( 'admin-ajax.php' ),
                            'prefix_id'      => $this->prefix_id . "",
                            'pagfort_nonce'  => wp_create_nonce( $this->pagfort_nonce ),
                            'await_notice'   => esc_html__( 'Wait a moment, trying to connect with the API ... ', $this->prefix ),
                            'error_notice'   => esc_html__( 'Could not connect to an API!', $this->prefix ),
                            'success_notice' => esc_html__( 'Your connection is working correctly!', $this->prefix ),
                            'confirm'        => esc_html__( 'Confirms order status update for ', $this->prefix )
                        );

                        wp_localize_script( $this->prefix . '-admin-js', 'data_enqueue', $data );
                        wp_localize_script( $this->prefix . '-client', 'data', array() );
                        wp_enqueue_script( $this->prefix . '-admin-js' );
                }
        }

        /**
         * Teste connection wit api server
         */
        public function pagfort_post_test_connection() {
                if ( !wp_verify_nonce( $_POST[ 'security' ], $this->pagfort_nonce ) ) {
                        error_log( print_r( "NONCE FALSE", true ) );
                        exit();
                }

                $valid = $this->ws_api->pagfort_get_token_ws();
                wp_send_json( $valid );
                die();
        }

        /**
         * Return discount value if exists and show in order form admin
         */
        public function pagfort_post_add_discount() {
                if ( !wp_verify_nonce( $_POST[ 'security' ], $this->pagfort_nonce ) ) {
                        error_log( print_r( "NONCE FALSE", true ) );
                        exit();
                }
                $discount = $this->gateway->settings;
                wp_send_json( $discount[ 'discount_payment' ] ?: 0  );
                die();
        }

        /**
         * Get nonce for requests
         * @return string nonce
         */
        private function pagfort_get_nonce() {
                return $this->prefix_id . '&' . $this->pagfort_version;
        }

        public function pagfort_post_order_validate() {
                $msg = array();
                $post = $_POST[ 'post' ];
                $order = wc_get_order( $post[ 'post_ID' ] );

                if ( !wp_verify_nonce( $_POST[ 'security' ], $this->pagfort_nonce ) ) {
                        error_log( print_r( "NONCE FALSE", true ) );
                        die();
                }

                check_ajax_referer( $this->pagfort_nonce, 'security' );

                if ( !current_user_can( 'edit_shop_orders' ) ) {
                        die( -1 );
                }

                if ( $post[ '_payment_method' ] == $this->prefix_id ) {
                        $msg = [];
                        if ( isset( $post[ 'customer_user' ] ) && $post[ 'customer_user' ] > 0 ) {
                                if ( $post[ 'order_status' ] != "wc-on-hold" ) {
                                        if ( !$order->get_date_created() )
                                                $msg[] = __( 'For issue bank slip, the status "WAITING" must be selected!', $this->prefix );
                                }
                                if ( $order->get_total() <= 0 ) {
                                        $msg[] = __( 'No items have been added to your order!', $this->prefix );
                                }
                                $post[ 'prefix' ] = $this->prefix;
                        } else {
                                $msg[] = __( "Select a customer!", $this->prefix );
                        }
                        $response = Pagfort_Boleto_Fields::pagfort_validate_fields( $post, true );
                        $merge = array_merge( $msg, $response );
                        if ( count( $merge ) ) {
                                wp_send_json( array( "valid" => 0, "msg" => $merge ) );
                        } else {
                                if ( $this->pagfort_payload_admin( $post ) ) {
                                        wp_send_json( array( "valid" => 1, "msg" => array() ) );
                                } else {
                                        wp_send_json( array( "valid" => 0, "msg" => array( 'WRONG' ) ) );
                                }
                        }
                } else {
                        wp_send_json( array( "valid" => 1, "msg" => array() ) );
                }

                die();
        }

        /**
         * 
         * @param type $post
         * @return boolean
         */
        private function pagfort_payload_admin( $post ) {
                $postmeta = get_post_meta( $post[ 'post_ID' ], $this->prefix_id . '_data', true );

                if ( $postmeta ) {

                        if ( 'editpost' == $post[ 'action' ] ) {
                                if ( '1' == $postmeta[ 'cod_status' ] ) {
                                        if ( in_array( $post[ 'order_status' ], array( 'wc-cancelled', 'wc-failed', ' wc-refunded' ) ) ) {

                                                $response = $this->ws_api->pagfort_edit_status( array(
                                                    'cpf_cnpj'       => $post[ '_billing_persontype' ] == '1' ? $post[ '_billing_cpf' ] : $post[ '_billing_cnpj' ],
                                                    'n_do_documento' => $postmeta[ 'n_do_documento' ] ) );

                                                if ( $response ) {
                                                        $postmeta[ 'cod_status' ] = 33;
                                                        $postmeta[ 'status' ] = 'CANCELADO';
                                                        $this->pagfort_save_admin_post_meta( $postmeta, $post[ 'post_ID' ] );
                                                }
                                        }
                                }
                        }
                        return true;
                } else {
                        $response_payload = $this->ws_api->pagfort_payload_admin_ws( $post );

                        if ( $response_payload[ 'result' ] ) {
                                $this->pagfort_update_user_meta_admin( $post );
                                $response_payload[ 'cod_status' ] = 1;
                                $response_payload[ 'status' ] = 'ABERTO';
                                $this->pagfort_save_admin_post_meta( $response_payload, $post[ 'post_ID' ] );
                                return true;
                        }
                }
                return false;
        }

        /**
         * Update user meta admin
         * @param array $post
         */
        public function pagfort_update_user_meta_admin( $post ) {
                if ( !empty( $post[ '_billing_number' ] ) )
                        update_user_meta( $post[ 'customer_user' ], 'billing_number', sanitize_text_field( $post[ '_billing_number' ] ) );

                if ( !empty( $post[ '_billing_neighborhood' ] ) )
                        update_user_meta( $post[ 'customer_user' ], 'billing_neighborhood', sanitize_text_field( $post[ '_billing_neighborhood' ] ) );
        }

        /**
         * Save result from api in post meta
         * @param array $send
         * @param int $order_id
         */
        public function pagfort_save_admin_post_meta( $send, $order_id ) {
                $postmeta = array(
                    'cod_documento'   => $send[ 'cod_documento' ],
                    'valor_numerico'  => $send[ 'valor_numerico' ],
                    'n_do_documento'  => $send[ 'n_do_documento' ],
                    'vencimento'      => $send[ 'vencimento' ],
                    'data_emissao'    => $send[ 'data_emissao' ],
                    'cod_status'      => $send[ 'cod_status' ],
                    'status'          => $send[ 'status' ],
                    'url_pagamento'   => $send[ 'url_pagamento' ],
                    'linha_digitavel' => $send[ 'linha_digitavel' ]
                );

                update_post_meta( $order_id, $this->prefix_id . '_data', $postmeta );
        }

        /**
         * Return status sandbox
         * @param obj $gateway
         * @return boolean
         */
        protected function get_sandbox_status() {
                return "yes" == $this->gateway->settings[ 'sandbox_enable' ] ? true : false;
        }

}

new Pagfort_Boleto_Admin();
