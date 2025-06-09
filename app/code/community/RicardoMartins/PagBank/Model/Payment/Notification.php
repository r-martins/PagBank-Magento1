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

            $payment = $order->getPayment();
            $method = $payment->getMethod();
            if ($method !== RicardoMartins_PagBank_Model_Method_Pix::METHOD_CODE) {
                $info = unserialize($payment->getAdditionalData());
                if(!isset($charge['id']) || !isset($info['charge_id'])){
                    throw new Exception("Charge id not found in order or charge");
                }
                if ($info['charge_id'] !== $charge['id']) {
                    throw new Exception("Mismatch between notification charge ID and order charge ID for order ID {$order->getId()}");
                }
            }
            $status = $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS] ?: '';
            $methodInstance = $order->getPayment()->getMethodInstance();
            $methodInstance->handleNotification($order, $status, $charge);
            return true;
        } catch (Exception $e) {
            $helper->writeLog("Failed to process notification: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Check the notification
     *
     * @param $pagbankOrderId
     * @return false|mixed
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