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

        /** @var RicardoMartins_PagBank_Model_Request_Object_Customer_Address $address */
        $address = Mage::getModel('ricardomartins_pagbank/request_object_customer_address');
        $address->setStreet($shippingAddress->getStreet(1));
        $address->setNumber($shippingAddress->getStreet(2));
        $address->setLocality($shippingAddress->getStreet(4));
        $address->setCity($shippingAddress->getCity());
        $address->setRegion($shippingAddress->getRegion());
        $address->setRegionCode($shippingAddress->getRegionCode());
        $address->setPostalCode($shippingAddress->getPostcode());
        $address->setCountry();

        if ($shippingAddress->getStreet(3)) {
            $address->setComplement($shippingAddress->getStreet(3));
        }

        $result[self::SHIPPING][self::SHIPPING_ADDRESS] = $address->getData();

        return $result;
    }
}
