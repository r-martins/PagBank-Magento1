<?php

/**
 * Interface AmountInterface - Defines the order amount data.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_AmountInterface
{
    /**
     * Order amount value to be paid.
     * Receives an integer value in cents.
     * Characters limit: 9 digits.
     */
    const VALUE = 'value';

    /**
     * ISO currency code with 3 characters.
     * Receives a string value in capital letters.
     * Characters limit: 3 characters.
     */
    const CURRENCY = 'currency';

    /**
     * Brazilian Real currency code.
     */
    const CURRENCY_BRL = 'BRL';
}
