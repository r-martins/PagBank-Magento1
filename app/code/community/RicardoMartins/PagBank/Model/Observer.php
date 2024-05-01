<?php

class RicardoMartins_PagBank_Model_Observer
{
    /**
     * Generate public key on save connect key
     *
     * @param Varien_Event_Observer $observer
     * @return void
     * @throws Exception
     */
    public function generatePublicKey(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getPublicKeyEndpoint();
        $body = [
            RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::TYPE => RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::TYPE_CARD
        ];

        try {
            /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
            $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');
            $response = $api->placePostRequest($endpoint, $body);
            $publicKey = $response[RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::PUBLIC_KEY];
        } catch (\Exception $e) {
            $helper->writeLog(sprintf('Error generating public key: %s', $e->getMessage()));
            throw new \Exception(__('Error generating public key: %s', $e->getMessage()));
        }

        try {
            Mage::getConfig()->saveConfig('payment/ricardomartins_pagbank/public_key', $publicKey);
        } catch (\Exception $e) {
            $helper->writeLog(sprintf('Error saving public key: %s', $e->getMessage()));
            throw new \Exception(__('Error saving public key: %s', $e->getMessage()));
        }
    }

    /**
     * Assign additional data to specific information payment info block
     *
     * @param $observer
     * @return $this
     */
    public function paymentInfoBlockPrepareSpecificInformation($observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $transport = $observer->getEvent()->getTransport();
        $helper = Mage::helper('ricardomartins_pagbank');

        $additionalData = unserialize($payment->getAdditionalData());

        $info = [
            RicardoMartins_PagBank_Model_Method_Cc::CC_BRAND,
            RicardoMartins_PagBank_Model_Method_Cc::CC_LAST_4,
            RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_MONTH,
            RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_YEAR,
            RicardoMartins_PagBank_Model_Method_Cc::CC_OWNER,
            RicardoMartins_PagBank_Model_Method_Cc::CC_INSTALLMENTS
        ];

        if (!$observer->getEvent()->getBlock()->getIsSecureMode()) {
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::CHARGE_ID;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::CHARGE_LINK;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::AUTHORIZATION_CODE;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::NSU;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::CC_PAGBANK_SESSION;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::CC_3DS_ID;
        }

        foreach ($info as $key) {
            if ($value = $payment->getAdditionalInformation($key)) {
                $transport->setData($helper->getInfoLabels($key), $value);
            } elseif (isset($additionalData[$key])) {
                $transport->setData($helper->getInfoLabels($key), $additionalData[$key]);
            }
        }
        return $this;
    }

    /**
     * @param $observer
     * @return $this
     * @throws Throwable
     */
    public function salesOrderPaymentPlaceEnd($observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        $method = $payment->getMethodInstance();

        if ($method instanceof RicardoMartins_PagBank_Model_Method_Cc) {
            try {
                $additionalData = unserialize($payment->getAdditionalData());
                $statusPagbank = $additionalData['status_pagbank'];
                if ($statusPagbank) {
                    $method->handleNotification($order, $statusPagbank);
                }
            } catch (\Exception $e) {
                $helper = Mage::helper('ricardomartins_pagbank');
                $helper->writeLog(sprintf('Error handling PagBank return: %s', $e->getMessage()));
            }
        }

        if ($method instanceof RicardoMartins_PagBank_Model_Method_Pix || $method instanceof RicardoMartins_PagBank_Model_Method_Billet) {
            try {
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
            } catch (\Exception $e) {
                $helper = Mage::helper('ricardomartins_pagbank');
                $helper->writeLog(sprintf('Error handling PagBank return: %s', $e->getMessage()));
            }
        }

        return $this;
    }
}