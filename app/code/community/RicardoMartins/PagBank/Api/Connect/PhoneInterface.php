<?php

/**
 * Interface PhoneInterface - Phone data.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_PhoneInterface
{
    /**
     * Phone country code (DDI).
     * Receive an integer (int32).
     * Character limit: 3.
     */
    const COUNTRY = 'country';

    /**
     * Brazil country code (DDI).
     */
    const DEFAULT_COUNTRY_CODE = 55;

    /**
     * Phone area code (DDD).
     * Receive an integer (int32).
     * Character limit: 2.
     */
    const AREA = 'area';

    /**
     * Phone number.
     * Receive an integer (int32).
     * Character limit: 8 or 9.
     */
    const NUMBER = 'number';

    /**
     * Phone type.
     * Receive a string.
     * ENUM. Possible values: MOBILE, BUSINESS, HOME.
     */
    const TYPE = 'type';

    /**
     * Phone type mobile.
     */
    const TYPE_MOBILE = 'MOBILE';

    /**
     * Phone type business.
     */
    const TYPE_BUSINESS = 'BUSINESS';

    /**
     * Phone type home.
     */
    const TYPE_HOME = 'HOME';
}
