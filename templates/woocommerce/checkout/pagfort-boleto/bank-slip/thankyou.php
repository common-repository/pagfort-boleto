<?php ?>

<section id="<?= esc_attr( $prefix_id ); ?>-thankyou">
    <?php if ( $sandbox_enable ): ?>
                <h2><?= esc_html__( 'Sandbox Enviroment', esc_attr( $prefix ) ); ?></h2>
                <p><?= esc_html__( 'The bank slip generation test has been successfully completed!', esc_attr( $prefix ) ); ?></p>
        <?php else: ?>

                <h2><?= esc_html__( 'Your ticket was successfully generated!', esc_attr( $prefix ) ); ?></h2>

                <div class="<?= esc_attr( $prefix ); ?>-container">
                        <p>Após o pagamento e a compensação bancária do boleto, seu pedido será liberado.</p>

                        <p>A data de vencimento do seu boleto é para o dia <strong><?= esc_html( $vencimento ); ?></strong>.</p>

                        <p><?= esc_html__( 'Below we have some options to facilitate your payment.', esc_attr( $prefix ) ); ?></p>

                </div>

                <div class="<?= esc_attr( $prefix ); ?>-container container-strong">
                        <p><?= esc_html__( 'Print your bank slip', esc_attr( $prefix ) ); ?></p>
                        <p><?= sprintf( '<a class="button " href="%s" target="_blank" style="">%s</a>', $url_pagamento ?: '#', __( 'Print bank slip', $prefix ) ); ?></p>
                </div>

                <?php if(!$barcode_style):?>
                <div class="<?= esc_attr( $prefix ); ?>-container container-barcode container-strong">
                        <p><?= esc_html__( 'Use your banking app reader', esc_attr( $prefix ) ) ?></p>
                        <span id=""><?php echo $barcode ?></span>
                </div>
                <?php else:?>
                
                <div class="<?= esc_attr( $prefix ); ?>-container container-barcode container-strong">
                        <p><?= esc_html__( 'Use your banking app reader', esc_attr( $prefix ) ) ?></p>
                        <span id="barcode_img"></span>
                </div>
                <?php endif;?>

                <div class="<?= esc_attr( $prefix ); ?>-container container-strong">
                        <p><?= esc_html__( 'Copy numbers from digitable line', esc_attr( $prefix ) ); ?></p>
                        <p>
                                <span class="<?php echo esc_attr( $prefix ) ?>-barcode"><?= esc_html( $linha_digitavel ); ?></span>
                                <button 
                                    type="button" 
                                    class="button button-copy-barcode" 
                                    aria-label="<?= esc_html( $linha_digitavel ); ?>">
                                        <?= esc_html__( 'Copy' ) ?>
                                </button>
                        </p>
                </div>

                <div class="<?= esc_attr( $prefix ); ?>-container container-strong">
                        <div class="woocommerce-info">
                                <p><?= sprintf( __( '%sAttention!%s Your bank slip will not be sent by email, and will not be delivered by post.', $prefix ), '<strong>', '</strong>' ); ?></p>
                        </div>
                </div>
        <?php endif; ?>
</section>

<div class="pagfort-boleto-clear"></div>
