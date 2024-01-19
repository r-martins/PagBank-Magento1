<?php

/**
 * Interface QrCodeInterface - QR Code object.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface
{
    /**
     * Expiration date of the QR Code. By default, the QR Code expires in 24 hours.
     * Receives a string in the format YYYY-MM-DDThh:mm:ss.sTZD.
     */
    const EXPIRATION_DATE = 'expiration_date';

    /**
     * Datetime format (YYYY-MM-DDThh:mm:ss.sTZD).
     */
    const DATETIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    /**
     * Contains information on the amount to be charged.
     * Receives an object.
     * @see RicardoMartins_PagBank_Api_Connect_AmountInterface
     */
    const AMOUNT = 'amount';
}
