<?php
if ( !defined( 'ABSPATH' ) ) {
        exit;
}

$plugin_slug = 'woocommerce-extra-checkout-fields-for-brazil';

if ( current_user_can( 'install_plugins' ) ) {
        $url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
} else {
        $url = 'http://wordpress.org/plugins/' . $plugin_slug;
}
?>
<div class="notice <?php echo 'notice-error' ?> is-dismissible">
        <p>
                <strong><?php echo __( 'Pagfort Bank Slip for WooCommerce', 'pagfort-boleto' ); ?> </strong>
        </p>
        <p>
            <?php printf( __( 'This plugin depends on the last version of %s to work correctly!', 'pagfort-boleto' ), '<a href="' . esc_url( $url ) . '" target="_blank">' . __( 'Brazilian Market on WooCommerce', 'pagfort-boleto' ) . '</a>' ); ?>
        </p>
</div>
