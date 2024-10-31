<?php

/**
 * Plugin Name: Pagfort Boleto
 * Plugin URI: http://www.pagfort.com.br/
 * Description: Meio de pagamento brasileiro utilizando boleto bancário integrado com Woocommerce
 * Author: SPC Protesto Nacional
 * Author URI: https://protestonacional.com.br
 * Version: 1.0.1
 * License: GPLv2 or later
 * Text Domain: pagfort-boleto
 * Domain Path: /languages/
 * WC requires at least: 3.0.0
 * WC tested up to:      4.3.1
 *
 * Pagfort Boleto is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 or later of the License, or
 * any later version.
 *
 * Pagfort Boleto is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Pagfort Boleto. If not, see
 * <https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt>.
 */
if ( !defined( 'ABSPATH' ) ) {
        exit;
}


if ( !class_exists( 'Pagfort_Boleto' ) ):

        class Pagfort_Boleto {

                /**
                 * Plugin version.
                 *
                 * @var string
                 */
                const VERSION = '1.0.1';

                /**
                 * Instance of this class.
                 *
                 * @var object
                 */
                protected static $instance = null;

                /**
                 * Initialize the plugin actions.
                 */
                private function __construct() {

                        // Load plugin text domain
                        add_action( 'init', array( $this, 'load_translations' ) );

                        // Checks if WooCommerce is installed.
                        if ( class_exists( 'WC_Payment_Gateway' ) ) {

                                // Public includes.
                                $this->includes();

                                // Admin includes.
                                if ( is_admin() ) {
                                        $this->admin_includes();
                                }
                                //New gateway add
                                add_filter( 'woocommerce_payment_gateways', array( $this, 'add_pagfort_gateway' ) );

                                //Link plugin page
                                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
                                
                        } else {
                                add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
                        }
                }

                /**
                 * Return an instance of this class.
                 *
                 * @return object A single instance of this class.
                 */
                public static function get_instance() {
                        // If the single instance hasn't been set, set it now.
                        if ( null == self::$instance ) {
                                self::$instance = new self;
                        }
                        return self::$instance;
                }

                /**
                 * Get plugin path.
                 *
                 * @return string
                 */
                public static function get_plugin_path() {
                        return plugin_dir_path( __FILE__ );
                }

                /**
                 * Load the plugin text domain for translation.
                 */
                public function load_translations() {
                        $locale = apply_filters( 'plugin_locale', get_locale(), 'pagfort-boleto' );
                        load_textdomain( 'pagfort-boleto', trailingslashit( WP_LANG_DIR ) . 'pagfort-boleto/pagfort-boleto-' . $locale . '.mo' );
                        load_plugin_textdomain( 'pagfort-boleto', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
                }

                /**
                 * Includes.
                 */
                private function includes() {
                        include_once 'vendor/econea/nusoap/src/nusoap.php';
                        include_once 'vendor/picqer/php-barcode-generator/src/BarcodeGenerator.php';
                        include_once 'vendor/picqer/php-barcode-generator/src/BarcodeGeneratorSVG.php';
                        include_once 'includes/class-pagfort-boleto-gateway.php';
                        include_once 'includes/class-pagfort-fields.php';
                        include_once 'includes/class-pagfort-ws-api.php';
                        include_once 'includes/class-pagfort-boleto-client.php';
                }

                /**
                 * Includes admin.
                 */
                private function admin_includes() {
                        require_once 'includes/class-pagfort-boleto-admin.php';
                }

                /**
                 * Returns a bool that indicates if currency is amongst the supported ones.
                 *
                 * @return bool
                 */
                protected function using_supported_currency() {
                        return ( 'BRL' == get_woocommerce_currency() );
                }

                /**
                 * Returns a value indicating the the Gateway is available or not. It's called
                 * automatically by WooCommerce before allowing customers to use the gateway
                 * for payment.
                 *
                 * @return bool
                 */
                public function is_available() {
                        // Test if is valid for use.
                        $available = ( 'yes' == $this->get_option( 'enabled' ) ) && $this->using_supported_currency();
                        return $available;
                }

                public function admin_options() {
                        include 'includes/views/html-admin-page.php';
                }

                /**
                 * Add the gateway to WooCommerce.
                 *
                 * @param  array $methods WooCommerce payment methods.
                 *
                 * @return array          Payment methods with Boleto.
                 */
                public function add_pagfort_gateway( $methods ) {
                        array_push( $methods, 'Pagfort_Boleto_Gateway' );
                        return $methods;
                }

                /**
                 * Plugin activate method.
                 */
                public static function activate_pagfort_plugin() {
                        require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-pagfort-activator.php';
                        Plugin_Pagfort_Activator::activate();
                }

                /**
                 * Plugin deactivate method.
                 */
                public static function deactivate_pagfort_plugin() {
                        require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-pagfort-deactivator.php';
                        Plugin_Pagfort_Deactivator::deactivate();
                }

                /**
                 * Action links.
                 *
                 * @param  array $links
                 *
                 * @return array
                 */
                public function plugin_action_links( $links ) {
                        $plugin_links = array();
                        $settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=pagfort_boleto' );
                        $plugin_links[] = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'pagfort-boleto' ) . '</a>';

                        return array_merge( $plugin_links, $links );
                }

                /**
                 * WooCommerce fallback notice.
                 *
                 * @return string
                 */
                public static function woocommerce_missing_notice() {
                        include_once 'includes/views/html-notice-woocommerce-missing.php';
//                        wp_die( '', __( 'Plugin dependency check', 'pagfort-boleto' ), array( 'back_link' => true ) );
                }

                public static function brazilian_market_missing_notice() {
                        include_once 'includes/views/html-notice-extra-checkout-fields-brazil-detected.php';
                        wp_die( '', __( 'Plugin dependency check', 'pagfort-boleto' ), array( 'back_link' => true ) );
                }

        }

        /**
         * Plugin activation and deactivation methods.
         */
        register_activation_hook( __FILE__, array( 'Pagfort_Boleto', 'activate_pagfort_plugin' ) );
        register_deactivation_hook( __FILE__, array( 'Pagfort_Boleto', 'deactivate_pagfort_plugin' ) );

        /**
         * Initialize the plugin.
         */
        add_action( 'plugins_loaded', array( 'Pagfort_Boleto', 'get_instance' ) );
    
endif;
