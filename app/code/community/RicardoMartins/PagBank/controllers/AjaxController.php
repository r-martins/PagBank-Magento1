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

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $isSandbox = $helper->isSandbox($storeId);
        if ($isSandbox) {
            $creditCardBin = $helper->getSandboxCcBin();
        }

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Installments $installmentsBuilder */
        $installmentsBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_installments', $creditCardBin);
        $installments = $installmentsBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        $endpoint = $helper->getInterestEndpoint($storeId);

        try {
            $response = $api->placeGetRequest($endpoint, $installments);
            $creditCard = reset($response['payment_methods']['credit_card']);
            $installmentsPlans = $creditCard['installment_plans'];
        } catch (Exception $e) {
            $installmentsPlans = [
                [
                    'installments' => 1,
                    'interest_free' => true,
                    'installment_value' => $installments['value'],
                    'amount' => [
                        'value' => $installments['value'],
                        'currency' => 'BRL'
                    ]
                ]
            ];
        }

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

        $hasCustomer = (bool)$quote->getCustomer()->getId();

        $name = $hasCustomer ? $quote->getCustomer()->getName() : $quote->getBillingAddress()->getFirstname() . ' ' . $quote->getBillingAddress()->getLastname();
        $email = $hasCustomer ? $quote->getCustomer()->getEmail() : $quote->getBillingAddress()->getEmail();
        $phone = preg_replace('/[^0-9]/', '', $quote->getBillingAddress()->getTelephone());

        $street = $quote->getBillingAddress()->getStreet(1);
        $number = $quote->getBillingAddress()->getStreet(2);
        $complement = $quote->getBillingAddress()->getStreet(3);
        $neighborhood = $quote->getBillingAddress()->getStreet(4);
        $regionCode = $quote->getBillingAddress()->getRegionCode();
        $city = $quote->getBillingAddress()->getCity();

        $oscData = Mage::getSingleton('checkout/session')->getData('onestepcheckout_form_values');
        if ($oscData) {
            $name = $oscData['billing']['firstname'] . ' ' . $oscData['billing']['lastname'];
            $email = $oscData['billing']['email'];
            $phone = preg_replace('/[^0-9]/', '', $oscData['billing']['telephone']);
            $street = $oscData['billing']['street'][0];
            $number = $oscData['billing']['street'][2];
            $complement = $oscData['billing']['street'][3];
            $neighborhood = $oscData['billing']['street'][4];
            $regionCode = $oscData['billing']['region_id'];
            $city = $oscData['billing']['city'];
        }

        $result = [
            'totalAmount' => $total,
            'customerName' => $helper->escapeHtml($name),
            'email' => $helper->escapeHtml($email),
            'phone' => $helper->escapeHtml($phone),
            'street' => $helper->escapeHtml($street),
            'number' => $helper->escapeHtml($number),
            'complement' => $helper->escapeHtml($complement),
            'neighborhood' => $helper->escapeHtml($neighborhood),
            'regionCode'=> $helper->escapeHtml($regionCode),
            'country'=> 'BRA',
            'city'=> $helper->escapeHtml($city),
            'postalCode'=> $helper->escapeHtml($postcode)
        ];

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
