</section>
<section class="woocommerce-spcpn-boleto">
        <h2 class="woocommerce-column__title">
            <?php echo esc_html__( 'Bank slip details', esc_attr( $prefix ) ) ?>
        </h2>
        <p><?php echo $sandbox_enable ? esc_html__( "( * Sandbox Enviroment )", esc_attr( $prefix ) ) : "" ?></p>
        <table class="woocommerce-table shop_table boleto_info">
                <tbody>
                        <tr>
                                <th><?php echo esc_html__( 'Bank slip status', esc_attr( $prefix ) ) ?></th>
                                <td><strong><?php echo $status; ?></strong></td>
                        </tr>
                        <tr>
                                <th><?php echo esc_html__( 'Date of issue', esc_attr( $prefix ) ) ?></th>
                                <td><?php echo implode( '/', array_reverse( explode( '-', $data_emissao ) ) ); ?></td>
                        </tr>
                        <tr>
                                <th><?php echo esc_html__( 'Due date', esc_attr( $prefix ) ) ?></th>
                                <td><?php echo implode( '/', array_reverse( explode( '-', $vencimento ) ) ); ?></td>
                        </tr>
                        <?php if ( ($cod_status == 1) && (!$sandbox_enable) ): ?>
                                <tr>
                                        <th><?php echo esc_html__( 'Impression link', esc_attr( $prefix ) ) ?></th>
                                        <td>
                                            <?php echo sprintf( '<a class="button" href="%s" target="_blank"">%s</a>', $url_pagamento ?: '#', __( 'Print ticket &rarr;', $prefix ) ); ?>
                                </tr>
                                <tr>
                                        <th><?php echo esc_html__( 'Digitable line', esc_attr( $prefix ) ) ?></th>
                                        <td><?php echo $linha_digitavel; ?></td>
                                </tr>
                                <?php if ( !$barcode_style ): ?>
                                        <tr>
                                                <th colspan="2">
                                                    <?php echo $barcode; ?>
                                                </th>
                                        </tr>
                                <?php else: ?>
                                        <tr>
                                                <th colspan="2">
                                                        <span id="barcode_img"></span> 
                                                </th>
                                        </tr>
                                <?php endif; ?>
                        <?php endif; ?>
                </tbody>
        </table>
