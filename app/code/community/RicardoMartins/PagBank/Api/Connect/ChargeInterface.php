<?php

/**
 * Interface ChargeInterface - Charge data.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 * @see https://dev.pagbank.uol.com.br/reference/objeto-charge
 */
interface RicardoMartins_PagBank_Api_Connect_ChargeInterface
{
    /**
     * Unique identifier assigned to the charge.
     * Receives a string.
     * Characters limit: 64 characters.
     */
    const REFERENCE_ID = 'reference_id';

    /**
     * Date and time the charge was created.
     * Receives a string in the format YYYY-MM-DDThh:mm:ss.sTZD.
     */
    const CREATED_AT = 'created_at';

    /**
     * Datetime format (YYYY-MM-DDThh:mm:ss.sTZD).
     */
    const DATETIME_FORMAT = 'Y-m-d_TH:i:s.vP';

    /**
     * Contains information on the amount to be charged.
     * Receives an object.
     * @see RicardoMartins_PagBank_Api_Connect_AmountInterface
     */
    const AMOUNT = 'amount';

    /**
     * Contains information about the payment method.
     * Receives an object.
     * @see RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    const PAYMENT_METHOD = 'payment_method';
}
