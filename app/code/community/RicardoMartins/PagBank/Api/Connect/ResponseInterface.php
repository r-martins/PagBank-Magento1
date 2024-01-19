<?php

interface RicardoMartins_PagBank_Api_Connect_ResponseInterface
{
    /**
     * PagBank order identifier.
     * Format: ORDE_XXXXXXXXXXXX
     */
    const PAGBANK_ORDER_ID = 'id';

    /**
     * Charges array
     */
    const CHARGES = 'charges';

    /**
     * Charge status
     */
    const CHARGE_STATUS = 'status';

    /**
     * Charge id
     * Format: CHA_XXXXXXXXXXXX
     */
    const CHARGE_ID = 'id';

    /**
     * Charge authorized status.
     */
    const STATUS_AUTHORIZED = 'AUTHORIZED';

    /**
     * Charge paid status.
     */
    const STATUS_PAID = 'PAID';

    /**
     * Charge waiting status.
     * PagBang is analyzing the transaction.
     */
    const STATUS_IN_ANALYSIS = 'IN_ANALYSIS';

    /**
     * Charge declined status.
     * PagBang or the issuer declined the transaction.
     */
    const STATUS_DECLINED = 'DECLINED';

    /**
     * Charge canceled status.
     * PagBang or the issuer canceled the transaction.
     */
    const STATUS_CANCELED = 'CANCELED';

    /**
     * Charge denied status.
     * PagBang or the issuer denied the transaction.
     */
    const STATUS_DENIED = 'DENIED';

    /**
     * Order reference id
     */
    const REFERENCE_ID = 'reference_id';

    /**
     * Payment response
     */
    const PAYMENT_RESPONSE = 'payment_response';

    /**
     * Payment response code
     */
    const PAYMENT_RESPONSE_CODE = 'code';

    /**
     * Payment response message
     */
    const PAYMENT_RESPONSE_MESSAGE = 'message';

    /**
     * Payment response reference
     */
    const PAYMENT_RESPONSE_REFERENCE = 'reference';

    /**
     * Payment method data
     */
    const PAYMENT_METHOD = 'payment_method';

    /**
     * Payment method type
     */
    const PAYMENT_METHOD_TYPE = 'type';

    /**
     * Billet data
     */
    const BILLET = 'boleto';

    /**
     * Billet ID
     */
    const BILLET_ID = 'id';

    /**
     * Billet barcode
     */
    const BILLET_BARCODE = 'barcode';

    /**
     * Billet formatted barcode
     */
    const BILLET_FORMATED_BARCODE = 'formatted_barcode';

    /**
     * Billet due date
     */
    const BILLET_DUE_DATE = 'due_date';

    /**
     * Credit card data
     */


    /**
     * QrCodes Data (Pix)
     */
    const QR_CODES = 'qr_codes';

    /**
     * QrCode id
     * Format: QRCO_XXXXXXXXXXXX
     */
    const QRCODE_ID = 'id';

    /**
     * Payment error messages
     * Array of error messages
     */
    const ERROR_MESSAGES = 'error_messages';

    /**
     * Payment error message code
     */
    const ERROR_MESSAGE_CODE = 'code';

    /**
     * Payment error message description
     */
    const ERROR_MESSAGE_DESCRIPTION = 'description';

    /**
     * Payment error parameter name
     */
    const ERROR_MESSAGE_PARAMETER_NAME = 'parameter_name';
}
