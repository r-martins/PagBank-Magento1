<?php

/**
 * Interface ItemInterface - Item interface.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_ItemInterface
{
    /**
     * The reference id of the item.
     * Receive a string.
     */
    const REFERENCE_ID = 'reference_id';

    /**
     * The name of the item.
     * Receive a string.
     * Character limit: 64 characters.
     */
    const NAME = 'name';

    /**
     * The quantity of the item.
     * Receive an integer (int32).
     * Character limit: 5 characters.
     */
    const QUANTITY = 'quantity';

    /**
     * The unit amount of the item (in cents).
     * Receive an integer (int32).
     * Character limit: 9 characters.
     */
    const UNIT_AMOUNT = 'unit_amount';
}
