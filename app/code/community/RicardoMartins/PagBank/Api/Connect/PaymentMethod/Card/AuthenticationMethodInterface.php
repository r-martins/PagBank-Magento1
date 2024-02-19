<?php

/**
 * Interface AuthenticationMethodInterface - 3DS Authentication Method Information.
 * @see https://dev.pagbank.uol.com.br/docs/criar-pagar-pedido-com-3ds-validacao-pagbank
 */
interface RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface
{
    /**
     * The 3DS authentication process identifier.
     * Receives the response from the 3DS authentication process (authenticate3DS SDK request).
     */
    const AUTHENTICATION_METHOD_ID = 'id';

    /**
     * The 3DS authentication process type key.
     */
    const AUTHENTICATION_METHOD_TYPE = 'type';

    /**
     * The 3DS authentication process type value.
     * ENUM: THREEDS
     */
    const AUTHENTICATION_METHOD_TYPE_VALUE = 'THREEDS';
}
