<?php

class RicardoMartins_PagBank_Model_Request_Builder_Shipping
{
    /**
     * Shipping order information
     */
    const SHIPPING = 'shipping';

    /**
     * Shipping address information
     */
    const SHIPPING_ADDRESS = 'address';

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
        $result = [];

        if ($this->order->getIsVirtual()) {
            return $result;
        }

        $shippingAddress = $this->order->getShippingAddress();

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $regionCode = strlen($shippingAddress->getRegionCode()) == 2 ? $shippingAddress->getRegionCode() : $helper->getRegionCode($shippingAddress->getRegionCode());
        $regionCode = strtoupper($regionCode); // Ensure region code is in uppercase
        /* Determining how many lines we have in the address */
        $addressLinesNotEmpty = array_filter($shippingAddress->getStreet());
        
        /** @var RicardoMartins_PagBank_Model_Request_Object_Customer_Address $address */
        $address = Mage::getModel('ricardomartins_pagbank/request_object_customer_address');
        $street = $shippingAddress->getStreet(1);
        $number = $shippingAddress->getStreet(2);
        $locality = end($addressLinesNotEmpty);
        $city = $shippingAddress->getCity();
        $address->setStreet($helper->sanitizeString($street));
        $address->setNumber($helper->sanitizeString($number));
        if (count($addressLinesNotEmpty) > 3) {
            $complement = $shippingAddress->getStreet(3);
            $address->setComplement($helper->sanitizeString($complement));
        }
        $address->setLocality($helper->sanitizeString($locality));
        $address->setCity($helper->sanitizeString($city));
        $address->setRegion($shippingAddress->getRegion());
        $address->setRegionCode($regionCode);
        $address->setPostalCode($shippingAddress->getPostcode());
        $address->setCountry();

        if ($shippingAddress->getStreet(3)) {
            $address->setComplement($shippingAddress->getStreet(3));
        }

        $result[self::SHIPPING][self::SHIPPING_ADDRESS] = $address->getData();

        return $result;
    }
}
