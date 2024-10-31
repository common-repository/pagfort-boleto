<?php

if ( !defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Pagfort_Ws_Api
 */
class Pagfort_Ws_Api {

        protected $URL_AUTH;
        protected $URL_DEV;
        protected $URL_DOC;
        protected $gateway;
        protected $pagfort_version;
        protected $prefix;
        protected $prefix_id;

        public function __construct() {

                $this->order_id = null;
                $this->ndoc = null;
                $this->status = new stdClass();
                $this->gateway = new Pagfort_Boleto_Gateway();
                $this->pagfort_version = Pagfort_Boleto::VERSION;
                $this->prefix_id = Pagfort_Boleto_Gateway::PAGFORT_PREFIX_ID;
                $this->prefix = Pagfort_Boleto_Gateway::PAGFORT_PREFIX;

                //Init rest endpoint for api request
                $this->pagfort_rest_endpoint_init();

                //Set default config api endpoint
                $this->pagfort_set_api_endpoint();
        }

        /**
         * Validate request data from api
         * @param WP_REST_Request $request
         * @return \WP_Error
         */
        public function pagfort_validate_auth_handler( WP_REST_Request $request ) {
                $auth = new WC_REST_Authentication();
                $user_rest = new stdClass();

                if (!isset($_SERVER['PHP_AUTH_USER']) && (isset($_SERVER['HTTP_AUTHORIZATION']) || isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))) {
                    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                        $header = $_SERVER['HTTP_AUTHORIZATION'];
                    } else {
                        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                    }

                    list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($header, 6)));
                }

                if ( isset( $_SERVER[ 'PHP_AUTH_USER' ] ) && isset( $_SERVER[ 'PHP_AUTH_PW' ] ) ) {

                        $user_rest->auth_user = sanitize_text_field( $_SERVER[ 'PHP_AUTH_USER' ] );
                        $user_rest->auth_pw = sanitize_text_field( $_SERVER[ 'PHP_AUTH_PW' ] );
                        $user_rest->basic = $auth->get_authorization_header();
                        $body = json_decode( $request->get_body() );

                        if ( ($body) && isset( $body->order_id ) ) {
                                $exp = explode( "#", $body->order_id );
                                $order_id = $exp[ 0 ];
                                $this->order_id = $order_id;
                                $this->ndoc = $body->order_id;
                                return $this->pagfort_verify_user_rest( $user_rest );
                        } else {
                                return new WP_Error( $this->prefix_id . '_error_rest', __( 'The body parameter must be a valid number.', $this->prefix_id ), array( 'status' => 401 ) );
                        }
                }
                return new WP_Error( $this->prefix_id . '_error_rest', __( 'Access denied', $this->prefix ), array( 'status' => 403 ) );
        }

        /**
         * Verify userdata is valid
         * @param type $usr
         * @return \WP_Error
         */
        private function pagfort_verify_user_rest( $usr ) {
                if ( !wc_get_order( $this->order_id ) )
                        return new WP_Error( $this->prefix_id . '_error_rest', __( 'Order number is not found or invalid.', $this->prefix_id ), array( 'status' => 403 ) );

                $postmeta = get_post_meta( $this->order_id, $this->prefix_id . '_data', true );

                if ( $postmeta[ 'n_do_documento' ] !== $this->ndoc )
                        return new WP_Error( $this->prefix_id . '_error_rest', __( 'Banks slip data number is not found or invalid.', $this->prefix_id ), array( 'status' => 403 ) );


                $user = $this->pagfort_get_user_data_by_consumer_key( $usr->auth_user );
                $hash1 = base64_encode( $usr->auth_user . ':' . $usr->auth_pw );
                $hash2 = explode( ' ', $usr->basic );

                if ( !$user )
                        return new WP_Error( $this->prefix_id . '_error_rest', __( 'Incorrect access information', $this->prefix ), array( 'status' => 401 ) );

                if ( !hash_equals( $user->consumer_secret, $usr->auth_pw ) )
                        return new WP_Error( $this->prefix_id . '_error_rest', __( 'Consumer secret is invalid.', $this->prefix_id ), array( 'status' => 401 ) );

                if ( $hash1 !== $hash2[ 1 ] )
                        return new WP_Error( $this->prefix_id . '_error_rest', __( 'Rest API keys are invalid.', $this->prefix_id ), array( 'status' => 401 ) );

                if ( 'read' !== $user->permissions )
                        return new WP_Error( $this->prefix_id . '_error_rest', __( 'The API key provided does not have read permissions.', $this->prefix_id ), array( 'status' => 403 ) );

                return $this->pagfort_get_status_order_ws();
        }

        /**
         * Check if order_id exists
         * @return boolean
         */
        private function pagfort_get_status_order_ws() {
                $token = $this->pagfort_get_token_ws();
                if ( !$token )
                        return false;
                $client = new nusoap_client( $this->URL_DOC );
                $client->soap_defencoding = 'UTF-8';
                $client->decode_utf8 = false;
                $client->setCredentials( $this->pagfort_get_api_settings_access(), $token, "basic" );

                $result = $client->call( 'obtTitulo', array( 'data' =>
                    array( 'n_do_documento' => $this->ndoc ) ) );

                if ( in_array( $result[ 'cod_msg' ], array( 32 ) ) ) {
                        error_log( print_r( "GET_STATUS_ORDER_WS SUCESSO", true ) );
                        return $this->pagfort_check_order_rest_ws( $result[ 'titulos' ][ 0 ] );
                } else {
                        $this->gateway->pagfort_add_log( serialize( $result[ 'msg' ] ) );
                        wc_add_notice( $result[ 'msg' ], 'error' );
                        return false;
                }
        }

        /**
         * Get order from ws and check status
         * @param array $result
         */
        private function pagfort_check_order_rest_ws( $result ) {
                $order = wc_get_order( $this->order_id );
                $this->status->woo = null;

                if ( in_array( $result[ 'cod_status' ], array( 31 ) ) ) {
                        $this->status->woo = 'processing';
                        $this->status->order = __( 'BANK SLIP PAID', $this->prefix );
                        $this->status->cod = $result[ 'cod_status' ];
                        $this->status->ws = $result[ 'status' ];
                }

                return $this->status->woo ? $this->pagfort_update_status_order_ws( $order, $this->status ) : false;
        }

        /**
         * Update status order
         * @param type $order
         * @param type $status
         * @return \WP_Error|boolean
         */
        private function pagfort_update_status_order_ws( $order, $status ) {
                if ( $status->woo ) {
                        $update = $order->update_status( $status->woo, $status->order );
                        if ( $update ) {
                                $boleto_data = get_post_meta( $this->order_id, $this->prefix_id . '_data', true );
                                $boleto_data[ 'cod_status' ] = $this->status->cod;
                                $boleto_data[ 'status' ] = $this->status->order;
                                update_post_meta( $this->order_id, $this->prefix_id . '_data', $boleto_data );
                                return true;
                        } else {
                                return new WP_Error( 'update_order_rest_error', __( 'Unable to change order status.', $this->prefix_id ), array( 'status' => 200 ) );
                        }
                }
        }

        /**
         * Get token from API
         * @return boolean
         */
        public function pagfort_get_token_ws() {
                $settings = $this->gateway->settings;
                $aut = new nusoap_client( $this->URL_AUTH );
                $aut->soap_defencoding = 'UTF-8';
                $aut->decode_utf8 = false;
                $pwd = "yes" == $settings[ 'sandbox_enable' ] ? $settings[ 'sandbox_password' ] : $settings[ 'production_password' ];
                if ( $pwd ) {
                        $aut->setCredentials( $this->pagfort_get_api_settings_access(), $pwd, "basic" );
                        $result = $aut->call( 'obtToken' );
                        if ( $result[ 'cod_msg' ] == 59 )
                                return $result[ 'token' ];
                        else
                                $this->gateway->pagfort_add_log( serialize( $result ) );
                }
                $this->gateway->pagfort_add_log( serialize( array( 'Informações incorretas de acesso a API' ) ) );
                return false;
        }

        /**
         * Get order from API
         * @param type $order_id
         * @return boolean
         */
        public function pagfort_get_order_ws( $order_id ) {
                $client = new nusoap_client( $this->URL_DOC );
                $client->soap_defencoding = 'UTF-8';
                $client->decode_utf8 = false;
                $client->setCredentials( $this->pagfort_get_api_settings_access(), $this->pagfort_get_token_ws(), "basic" );

                $result = $client->call( 'obtTitulo', array( 'data' => array( 'n_do_documento' => $order_id ) ) );

                if ( $result[ 'cod_msg' ] == 32 )
                        return $result[ 'titulos' ][ 0 ];
                else
                        $this->gateway->pagfort_add_log( serialize( $result ) );

                return false;
        }

        /**
         * Edit status bank slip in API
         * @param array $data
         * @return boolean
         */
        public function pagfort_edit_status( $data ) {
                $client = new nusoap_client( $this->URL_DOC );
                $client->soap_defencoding = 'UTF-8';
                $client->decode_utf8 = false;
                $client->setCredentials( $this->pagfort_get_api_settings_access(), $this->pagfort_get_token_ws(), "basic" );

                $result = $client->call( 'canDocumentoCpfCnpj', array( 'documento' => array(
                        'cpf_cnpj'       => $data[ 'cpf_cnpj' ],
                        'n_do_documento' => $data[ 'n_do_documento' ],
                        'cancelar_todos' => 'F'
                    ) ) );

                if ( $result[ 'cod_msg' ] == 39 )
                        return true;
                else
                        $this->gateway->pagfort_add_log( serialize( $result ) );

                return false;
        }

        /**
         * Prepare data from client checkout to send to API
         * @param type $order_id
         * @return type
         */
        public function pagfort_payload_ws( $order_id ) {
                $order = wc_get_order( $order_id );
                $ndoc = $order_id . '#' . current_time( 'timestamp' );
                $payload_data = array( 'data' => array(
                        'permitir_cobranca'     => 1,
                        'data_emissao'          => current_time( 'Y-m-d' ),
                        'vencimento'            => date_i18n( "Y-m-d", strtotime( "+" . absint( $this->gateway->settings[ 'default_due_date' ] ) . " days", strtotime( date( "Y-m-d" ) ) ) ),
                        'n_do_documento'        => $ndoc,
                        'fatura'                => $ndoc,
                        'valor_numerico'        => $order->get_total(),
                        'valor_desconto'        => '',
                        'cpf_cnpj'              => $order->get_meta( '_billing_persontype' ) == '1' ? $order->get_meta( '_billing_cpf' ) : $order->get_meta( '_billing_cnpj' ),
                        'gerar_boleto'          => 1,
                        'cidade_documento'      => $order->get_billing_city(),
                        'sigla_documento'       => $order->get_billing_state(),
                        'sigla'                 => $order->get_billing_state(),
                        'cidade'                => $order->get_billing_city(),
                        'tipo_pessoa'           => $order->get_meta( '_billing_persontype' ) == '1' ? 'PF' : 'PJ',
                        'rg_inscricao_estadual' => $order->get_meta( '_billing_persontype' ) == '1' ? $order->get_meta( '_billing_rg' ) : $order->get_meta( '_billing_ie' ),
                        'nome_razao_social'     => $order->get_meta( '_billing_persontype' ) == '1' ? $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() : $order->get_billing_company(),
                        'estado_civil'          => 'Desconhecido',
                        'logradouro'            => $order->get_billing_address_1(),
                        'numero'                => $order->get_meta( '_billing_number' ),
                        'complemento'           => $order->get_billing_address_2(),
                        'bairro'                => $order->get_meta( '_billing_persontype' ),
                        'cep'                   => $order->get_billing_postcode(),
                        'e_mail'                => $order->get_billing_email(),
                        'telefone'              => $order->get_billing_phone(),
                        'telefone_2'            => $order->get_meta( '_billing_cellphone' ),
                        'responsavel_legal'     => $order->get_meta( '_billing_persontype' ) == '2' ? $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() : '',
                    ) );

                return $this->pagfort_send_payload_ws( $payload_data );
        }

        /**
         * Prepare data from admin order to send API
         * @param type $post
         * @return type
         */
        public function pagfort_payload_admin_ws( $post ) {
                $order = wc_get_order( $post[ 'post_ID' ] );
                $partial = $order->get_total() - ($order->get_total() * ($this->gateway->settings[ 'discount_payment' ] / 100));
                $total = number_format( $partial, 2, '.', '' );
                $ndoc = $post[ 'post_ID' ] . '#' . current_time( 'timestamp' );
                $payload_data = array( 'data' => array(
                        'permitir_cobranca'     => 1,
                        'data_emissao'          => current_time( 'Y-m-d' ),
                        'vencimento'            => date_i18n( "Y-m-d", strtotime( "+" . absint( $this->gateway->settings[ 'default_due_date' ] ) . " days", strtotime( date( "Y-m-d" ) ) ) ),
                        'n_do_documento'        => $ndoc,
                        'fatura'                => $ndoc,
                        'valor_numerico'        => $total,
                        'valor_desconto'        => '',
                        'cpf_cnpj'              => sanitize_text_field( $post[ '_billing_persontype' ] ) == '1' ? $post[ '_billing_cpf' ] : $post[ '_billing_cnpj' ],
                        'gerar_boleto'          => 1,
                        'cidade_documento'      => sanitize_text_field( $post[ '_billing_city' ] ),
                        'sigla_documento'       => sanitize_text_field( $post[ '_billing_state' ] ),
                        'sigla'                 => sanitize_text_field( $post[ '_billing_state' ] ),
                        'cidade'                => sanitize_text_field( $post[ '_billing_city' ] ),
                        'tipo_pessoa'           => $post[ '_billing_persontype' ] == '1' ? 'PF' : 'PJ',
                        'rg_inscricao_estadual' => $post[ '_billing_persontype' ] == '1' ? sanitize_text_field( $post[ '_billing_rg' ] ) : sanitize_text_field( $post[ '_billing_ie' ] ),
                        'nome_razao_social'     => $post[ '_billing_persontype' ] == '1' ? sanitize_text_field( $post[ '_billing_first_name' ] ) . ' ' . sanitize_text_field( $post[ '_billing_last_name' ] ) : sanitize_text_field( $post[ '_billing_company' ] ),
                        'estado_civil'          => 'Desconhecido',
                        'logradouro'            => sanitize_text_field( $post[ '_billing_address_1' ] ),
                        'numero'                => sanitize_text_field( $post[ '_billing_number' ] ),
                        'complemento'           => sanitize_text_field( $post[ '_billing_address_2' ] ),
                        'bairro'                => sanitize_text_field( $post[ '_billing_neighborhood' ] ),
                        'cep'                   => sanitize_text_field( $post[ '_billing_postcode' ] ),
                        'e_mail'                => sanitize_email( $post[ '_billing_email' ] ),
                        'telefone'              => sanitize_text_field( $post[ '_billing_phone' ] ),
                        'telefone_2'            => sanitize_text_field( $post[ '_billing_cellphone' ] ),
                        'responsavel_legal'     => $post[ '_billing_persontype' ] == '2' ? sanitize_text_field( $post[ '_billing_first_name' ] ) . ' ' . sanitize_text_field( $post[ '_billing_last_name' ] ) : '',
                    ) );

                $send_payload = $this->pagfort_send_payload_ws( $payload_data );
                if ( $send_payload[ 'result' ] && $this->gateway->settings[ 'discount_payment' ] ) {

                        $discount = ($order->get_total() * ($this->gateway->settings[ 'discount_payment' ] / 100));

                        $item_fee = new WC_Order_Item_Fee();
                        $item_fee->set_name( __( "Desconto no boleto", $this->prefix ) );
                        $item_fee->set_total( -number_format( $discount, 2, '.', '' ) );

                        $order->add_item( $item_fee );
                        $order->set_total( $total );
                        $order->save();
                }
                return $send_payload;
        }

        /**
         * Send data to API
         * @param type $payload_data
         * @return boolean
         */
        private function pagfort_send_payload_ws( $payload_data ) {
                $token = $this->pagfort_get_token_ws();
                if ( !$token )
                        return false;

                $client = new nusoap_client( $this->URL_DEV );
                $client->soap_defencoding = 'UTF-8';
                $client->decode_utf8 = false;

                $client->setCredentials( $this->pagfort_get_api_settings_access(), $token, "basic" );
                $result = $client->call( 'insDevedorDocumento', $payload_data );

                if ( in_array( $result[ 'cod_msg' ], array( 107, 112, 113, 114 ) ) ) {
                        $result[ 'result' ] = true;
                        return array_merge( $result, $payload_data[ 'data' ] );
                } else {
                        $result[ 'result' ] = false;
                        $this->gateway->pagfort_add_log( serialize( $result ) );
                        return $result;
                }
        }

        /**
         * Return the user data for the given consumer_key.
         * @param string $ck Consumer key.
         * @return array $user
         */
        protected function pagfort_get_user_data_by_consumer_key( $ck ) {
                global $wpdb;
                $consumer_key = wc_api_hash( sanitize_text_field( $ck ) );

                $user = $wpdb->get_row(
                        $wpdb->prepare(
                                "
			SELECT permissions, consumer_secret
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = %s ", $consumer_key
                        )
                );

                return $user;
        }

        /**
         * Init endpoint rest api
         */
        private function pagfort_rest_endpoint_init() {
                add_action( 'rest_api_init', function () {
                        register_rest_route( 'pagfort-boleto/v1', '/orderws', array(
                            'methods'  => 'POST',
                            'callback' => array( $this, 'pagfort_validate_auth_handler' )
                        ) );
                } );

                add_action( 'rest_api_init', array( $this, 'pagfort_customize_rest_cors' ) );
        }

        /**
         * Customize permitted url from request
         */
        public function pagfort_customize_rest_cors() {
                remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

                add_filter( 'rest_pre_serve_request', function ($value) {
                        header( 'Access-Control-Allow-Origin:' . $this->pagfort_get_allowed_urls() );
                        header( 'Access-Control-Allow-Methods: POST' );
                        header( 'Access-Control-Allow-Credentials: true' );
                        header( 'Access-Control-Expose-Headers: Link', false );
                        return $value;
                } );
        }

        /**
         * Get permitted url
         * @param boolean $origin
         * @return string url 
         */
        protected function pagfort_get_allowed_urls() {
                return 'https://www.protestonacional.com.br';
        }

        /**
         * Return login from sandbox or production
         * @return array $settings
         */
        protected function pagfort_get_api_settings_access() {
                $settings = $this->gateway->settings;
                if ( isset( $settings[ 'sandbox_login' ] ) || isset( $settings[ 'production_login' ] ) ) {
                        return "yes" == $settings[ 'sandbox_enable' ] ? $settings[ 'sandbox_login' ] : $settings[ 'production_login' ];
                } else {
                        $this->gateway->pagfort_add_log( serialize( array( 'Informações incorretas de acesso a API' ) ) );
                }
        }

        /**
         * Set private variable return url
         */
        protected function pagfort_set_api_endpoint() {
                $settings = $this->gateway->settings;
                "yes" == $settings[ 'sandbox_enable' ] ?
                                $this->pagfort_get_api_endpoint_sandbox() :
                                $this->pagfort_get_api_endpoint_production();
        }

        /**
         * Return variable api url
         * @param boolean $sndbx
         * @return string
         */
        protected function pagfort_api_urls( $sndbx ) {
                return $sndbx ? 'https://www.protestonacional.com.br/hws-2.0/index.php' : 'https://www.protestonacional.com.br/pnws_2_0/index.php';
        }

        /**
         * Get sandbox url
         */
        protected function pagfort_get_api_endpoint_sandbox() {
                $base = $this->pagfort_api_urls( true );
                $this->URL_AUTH = "$base/aut/aut_ws";
                $this->URL_DEV = "$base/devedor";
                $this->URL_DOC = "$base/documento";
        }

        /**
         * Get production url 
         */
        protected function pagfort_get_api_endpoint_production() {
                $base = $this->pagfort_api_urls( false );
                $this->URL_AUTH = "$base/aut/aut_ws";
                $this->URL_DEV = "$base/devedor";
                $this->URL_DOC = "$base/documento";
        }

}

new Pagfort_Ws_Api();
