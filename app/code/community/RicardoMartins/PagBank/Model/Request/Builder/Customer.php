<?php

class RicardoMartins_PagBank_Model_Request_Builder_Customer
{
    /**
     * Customer information
     */
    const CUSTOMER = 'customer';

    /**
     * @var Mage_Sales_Model_Order $order
     */
    protected $order;

    /**
     * @param $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * @return array
     */
    public function build()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $document = $helper->getDocumentValue($this->order);

        $telephone = $this->order->getBillingAddress()->getTelephone();
        if (!$telephone) {
            $telephone = $this->order->getBillingAddress()->getFax();
        }

        $telephone = preg_replace('/[^0-9]/','', $telephone);

        /** @var RicardoMartins_PagBank_Model_Request_Object_Customer_Phone $phones */
        $phones = Mage::getModel('ricardomartins_pagbank/request_object_customer_phone');
        $phones->setCountry(RicardoMartins_PagBank_Api_Connect_PhoneInterface::DEFAULT_COUNTRY_CODE);
        $phones->setArea((int) substr($telephone, 0, 2));
        $phones->setNumber((int) substr($telephone, 2));
        $phones->setType($this->getPhoneType($telephone, $document));

        /** @var RicardoMartins_PagBank_Model_Request_Object_Customer $customer */
        $customer = Mage::getModel('ricardomartins_pagbank/request_object_customer');
        
        // Get customer name - try from customer first, then from billing address
        // This handles cases where customer is not logged in (guest checkout)
        $firstname = $this->order->getCustomerFirstname();
        $lastname = $this->order->getCustomerLastname();
        
        // If customer name is empty, get from billing address (common in Maho guest checkout)
        $billingAddress = $this->order->getBillingAddress();
        if ((empty($firstname) || empty($lastname)) && $billingAddress) {
            if (empty($firstname)) {
                $firstname = $billingAddress->getFirstname();
            }
            if (empty($lastname)) {
                $lastname = $billingAddress->getLastname();
            }
        }
        
        // Ensure we have at least something
        $firstname = $firstname ?: '';
        $lastname = $lastname ?: '';
        
        $customerName = trim($firstname . ' ' . $lastname);
        if (empty($customerName)) {
            // Last resort: try to get from quote billing address
            $quote = $this->order->getQuote();
            if ($quote && $quote->getBillingAddress()) {
                $quoteBilling = $quote->getBillingAddress();
                $firstname = $quoteBilling->getFirstname() ?: $firstname;
                $lastname = $quoteBilling->getLastname() ?: $lastname;
                $customerName = trim($firstname . ' ' . $lastname);
            }
        }
        
        $customer->setName(
            $helper->sanitizeCustomerName($customerName)
        );
        $customer->setTaxId($document);
        $customer->setPhones([$phones->getData()]);

        $email = $this->order->getCustomerEmail();
        $storeId = $this->order->getStoreId();
        if ($helper->sendBuyerEmailHash($storeId)) {
            $email = $helper->getHashEmail($email);
        }
        $customer->setEmail($email);

        return [
            self::CUSTOMER => $customer->getData()
        ];
    }

    /**
     * @param string $telephone
     * @param string|null $taxvat
     * @return string
     */
    private function getPhoneType($telephone, $taxvat = null)
    {
        if (!$taxvat) {
            return RicardoMartins_PagBank_Api_Connect_PhoneInterface::TYPE_MOBILE;
        }

        $countTaxvatCharacters = strlen($taxvat);
        if ($countTaxvatCharacters === 14) {
            return RicardoMartins_PagBank_Api_Connect_PhoneInterface::TYPE_BUSINESS;
        }

        $countPhoneCharacters = strlen($telephone);
        if ($countPhoneCharacters === 8) {
            return RicardoMartins_PagBank_Api_Connect_PhoneInterface::TYPE_HOME;
        }

        return RicardoMartins_PagBank_Api_Connect_PhoneInterface::TYPE_MOBILE;
    }
}
