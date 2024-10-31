<?php
/**
 * Admin options screen.
 */
if (!defined('ABSPATH')) {
    exit;
}

$reviews_url = 'https://wordpress.org/support/view/plugin-reviews/pagfort-boleto?filter=5#postform';
?>

<h3><?php echo $this->method_title; ?></h3>

<?php
if ('yes' == $this->get_option('enabled')) {
    if (!$this->using_supported_currency() && !class_exists('woocommerce_wpml')) {
        include 'html-notice-currency-not-supported.php';
    }
}
?>

<?php echo wpautop($this->method_description); ?>

<?php if (apply_filters('woocommerce_boleto_help_message', true)) : ?>
    <div class="updated woocommerce-message">
      <p><?php printf(__('Diga-nos o que está achando do plugin %s dando seu %s ou sua nota %s no site do %s. Obrigado!', 'pagfort-boleto'), '<strong>' . __('SPCPN Boleto', 'pagfort-boleto') . '</strong>', '<a href="http://google.com">' . __('feedback', 'pagfort-boleto') . '</a>', '<a href="' . $reviews_url . '" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>', '<a href="' . $reviews_url . '" target="_blank">' . __('WordPress.org', 'pagfort-boleto') . '</a>'); ?></p>
    </div>
<?php endif; ?>

<table class="form-table">
  <?php $this->generate_settings_html(); ?>
</table>


