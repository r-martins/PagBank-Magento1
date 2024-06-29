<?php

/**
 * Interface PublicKeyInterface - Public key create.
 * @see https://dev.pagbank.uol.com.br/reference/criar-chave-publica
 */
interface RicardoMartins_PagBank_Api_Connect_PublicKeyInterface
{
    /**
     * Public key.
     * Used to encrypt the card data.
     */
    const PUBLIC_KEY = 'public_key';

    /**
     * Public key type.
     * Send the type of public key.
     */
    const TYPE = 'type';

    /**
     * Public key type card.
     */
    const TYPE_CARD = 'card';

    /**
     * Response error key
     */
    const RESPONSE_ERROR = 'error';

    /**
     * Public key config path.
     * Used to save the public key.
     */
    const PUBLIC_KEY_CONFIG_PATH = 'payment/ricardomartins_pagbank/public_key';
}
