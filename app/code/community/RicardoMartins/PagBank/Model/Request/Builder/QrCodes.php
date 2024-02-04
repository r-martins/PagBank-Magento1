<?php

class RicardoMartins_PagBank_Model_Request_Builder_QrCodes
{
    /**
     * Object containing the QR Codes linked to an order.
     * Receives expiration date and order amount.
     */
    const QR_CODES = 'qr_codes';

    /**
     * @var $order
     */
    protected $order;

    /**
     * @param $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * @return array
     */
    public function build()
    {
        $result = [];
        $storeId = $this->order->getStoreId();

        /** @var RicardoMartins_PagBank_Model_Request_Object_Amount $amount */
        $amount = Mage::getModel('ricardomartins_pagbank/request_object_amount');
        $amount->setValue($this->order->getGrandTotalAmount());

        $expirationDate = Mage::getStoreConfig('payment/ricardomartins_pagbank_pix/expiration_time', $storeId);

        /** @var RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Qrcode $qrCodes */
        $qrCodes = Mage::getModel('ricardomartins_pagbank/request_object_paymentmethod_qrcode');
        $qrCodes->setAmount($amount->getData());
        $qrCodes->setExpirationDate($expirationDate);

        $result[self::QR_CODES][] = $qrCodes->getData();

        return $result;
    }
}
