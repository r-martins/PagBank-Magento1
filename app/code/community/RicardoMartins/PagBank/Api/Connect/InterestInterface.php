<?php
/**
 * Interface InterestInterface - Buyer data.
 * @see https://developer.pagbank.com.br/reference/criar-transacao-com-repasse-de-taxa
 */
interface RicardoMartins_PagBank_Api_Connect_InterestInterface
{
    /**
     * Information about interest
     * Receives an integer with the number of installments.
     */
    const INSTALLMENTS = 'installments';
    
    /**
     * Installment's total value in cents
     * Receives an int value.
     */
    const TOTAL = 'total';
}
