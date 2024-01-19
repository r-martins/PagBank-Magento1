<?php

/**
 * Interface AddressInterface - Address data.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_AddressInterface
{
    /**
     * Address street.
     * Receives a string.
     * Characters limit: 160.
     */
    const STREET = 'street';

    /**
     * Address number.
     * Receives a string.
     * Characters limit: 20.
     */
    const NUMBER = 'number';

    /**
     * Address complement.
     * Receives a string.
     * Characters limit: 40 characters.
     */
    const COMPLEMENT = 'complement';

    /**
     * Address locality (neighborhood).
     * Receives a string.
     * Characters limit: 60 characters.
     */
    const LOCALITY = 'locality';

    /**
     * Address city.
     * Receives a string.
     * Characters limit: 90 characters.
     */
    const CITY = 'city';

    /**
     * Address state name.
     * Receives a string.
     * Characters limit: 50 characters.
     */
    const REGION = 'region';

    /**
     * Address state code.
     * Receives a string with two characters (ISO 3166-2 format).
     * Characters limit: 2 characters.
     */
    const REGION_CODE = 'region_code';

    /**
     * Address country code.
     * Receives a string with three characters (ISO 3166-1 alpha-3 format).
     * Characters limit: 3 characters.
     */
    const COUNTRY = 'country';

    /**
     * Brazil country code (ISO 3166-1 alpha-3 format).
     */
    const COUNTRY_CODE_BRAZIL = 'BRA';

    /**
     * Address postal code.
     * Receives a string with 8 characters.
     * Characters limit: 8 characters.
     */
    const POSTAL_CODE = 'postal_code';
}
