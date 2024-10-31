<?php

if ( !defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Fields to Pagfort gateway plugin.
 */
class Pagfort_Boleto_Fields {

        public function __construct() {
                $this->prefix = Pagfort_Boleto_Gateway::PAGFORT_PREFIX;
                $this->prefix_id = Pagfort_Boleto_Gateway::PAGFORT_PREFIX_ID;
        }

        /**
         * Config fields Pagfort WooCommerce Admin
         * @return array
         */
        public function get_admin_config_fields() {
                $admin_fields = array(
                    'enabled'               => array(
                        'title'   => __( 'Enable / Disable', $this->prefix ),
                        'type'    => 'checkbox',
                        'label'   => __( 'Bank slip payment', $this->prefix ),
                        'default' => 'yes'
                    ),
                    'title'                 => array(
                        'title'       => __( 'Title', $this->prefix ),
                        'type'        => 'text',
                        'description' => __( 'This field controls the section title the user sees during checkout', $this->prefix ),
                        'desc_tip'    => false,
                        'default'     => __( 'Bank Slip', $this->prefix )
                    ),
                    'description'           => array(
                        'title'       => __( 'Description', $this->prefix ),
                        'type'        => 'textarea',
                        'description' => __( 'This field controls the section text the user sees during checkout', $this->prefix ),
                        'desc_tip'    => false,
                        'default'     => __( 'Pay with bank slip', $this->prefix )
                    ),
                    'default_due_date'      => array(
                        'title'       => __( 'Default due date', $this->prefix ),
                        'type'        => 'text',
                        'description' => __( 'Enter the default number of days to expire after the bank slip is generated', $this->prefix ),
                        'desc_tip'    => false,
                        'default'     => '5',
                    ),
                    'discount_payment'      => array(
                        'title'       => __( 'Discount payment %', $this->prefix ),
                        'type'        => 'text',
                        'description' => __( 'Máx 99.99%. Use dot to decimals (Ex.: 2.5, 10.7)', $this->prefix ),
                        'desc_tip'    => false,
                        'default'     => '0',
                    ),
                    'allow_billing'         => array(
                        'title'       => __( 'Enable / Disable', $this->prefix ),
                        'type'        => 'checkbox',
                        'description' => sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.protestonacional.com.br/cliente/index.php/informacao/servico#como-funciona-cobranca' ?: '#', __( 'Learn more about billing methods', $this->prefix ) ),
                        'label'       => __( 'Allow billing', $this->prefix ),
                        'default'     => 'no'
                    ),
//                    'barcode_style'         => array(
//                        'title'   => __( 'Enable / Disable', $this->prefix ),
//                        'type'    => 'checkbox',
//                        'label'   => __( 'Responsive Barcode', $this->prefix ),
//                        'default' => 'yes'
//                    ),
                    'access_credentials'    => array(
                        'title' => __( 'Sandbox enviroment API', $this->prefix ),
                        'type'  => 'title'
                    ),
                    'sandbox_enable'        => array(
                        'title'   => __( 'Enable / Disable', $this->prefix ),
                        'type'    => 'checkbox',
                        'label'   => __( 'Sandbox enviroment', $this->prefix ),
                        'default' => 'yes'
                    ),
                    'row_separator1'        => array(
                        'type'  => 'title',
                        'class' => 'row_separator',
                    ),
                    'sandbox_credential'    => array(
                        'title' => __( 'Access credentials for the sandbox environment', $this->prefix ),
                        'type'  => 'title'
                    ),
                    'sandbox_login'         => array(
                        'title'       => __( 'Login', $this->prefix ),
                        'type'        => 'text',
                        'description' => __( 'Your platform access login sandbox', $this->prefix ),
                        'desc_tip'    => true,
                        'default'     => 'teste@protestonacional.com.br'
                    ),
                    'sandbox_password'      => array(
                        'title'       => __( 'Password', $this->prefix ),
                        'type'        => 'password',
                        'description' => __( 'Your API access password', $this->prefix ),
                        'desc_tip'    => true,
                        'default'     => 'testeteste'
                    ),
                    'row_separator2'        => array(
                        'type'  => 'title',
                        'class' => 'row_separator',
                    ),
                    'production_credential' => array(
                        'title' => __( 'Access credentials for the production environment', $this->prefix ),
                        'type'  => 'title'
                    ),
                    'production_login'      => array(
                        'title'       => __( 'Login', $this->prefix ),
                        'type'        => 'text',
                        'description' => __( 'Your platform access login production', $this->prefix ),
                        'desc_tip'    => true,
                        'default'     => __( 'Enter your login', $this->prefix )
                    ),
                    'production_password'   => array(
                        'title'       => __( 'Password', $this->prefix ),
                        'type'        => 'password',
                        'description' => __( 'Your API access password', $this->prefix ),
                        'desc_tip'    => true
                    ),
                    'row_separator3'        => array(
                        'type'  => 'title',
                        'class' => 'row_separator',
                    ),
                    'connection_message'    => array(
                        'title' => '',
                        'type'  => 'title'
                    ),
                    'btn_test_connection'   => array(
                        'title'       => __( 'Connection test', $this->prefix ),
                        'type'        => 'button',
                        'description' => __( 'Test the server connection to ensure that communication is working correctly.', $this->prefix ),
                        'desc_tip'    => false,
                    ),
                    'debug_pagfort'         => array(
                        'title'       => __( 'Log debug', $this->prefix ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Enable all warning and error logs', $this->prefix ),
                        'default'     => 'no',
                        'description' => sprintf( __( 'File that stores some plugin logs, such as API server calls and others. % s Enable to monitor any server communication issues.', $this->prefix ), $this->get_log_view() ),
                    ),
                );

                return $admin_fields;
        }

        /**
         * Validate post from admin order, client order
         * @param array $post
         * @param boolean $admin
         * @return array 
         */
        public static function pagfort_validate_fields( $post, $admin ) {
                error_log( print_r( "PAGFORT_VALIDATE_FIELDS", true ) );

                $udrln = $admin ? '_' : '';
                $msg = array();

                if ( empty( $post[ $udrln . 'billing_persontype' ] ) ) {
                        $msg[] = __( 'Select the type of person!', $post[ 'prefix' ] );
                }

                if ( $post[ $udrln . 'billing_persontype' ] == '2' ) {
                        if ( empty( $post[ $udrln . 'billing_company' ] ) ) {
                                $msg[] = __( 'Company name is required for legal entity!', $post[ 'prefix' ] );
                        }
                        if ( empty( $post[ $udrln . 'billing_cnpj' ] ) ) {
                                $msg[] = __( 'CNPJ number is required!', $post[ 'prefix' ] );
                        }
                        if ( !Pagfort_Boleto_Fields::pagfort_valida_cpfcnpj(( $post[ $udrln . 'billing_cnpj' ] ) )) {
                                $msg[] = __( 'CNPJ number is invalid!', $post[ 'prefix' ] );
                        }
                        if ( empty( $post[ $udrln . 'billing_ie' ] ) ) {
                                $msg[] = __( 'Company State Registration is mandatory for legal entity!', $post[ 'prefix' ] );
                        }
                }

                if ( $post[ $udrln . 'billing_persontype' ] == '1' ) {
                        if ( empty( $post[ $udrln . 'billing_cpf' ] ) ) {
                                $msg[] = __( 'Social Security Number is required!', $post[ 'prefix' ] );
                        }
                        if ( !Pagfort_Boleto_Fields::pagfort_valida_cpfcnpj(( $post[ $udrln . 'billing_cpf' ] ) )) {
                                $msg[] = __( 'Social Security Number number is invalid!', $post[ 'prefix' ] );
                        }
                        if ( empty( $post[ $udrln . 'billing_rg' ] ) ) {
                                $msg[] = __( 'ID number is required!', $post[ 'prefix' ] );
                        }
                }

                if ( empty( $post[ $udrln . 'billing_number' ] ) ) {
                        $msg[] = __( "Address number is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_neighborhood' ] ) ) {
                        $msg[] = __( "Neighborhood is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_first_name' ] ) ) {
                        $msg[] = __( "First name is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_last_name' ] ) ) {
                        $msg[] = __( "Last name is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_address_1' ] ) ) {
                        $msg[] = __( "Address is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_city' ] ) ) {
                        $msg[] = __( "City is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_postcode' ] ) ) {
                        $msg[] = __( "Postcode is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_country' ] ) ) {
                        $msg[] = __( "Country is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_state' ] ) ) {
                        $msg[] = __( "State is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_email' ] ) ) {
                        $msg[] = __( "E-mail address is required!", $post[ 'prefix' ] );
                }

                if ( empty( $post[ $udrln . 'billing_phone' ] ) ) {
                        $msg[] = __( "Phone number is required!", $post[ 'prefix' ] );
                }

                return $msg;
        }

        /**
         * Link for debug log in WooCommerce
         * @return string
         */
        protected function get_log_view() {
                if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
                        return '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->prefix_id ) . '-' . sanitize_file_name( wp_hash( $this->prefix_id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', $this->prefix ) . '</a>';
                }
                return '<code>woocommerce/logs/' . esc_attr( $this->prefix_id ) . '-' . sanitize_file_name( wp_hash( $this->prefix_id ) ) . '.txt</code>';
        }

        /**
         * Handler for validate CPF or CNPJ
         * @param string $cpfcnpj
         * @return boolean
         */
        protected static function pagfort_valida_cpfcnpj( $cpfcnpj ) {
                $var = preg_replace( "/[^0-9]/", "", $cpfcnpj );
                if ( strlen( $var ) == 11 ) {
                        if ( Pagfort_Boleto_Fields::pagfort_valida_cpf( $var ) )
                                return true;
                } else {
                        if ( Pagfort_Boleto_Fields::pagfort_valida_cnpj( $var ) )
                                return true;
                }
                return false;
        }

        /**
         * Validate CPF
         * @param string $cpf
         * @return boolean
         */
        protected static function pagfort_valida_cpf( $cpf ) {
                $d1 = 0;
                $d2 = 0;
                $cpf = preg_replace( "/[^0-9]/", "", $cpf );
                $ignore_list = array(
                    '00000000000',
                    '01234567890',
                    '11111111111',
                    '22222222222',
                    '33333333333',
                    '44444444444',
                    '55555555555',
                    '66666666666',
                    '77777777777',
                    '88888888888',
                    '99999999999'
                );
                if ( strlen( $cpf ) != 11 || in_array( $cpf, $ignore_list ) ) {
                        return false;
                } else {
                        for ( $i = 0; $i < 9; $i++ ) {
                                $d1 += $cpf[ $i ] * (10 - $i);
                        }
                        $r1 = $d1 % 11;
                        $d1 = ($r1 > 1) ? (11 - $r1) : 0;
                        for ( $i = 0; $i < 9; $i++ ) {
                                $d2 += $cpf[ $i ] * (11 - $i);
                        }
                        $r2 = ($d2 + ($d1 * 2)) % 11;
                        $d2 = ($r2 > 1) ? (11 - $r2) : 0;
                        return (substr( $cpf, -2 ) == $d1 . $d2) ? true : false;
                }
        }

        /**
         * Validate CNPJ
         * @param string $str
         * @return boolean
         */
        protected static function pagfort_valida_cnpj( $str ) {
                if ( !preg_match( '|^(\d{2,3})\.?(\d{3})\.?(\d{3})\/?(\d{4})\-?(\d{2})$|', $str, $matches ) )
                        return false;

                array_shift( $matches );
                $str = implode( '', $matches );
                
                if ( strlen( $str ) > 14 )
                        $str = substr( $str, 1 );

                $sum1 = 0;
                $sum2 = 0;
                $sum3 = 0;
                $calc1 = 5;
                $calc2 = 6;

                for ( $i = 0; $i <= 12; $i++ ) {
                        $calc1 = $calc1 < 2 ? 9 : $calc1;
                        $calc2 = $calc2 < 2 ? 9 : $calc2;

                        if ( $i <= 11 )
                                $sum1 += $str[ $i ] * $calc1;

                        $sum2 += $str[ $i ] * $calc2;
                        $sum3 += $str[ $i ];
                        $calc1--;
                        $calc2--;
                }

                $sum1 %= 11;
                $sum2 %= 11;

                return ($sum3 && $str[ 12 ] == ($sum1 < 2 ? 0 : 11 - $sum1) && $str[ 13 ] == ($sum2 < 2 ? 0 : 11 - $sum2)) ? true : false;
        }

}

new Pagfort_Boleto_Fields();
