<?php

/**
 * Interface PaymentMethodInterface - Payment method data.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 * @see https://dev.pagbank.uol.com.br/reference/objeto-charge
 */
interface RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
{
    /**
     * Payment method type.
     * Receives a string value.
     * ENUM: Only BOLETO, CREDIT_CARD or DEBIT_CARD are accepted.
     */
    const TYPE = 'type';

    /**
     * Payment method type Billet.
     */
    const TYPE_BILLET = 'BOLETO';

    /**
     * Payment method type Credit Card.
     */
    const TYPE_CREDIT_CARD = 'CREDIT_CARD';

    /**
     * Installments number.
     * Required for credit card payments.
     * Receives an integer value.
     * Character limit: 2 digits.
     */
    const INSTALLMENTS = 'installments';

    /**
     * Capture flag.
     * Required for credit card payments.
     * Receives a boolean value.
     * If true, the payment will be captured automatically.
     * If false, the payment will be pre-authorized and must be captured later.
     */
    const CAPTURE = 'capture';

    /**
     * Soft descriptor. Optional. Only for credit card payments.
     * Receives a string value.
     * The soft descriptor is the name of the company that will appear in the customer's credit card statement.
     * Character limit: 17 characters.
     */
    const SOFT_DESCRIPTOR = 'soft_descriptor';

    /**
     * Card data. Required for credit card payments.
     * @see RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface
     */
    const TYPE_CREDIT_CARD_OBJECT = 'card';

    /**
     * Billet data. Required for billet payments.
     * @see RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface
     */
    const TYPE_BILLET_OBJECT = 'boleto';

    /**
     * Authentication method. Optional. Only for credit card payments.
     * @see RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface
     */
    const AUTHENTICATION_METHOD = 'authentication_method';
}
