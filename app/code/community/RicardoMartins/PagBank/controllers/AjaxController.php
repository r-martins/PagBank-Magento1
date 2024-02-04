<?php

class RicardoMartins_PagBank_AjaxController extends Mage_Core_Controller_Front_Action {

    /**
     * @return Mage_Core_Controller_Response_Http|Zend_Controller_Response_Abstract
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getInstallmentsAction()
    {
        $installmentsPlans = [];
        $params = $this->getRequest()->getParams();
        $creditCardBin = $params['cc_bin'];
        $storeId = Mage::app()->getStore()->getId();

        $installmentsBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_installments', $creditCardBin);
        $installments = $installmentsBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getInterestEndpoint($storeId);

        try {
            $response = $api->placeGetRequest($endpoint, $installments);
            $creditCard = reset($response['payment_methods']['credit_card']);
            $installmentsPlans = $creditCard['installment_plans'];
        } catch (Exception $e) {}

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($installmentsPlans));
    }
}
