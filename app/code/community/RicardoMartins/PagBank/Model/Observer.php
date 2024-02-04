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
            $api = new RicardoMartins_PagBank_Model_Api_Connect_Client();
            $response = $api->placePostRequest($endpoint, $body);
            $publicKey = $response[RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::PUBLIC_KEY];
            Mage::getConfig()->saveConfig('payment/ricardomartins_pagbank/public_key', $publicKey);
        } catch (\Exception $e) {
            throw new \Exception(__('Public Key Error: %1', $e->getMessage()));
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
}