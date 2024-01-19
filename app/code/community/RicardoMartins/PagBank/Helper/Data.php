<?php

class RicardoMartins_PagBank_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return string
     */
    public function getOrdersEndpoint()
    {
        if ($this->isSandbox()) {
            return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_ORDERS
                . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_ORDERS;
    }

    /**
     * @return string
     */
    public function getPublicKeyEndpoint()
    {
        if ($this->isSandbox()) {
            return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_PUBLIC_KEY
                . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_PUBLIC_KEY;
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        $connectKey = $this->getConnectKey();
        if (str_contains($connectKey, RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PREFIX)) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getConnectKey()
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank/connect_key');
    }

    /**
     * @return string[]
     */
    public function getHeaders()
    {
        return [
            'Authorization: Bearer ' . $this->getConnectKey(),
            'Accept: application/json',
            'Content-Type: application/json',
            'Api-Version: 4.0'
        ];
    }

    /**
     * @param $order
     * @return array|string|string[]|null
     */
    public function getDocumentValue($order)
    {
        $payment = $order->getPayment();
        $documentFrom = Mage::getStoreConfig('payment/ricardomartins_pagbank/document_from');

        switch ($documentFrom) {
            case 'taxvat':
                $document = $order->getCustomerTaxvat();
                break;
            case 'vat_id':
                $document = $order->getBillingAddress()->getVatId();
                break;
            default:
                $document = $payment->getAdditionalInformation('tax_id');
                break;
        }

        if (!$document) {
            $document = $payment->getAdditionalInformation('tax_id');
        }

        return preg_replace('/[^0-9]/','', $document);
    }
}