<div style="margin-bottom: 40px;">
        <h2>
            <?php echo esc_html__( 'Payment', esc_attr( $prefix ) ) ?>
        </h2>
        <table class="td" cellspacing="0" cellpadding="6" border="1" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
                <tbody>
                        <tr>
                                <th class="td" scope="row" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo esc_html__( 'Bank slip status', esc_attr( $prefix ) ) ?></th>
                                <td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo $status; ?></td>
                        </tr>
                        <tr>
                                <th class="td" scope="row" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo esc_html__( 'Date of issue', esc_attr( $prefix ) ) ?></th>
                                <td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo implode( '/', array_reverse( explode( '-', $data_emissao ) ) ); ?></td>
                        </tr>
                        <tr>
                                <th class="td" scope="row" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo esc_html__( 'Due date', esc_attr( $prefix ) ) ?></th>
                                <td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo implode( '/', array_reverse( explode( '-', $vencimento ) ) ); ?></td>
                        </tr>
                        <?php if ( !$sandbox_enable ) : ?>
                                <tr>
                                        <th class="td" scope="row" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo esc_html__( 'Impression link', esc_attr( $prefix ) ) ?></th>
                                        <td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;">
                                            <?php echo sprintf( '<a class="button" href="%s" target="_blank"">%s</a>', esc_url( $url_pagamento ) ?: '#', __( 'Print ticket &rarr;', $prefix ) ); ?>
                                </tr>
                                <tr>
                                        <th class="td" scope="row" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo esc_html__( 'Digitable line', esc_attr( $prefix ) ) ?></th>
                                        <td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo $linha_digitavel; ?></td>
                                </tr>
                                <?php if ( !$barcode_style ): ?>
                                        <tr>
                                                <th class="td" scope="row" colspan="2" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;">
                                                    <?php echo $barcode; ?>
                                                </th>
                                        </tr>
                                <?php else: ?>
                                        <tr>
                                                <th class="td" scope="row" colspan="2" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;">
                                                        <span id="barcode_img"></span> 
                                                </th>
                                        </tr>
                                <?php endif; ?>
                        <?php endif; ?>
                </tbody>
        </table>
</div>