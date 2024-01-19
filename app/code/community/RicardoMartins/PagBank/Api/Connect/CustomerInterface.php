<?php

/**
 * Interface ConnectInterface - Customer data.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_CustomerInterface
{
    /**
     * Customer name.
     * Receive a string.
     * Character limit: 30 characters.
     */
    const NAME = 'name';

    /**
     * Customer document. CPF or CNPJ is required.
     * CPF has 11 digits and CNPJ has 14 digits.
     * Receive a string.
     */
    const TAX_ID = 'tax_id';

    /**
     * Customer email
     * Receive a string.
     * Character limit: 10 to 255 characters.
     */
    const EMAIL = 'email';

    /**
     * Customer phones.
     * Receive an array of phones.
     * @see RicardoMartins_PagBank_Api_Connect_PhoneInterface
     */
    const PHONES = 'phones';
}
