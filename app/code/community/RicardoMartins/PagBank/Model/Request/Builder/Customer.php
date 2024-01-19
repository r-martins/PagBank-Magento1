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
        $telephone = preg_replace('/[^0-9]/','', $telephone);

        $phones = new RicardoMartins_PagBank_Model_Request_Object_Customer_Phone();
        $phones->setCountry(RicardoMartins_PagBank_Api_Connect_PhoneInterface::DEFAULT_COUNTRY_CODE);
        $phones->setArea((int) substr($telephone, 0, 2));
        $phones->setNumber((int) substr($telephone, 2));
        $phones->setType($this->getPhoneType($telephone, $document));

        $customer = new RicardoMartins_PagBank_Model_Request_Object_Customer();
        $customer->setName($this->order->getCustomerFirstname() . ' ' . $this->order->getCustomerLastname());
        $customer->setTaxId($document);
        $customer->setEmail($this->order->getCustomerEmail());
        $customer->setPhones([$phones->getData()]);

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
