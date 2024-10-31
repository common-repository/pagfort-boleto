<?php
/**
 * Admin View: Notice - Currency not supported.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="error">
  <p><strong><?php _e('Pagfort Boleto Disabled', 'pagfort-boleto'); ?></strong>: <?php printf(__('Currency <code>%s</code> is not supported. Works only with Brazilian Real.', 'pagfort-boleto'), get_woocommerce_currency()); ?>
  </p>
</div>
