<?php

/**
 * Interface HolderInterface
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_HolderInterface
{
    /**
     * Customer name
     * Receives a string with 1 to 30 characters.
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
     * Receive a string with 10 - 255 characters.
     */
    const EMAIL = 'email';

    /**
     * Customer address.
     * @see RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    const ADDRESS = 'address';
}
