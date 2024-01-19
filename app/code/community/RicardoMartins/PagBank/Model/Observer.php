<?php

class RicardoMartins_PagBank_Model_Observer
{
    public function generatePublicKey(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getPublicKeyEndpoint();
        $body = [
            RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::TYPE => RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::TYPE_CARD
        ];

        try {
            $api = new RicardoMartins_PagBank_Model_Api_Connect_Client();
            $response = $api->placePostRequest($endpoint, $body);
            $publicKey = $response[RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::PUBLIC_KEY];
            Mage::getConfig()->saveConfig('payment/ricardomartins_pagbank/public_key', $publicKey);
        } catch (\Exception $e) {
            throw new \Exception(__('Public Key Error: %1', $e->getMessage()));
        }
    }
}