<?php

/**
 * Fired during plugin activation.
 */
class Plugin_Pagfort_Activator {

        /**
         * Check plugins dependencies
         *
         */
        public static function activate() {
                if ( !class_exists( 'woocommerce' ) ) {
                        Pagfort_Boleto::woocommerce_missing_notice();
                }

                if ( !class_exists( 'Extra_Checkout_Fields_For_Brazil' ) )
                        Pagfort_Boleto::brazilian_market_missing_notice();
                
                flush_rewrite_rules();
        }

}
