<?php

class RicardoMartins_PagBank_Model_Request_Builder_Installments
{
    /**
     * @var $creditCardBin
     */
    protected $creditCardBin;

    /**
     * @param $creditCardBin
     */
    public function __construct(
        $creditCardBin
    ) {
        $this->creditCardBin = $creditCardBin;
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function build()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $storeId = $quote->getStoreId();
        $grandTotalAmount = (float) $quote->getGrandTotal();

        $installments = Mage::getModel('ricardomartins_pagbank/request_object_installments');
        $installments->setPaymentMethods(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::PAYMENT_METHOD_TYPE_CC);
        $installments->setValue($grandTotalAmount);
        $installments->setCreditCardBin($this->creditCardBin);

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        if ($helper->isEnabledInstallmentsLimit($storeId)) {
            $maxIntallments = $helper->getInstallmentsLimit($storeId);
            $installments->setMaxInstallments($maxIntallments);
        }

        $maxIntallmentsNoInterest = $helper->getMaxInstallmentsNoInterest($grandTotalAmount, $storeId);
        if (!is_null($maxIntallmentsNoInterest)) {
            $installments->setMaxInstallmentsNoInterest($maxIntallmentsNoInterest);
        }

        return $installments->getData();
    }
}
