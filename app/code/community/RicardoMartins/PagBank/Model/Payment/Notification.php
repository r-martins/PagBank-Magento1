<?php

class RicardoMartins_PagBank_Model_Payment_Notification
{
    /**
     * Process the notification
     *
     * @param $charge
     * @return bool
     */
    public function processNotification($charge)
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $incrementId = $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID];
        try {
            $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
            if (!$order->getId()) {
                throw new Exception("Order {$incrementId} not found");
            }

            $status = $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS] ?: '';
            $methodInstance = $order->getPayment()->getMethodInstance();
            $methodInstance->handleNotification($order, $status);
            return true;
        } catch (Exception $e) {
            $helper->writeLog("Order {$incrementId} not found");
        }

        return false;
    }

    /**
     * Check the notification
     *
     * @param $pagbankOrderId
     * @return false
     * @throws Mage_Core_Exception
     */
    public function checkNotification($pagbankOrderId)
    {
        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getPaymentInfoEndpoint();
        $endpoint = str_replace('{pagbankOrderId}', $pagbankOrderId, $endpoint);

        return $api->placeGetRequest($endpoint, [], false);
    }
}