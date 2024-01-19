<?php

/**
 * Interface InstallmentsInterface - Installments for payment methods.
 * @see https://dev.pagbank.uol.com.br/reference/consultar-juros-transacao
 * @see https://dev.pagbank.uol.com.br/reference/criar-transacao-com-repasse-de-juros
 */
interface RicardoMartins_PagBank_Api_Connect_InstallmentsInterface
{
    /**
     * Payment methods from which the integrator would like to recover fees for transfer.
     * Receives an array of strings.
     */
    const PAYMENT_METHODS = 'payment_methods';

    /**
     * Payment method type.
     * Receives a string.
     */
    const PAYMENT_METHOD_TYPE_CC = 'CREDIT_CARD';

    /**
     * Transaction amount in cents.
     * Receives an integer.
     */

    const VALUE = 'value';

    /**
     * Maximum number of installments allowed.
     * Receives an integer.
     */
    const MAX_INSTALLMENTS = 'max_installments';

    /**
     * Maximum number of installments without interest for the customer.
     * The seller will assume the fees for the installments.
     * Receives an integer.
     */
    const MAX_INSTALLMENTS_NO_INTEREST = 'max_installments_no_interest';

    /**
     * Credit card bin.
     * The first 6 digits of the credit card.
     * Receives an integer.
     */
    const CREDIT_CARD_BIN = 'credit_card_bin';
}
