<?php

class RicardoMartins_PagBank_Model_Request_Builder_Charges_Billet
{
    /**
     * Represents all data available on a charge.
     * Receives an array of charges.
     */
    const CHARGES = 'charges';

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
        $storeId = $this->order->getStoreId();

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $billingAddress = $this->order->getBillingAddress();
        $regionCode = strlen($billingAddress->getRegionCode()) == 2 ? $billingAddress->getRegionCode() : $helper->getRegionCode($billingAddress->getRegionCode());

        /* Determining how many lines we have in the address */
        $addressLinesNotEmpty = array_filter($billingAddress->getStreet());
        
        /** @var RicardoMartins_PagBank_Model_Request_Object_Customer_Address $address */
        $address = Mage::getModel('ricardomartins_pagbank/request_object_customer_address');
        $address->setStreet($billingAddress->getStreet(1));
        $address->setNumber($billingAddress->getStreet(2));
        if (count($addressLinesNotEmpty) > 3) {
            $address->setComplement($billingAddress->getStreet(3));
        }
        $address->setLocality(end($addressLinesNotEmpty));
        $address->setCity($billingAddress->getCity());
        $address->setRegion($billingAddress->getRegion());
        $address->setRegionCode($regionCode);
        $address->setPostalCode($billingAddress->getPostcode());
        $address->setCountry();

        $document = $helper->getDocumentValue($this->order);

        /** @var RicardoMartins_PagBank_Model_Request_Object_Holder $holder */
        $holder = Mage::getModel('ricardomartins_pagbank/request_object_holder');
        $holder->setName($this->order->getCustomerFirstname() . ' ' . $this->order->getCustomerLastname());
        $holder->setTaxId($document);
        $holder->setEmail($this->order->getCustomerEmail());
        $holder->setAddress($address->getData());

        /** @var RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Billet_Instructionlines $instructionLines */
        $instructionLines = Mage::getModel('ricardomartins_pagbank/request_object_paymentmethod_billet_instructionlines');
        $instructionLines->setLineOne(Mage::getStoreConfig('payment/ricardomartins_pagbank_billet/instruction_line_one', $storeId));
        $instructionLines->setLineTwo(Mage::getStoreConfig('payment/ricardomartins_pagbank_billet/instruction_line_two', $storeId));

        /** @var RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Billet $billet */
        $billet = Mage::getModel('ricardomartins_pagbank/request_object_paymentmethod_billet');
        $expiration = Mage::getStoreConfig('payment/ricardomartins_pagbank_billet/expiration_time', $storeId) ?: 3;
        $billet->setDueDate(date('Y-m-d', strtotime('+' . $expiration . 'day')));
        $billet->setInstructionLines($instructionLines->getData());
        $billet->setHolder($holder->getData());

        /** @var RicardoMartins_PagBank_Model_Request_Object_Paymentmethod $paymentMethod */
        $paymentMethod = Mage::getModel('ricardomartins_pagbank/request_object_paymentmethod');
        $paymentMethod->setType(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_BILLET);
        $paymentMethod->setBillet($billet->getData());

        /** @var RicardoMartins_PagBank_Model_Request_Object_Amount $amount */
        $amount = Mage::getModel('ricardomartins_pagbank/request_object_amount');
        $amount->setValue($this->order->getBaseGrandTotal());

        $currencyCode = 'BRL';
        if ($this->order->getOrderCurrency()) {
            $currencyCode = $this->order->getOrderCurrency()->getCode();
        }

        if ($this->order->getQuoteCurrencyCode()) {
            $currencyCode = $this->order->getQuoteCurrencyCode();
        }

        $amount->setCurrency($currencyCode);

        /** @var RicardoMartins_PagBank_Model_Request_Object_Charge $charges */
        $charges = Mage::getModel('ricardomartins_pagbank/request_object_charge');
        $charges->setCreatedAt();
        $orderIncrementId = $this->order->getIncrementId() ? $this->order->getIncrementId() : $this->order->getReservedOrderId();
        $charges->setReferenceId($orderIncrementId);
        $charges->setAmount($amount->getData());
        $charges->setPaymentMethod($paymentMethod->getData());

        $result[self::CHARGES][] = $charges->getData();

        return $result;
    }
}
