<?php
if ( !defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Pagfort Boleto Gateway Class.
 */
class Pagfort_Boleto_Gateway extends WC_Payment_Gateway {

        const PAGFORT_PREFIX = 'pagfort-boleto';
        const PAGFORT_PREFIX_ID = 'pagfort_boleto';

        /**
         * Initialize gateway.
         */
        public function __construct() {
                $this->ws_api = null;
                $this->id = self::PAGFORT_PREFIX_ID;
                $this->icon = apply_filters( $this->id . '-icon', plugins_url( 'assets/images/boleto-icon.png', plugin_dir_path( __FILE__ ) ) );
                $this->has_fields = false;
                $this->method_title = 'Pagfort';
                $this->method_description = 'O Pagfort Boleto, permite que sua loja aceite pagamentos utilizando boleto bancário.';
                $this->order_button_text = 'Finalizar compra';

                // Load the settings.
                $this->pagfort_admin_config_fields();
                $this->init_settings();

                // Define user settings variables.
                $this->title = $this->get_option( 'title' );
                $this->description = $this->get_option( 'description' );
                $this->default_due_date = $this->get_option( 'default_due_date' );
                $this->discount_payment = $this->get_option( 'discount_payment' );
                $this->allow_billing = $this->get_option( 'allow_billing' );

                // Define pagfort settings
                $this->sandbox_enable = $this->get_option( 'sandbox_enable' );
                $this->debug_pagfort = $this->get_option( 'debug_pagfort' );
                $this->sandbox_login = $this->get_option( 'sandbox_login' );
                $this->sandbox_password = $this->get_option( 'sandbox_password' );
                $this->production_login = $this->get_option( 'production_login' );
                $this->production_password = $this->get_option( 'production_password' );

                $this->checkout_fields_for_brazil = 0;
                $this->pagfort_init_actions();

                $this->pagfort_verify_checkout_fields_for_brazil();
        }

        /**
         * Init actions gateway
         */
        private function pagfort_init_actions() {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_cart_calculate_fees', array( $this, 'pagfort_add_discount' ), 20, 1 );
                add_action( 'woocommerce_review_order_before_payment', array( $this, 'pagfort_refresh_payment_method' ) );
        }

        /**
         * Init admin config form fields.
         */
        public function pagfort_admin_config_fields() {
                $fields = new Pagfort_Boleto_Fields();
                $config_fields = $fields->get_admin_config_fields();
                $this->form_fields = array_merge( $config_fields );
        }

        /**
         * Get gateway settings
         * @return array
         */
        protected function getSettings() {
                return $this->settings;
        }

        /**
         * Checks if sandbox environment is enabled and shows warning message at checkout
         */
        public function payment_fields() {

                if ( $this->description ) {
                        if ( $this->discount_payment > 0 ) {
                                if ( get_locale() == 'pt_BR' )
                                        $this->description .= sprintf( esc_html__( '%s  %s%s de desconto no boleto bancário%s' ), '<p class="pagfort-sandbox-message italic-message">', $this->discount_payment, '%', '</p>' );
                                else
                                        $this->description .= sprintf( esc_html__( '%s  %s%s discount on bank slip%s' ), '<p class="pagfort-sandbox-message italic-message">', $this->discount_payment, '%', '</p>' );
                        }
                        if ( 'yes' == $this->sandbox_enable ) {
                                if ( get_locale() == 'pt_BR' )
                                        $this->description .= sprintf( __( '%s %sMODO DE TESTE:%s  Quando esta configuração estiver ativada, não serão gerados boletos, somente uma mensagem será apresentada informando o sucesso ou falha da operação.%s', $this->id ), '<p class="pagfort-sandbox-message">', '<strong>', '</strong>', '</p>', '</p>' );
                                else //if ( get_locale() == 'en_US' )
                                        $this->description .= sprintf( __( '%s %sSANDBOX MODE:%s  When this setting is enabled, no slips will be generated, only a message will be displayed stating the success or failure of the operation.%s', $this->id ), '<p class="pagfort-sandbox-message">', '<strong>', '</strong>', '</p>' );
                                $this->description = trim( $this->description );
                        }
                        echo wpautop( wp_kses_post( $this->description ) );
                }
        }

        /**
         * Check if Extra_Checkout_Fields_For_Brazil is installed
         */
        public function pagfort_verify_checkout_fields_for_brazil() {
                if ( class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
                        add_filter( 'woocommerce_checkout_fields', array( $this, 'pagfort_override_checkout_fields_for_brazil' ) );
                        $this->checkout_fields_for_brazil = 1;
                } else {
                        add_action( 'admin_notices', array( $this, 'pagfort_notice_extra_checkout_fields_for_brazil' ) );
                }
        }

        /**
         * Show the message about missing plugin installation
         */
        public function pagfort_notice_extra_checkout_fields_for_brazil() {
                $msg = $this->checkout_fields_for_brazil ? __( 'The Brazilian Market on WooCommerce Plugin (Extra Checkout Fields For Brazil) is installed!', $this->id ) : __( 'The Brazilian Market on WooCommerce Plugin (Extra Checkout Fields For Brazil) is not installed!', $this->id );
                $type = $this->checkout_fields_for_brazil ? 'success' : 'error';
                $prefix = $this->id;
                include_once 'views/html-notice-extra-checkout-fields-brazil-detected.php';
        }

        /**
         * Override configuration checkout fields for brazil
         * @param array $fields
         * @return array
         */
        public function pagfort_override_checkout_fields_for_brazil( $fields ) {
                if ( $this->checkout_fields_for_brazil ) {
                        $fields[ 'billing' ][ 'billing_neighborhood' ][ 'required' ] = true;
                        $fields[ 'billing' ][ 'billing_company' ][ 'label' ] = "Razão Social";
                        $fields[ 'billing' ][ 'billing_company' ][ 'placeholder' ] = "Informe a razão social da empresa";
                        return $fields;
                }
        }

        /**
         * Add discount if enabled
         * @param object $cart_object
         */
        public function pagfort_add_discount( $cart_object ) {

                if ( is_admin() && !defined( 'DOING_AJAX' ) || is_cart() )
                        return;

                if ( is_user_logged_in() ) {
                        $payment_method = self::PAGFORT_PREFIX_ID;
                        $percent = $this->discount_payment;
                        $cart_total = $cart_object->subtotal_ex_tax;
                        $chosen_payment_method = WC()->session->get( 'chosen_payment_method' );

                        if ( $payment_method == $chosen_payment_method && $this->discount_payment > 0 ) {
                                $label_text = __( "Discount", self::PAGFORT_PREFIX );
                                $discount = ($cart_total / 100) * $percent;
                                $cart_object->add_fee( $label_text, -$discount, false );
                        }
                }
        }

        /**
         * Refresh total value when change payment method in checkout
         */
        public function pagfort_refresh_payment_method() {
                wp_enqueue_script( self::PAGFORT_PREFIX . '-client' );
        }

        /**
         * Validate fields from checkout form
         * @return boolean
         */
        public function validate_fields() {
                $post = $_POST;
                if ( $post[ 'payment_method' ] == $this->id ) {
                        if ( !$this->checkout_fields_for_brazil ) {
                                wc_add_notice( "O Plugin Brazilian Market on WooCommerce (Extra Checkout Fields For Brazil) não está instalado", 'error' );
                                return false;
                        }
                        if ( !is_checkout_pay_page() ) {
                                $post[ 'prefix' ] = self::PAGFORT_PREFIX;
                                $msg = Pagfort_Boleto_Fields::pagfort_validate_fields( $post, false );

                                if ( count( $msg ) ) {
                                        $this->pagfort_add_log( serialize( $msg ) );
                                        wc_add_notice( join( '<br/>', $msg ), 'error' );
                                        return false;
                                }
                        }
                }
        }

        /**
         * Proceed with checkout payment
         * @global object $woocommerce
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
                global $woocommerce;
                $response = array(
                    'result'   => 'fail',
                    'redirect' => '',
                );

                $this->ws_api = new Pagfort_Ws_Api();
                $order = wc_get_order( $order_id );

                if ( $order->get_payment_method() == $this->id ) {
                        try {
                                $response_payload = $this->ws_api->pagfort_payload_ws( $order_id );
                                if ( $response_payload[ 'result' ] ) {
                                        $this->pagfort_save_client_post_meta( $response_payload, $order_id );
                                        $order->update_status( 'on-hold', __( 'Awaiting payment of the bank slip', 'pagfort-boleto' ) );

                                        if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
                                                WC()->cart->empty_cart();
                                                $url = $order->get_checkout_order_received_url();
                                        } else {
                                                $woocommerce->cart->empty_cart();
                                                $url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
                                        }

                                        $response[ 'result' ] = 'success';
                                        $response[ 'redirect' ] = $url;
                                        return $response;
                                } else {
                                        $order->update_status( 'failed', __( 'The bank slip could not be generated', 'pagfort-boleto' ) );
                                        $response[ 'cod' ] = $response_payload[ 'cod_msg' ];
                                        $response[ 'msg' ] = $response_payload[ 'msg' ];
                                        $response[ 'redirect' ] = $order->get_cancel_order_url();
                                        return $this->pagfort_process_payment_error( $response );
                                }
                        } catch ( Exception $exc ) {
                                $this->pagfort_add_log( $exc->getTraceAsString() );
                                wc_add_notice( serialize( $exc->getTraceAsString() ), 'error' );
                                return $response;
                        }
                }
                return $response;
        }

        /**
         * Save post meta from data api result
         * @param type $send
         * @param type $order_id
         */
        private function pagfort_save_client_post_meta( $send, $order_id ) {
                $data = array(
                    'cod_documento'   => $send[ 'cod_documento' ],
                    'valor_numerico'  => $send[ 'valor_numerico' ],
                    'n_do_documento'  => $send[ 'n_do_documento' ],
                    'vencimento'      => $send[ 'vencimento' ],
                    'data_emissao'    => $send[ 'data_emissao' ],
                    'cod_status'      => 1,
                    'status'          => "ABERTO",
                    'url_pagamento'   => $send[ 'url_pagamento' ],
                    'linha_digitavel' => $send[ 'linha_digitavel' ]
                );
                update_post_meta( $order_id, $this->id . '_data', $data );
        }

        /**
         * Add log to woocommerce logs
         * @param string $message
         */
        public function pagfort_add_log( $message ) {
                $log = function_exists( 'wc_get_logger' ) ? wc_get_logger() : new WC_Logger();
                if ( 'yes' == $this->get_option( 'debug_pagfort' ) )
                        $log->add( $this->id, $message );
        }

        /**
         * Fail generate process payment
         * @param type $error
         * @return string
         */
        private function pagfort_process_payment_error( $error ) {
                $notice = '<p><strong>' . $error[ 'msg' ] . '</strong></p>';
                if ( 27 === $error[ 'cod' ] ) {
                        $notice = '<p>' . __( 'The bank slip could not be generated' ) . '</p>';
                        $notice .= '<p><a href="' . esc_url( $error[ 'redirect' ] ) . '">' . __( 'Cancel order', $this->id ) . '</a>'
                                . __( ' and try again, if you continue to experience problems, contact your store administrator!', $this->id ) . ' </p>';
                }
                $response[ 'result' ] = 'fail';
                wc_add_notice( $notice, 'error' );
                return $response;
        }

        /**
         * Generate Button HTML.
         *
         * @access public
         * @param mixed $key
         * @param mixed $data_html
         * @since 1.0.0
         * @return string
         */
        public function generate_button_html( $key, $data_html ) {
                $field = $this->plugin_id . $this->id . '_' . $key;
                $defaults = array(
                    'class'             => 'button-secondary',
                    'css'               => '',
                    'custom_attributes' => array(),
                    'desc_tip'          => false,
                    'description'       => '',
                    'title'             => '',
                );
                $data = wp_parse_args( $data_html, $defaults );
                ob_start();
                ?>
                <tr valign="top">
                  <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data[ 'title' ] ); ?></label>
                    <?php echo $this->get_tooltip_html( $data ); ?>
                  </th>
                  <td class="forminp">
                    <fieldset>
                      <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data[ 'title' ] ); ?></span></legend>
                      <button class="<?php echo esc_attr( $data[ 'class' ] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data[ 'css' ] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><span class="dashicons dashicons-admin-plugins"></span> <?php echo wp_kses_post( $data[ 'title' ] ); ?></button>
                      <?php echo $this->get_description_html( $data ); ?>
                    </fieldset>
                  </td>
                </tr>
                <?php
                return ob_get_clean();
        }

}
