<?php

class RicardoMartins_PagBank_Model_Request_Builder_Charges_Pix
{
    /**
     * Object containing the QR Codes linked to an order.
     * Receives expiration date and order amount.
     */
    const QR_CODES = 'qr_codes';

    /**
     * @var Mage_Sales_Model_Order $order
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
        $expirationDate = Mage::getStoreConfig('payment/ricardomartins_pagbank_pix/expiration_time', $storeId) ?: 60;

        $amount = new RicardoMartins_PagBank_Model_Request_Object_Amount();
        $amount->setValue($this->order->getBaseGrandTotal());

        $qrCodes = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod_QrCode();
        $qrCodes->setAmount($amount->getData());
        $qrCodes->setExpirationDate($expirationDate);

        $result[self::QR_CODES][] = $qrCodes->getData();

        return $result;
    }
}
