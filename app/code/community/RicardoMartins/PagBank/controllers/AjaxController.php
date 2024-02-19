<?php

class RicardoMartins_PagBank_AjaxController extends Mage_Core_Controller_Front_Action
{

    /**
     * @return Mage_Core_Controller_Response_Http|Zend_Controller_Response_Abstract
     * @throws Mage_Core_Model_Store_Exception|Mage_Core_Exception
     */
    public function getInstallmentsAction()
    {
        $installmentsPlans = [];
        $params = $this->getRequest()->getParams();
        $creditCardBin = $params['cc_bin'];
        $storeId = Mage::app()->getStore()->getId();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Installments $installmentsBuilder */
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

    /**
     * @return Mage_Core_Controller_Response_Http|Zend_Controller_Response_Abstract
     */
    public function getQuoteDataAction()
    {
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper('checkout/cart')->getQuote();
        $quote->collectTotals();
        $total = $quote->getGrandTotal();

        $postcode = preg_replace('/[^0-9]/', '', $quote->getBillingAddress()->getPostcode());
        $phone = preg_replace('/[^0-9]/', '', $quote->getBillingAddress()->getTelephone());

        $result = [
            'totalAmount' => $total,
            'customerName' => $helper->escapeHtml($quote->getCustomer()->getName()),
            'email' => $helper->escapeHtml($quote->getCustomer()->getEmail()),
            'phone' => $helper->escapeHtml($phone),
            'street'=> $helper->escapeHtml($quote->getBillingAddress()->getStreet(1)),
            'number'=> $helper->escapeHtml($quote->getBillingAddress()->getStreet(2)),
            'complement'=> $helper->escapeHtml($quote->getBillingAddress()->getStreet(3)),
            'regionCode'=> $helper->escapeHtml($quote->getBillingAddress()->getRegionCode()),
            'country'=> 'BRA',
            'city'=> $helper->escapeHtml($quote->getBillingAddress()->getCity()),
            'postalCode'=> $helper->escapeHtml($postcode)
        ];

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
