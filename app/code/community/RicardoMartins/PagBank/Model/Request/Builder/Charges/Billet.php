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

        $address = new RicardoMartins_PagBank_Model_Request_Object_Customer_Address();
        $address->setStreet($billingAddress->getStreet(1));
        $address->setNumber($billingAddress->getStreet(2));
        $address->setComplement($billingAddress->getStreet(3));
        $address->setLocality($billingAddress->getStreet(4));
        $address->setCity($billingAddress->getCity());
        $address->setRegion($billingAddress->getRegionCode(), $billingAddress->getCountryId());
        $address->setRegionCode($billingAddress->getRegionCode());
        $address->setPostalCode($billingAddress->getPostcode());
        $address->setCountry();

        $document = $helper->getDocumentValue($this->order);

        $holder = new RicardoMartins_PagBank_Model_Request_Object_Holder();
        $holder->setName($this->order->getCustomerFirstname() . ' ' . $this->order->getCustomerLastname());
        $holder->setTaxId($document);
        $holder->setEmail($this->order->getCustomerEmail());
        $holder->setAddress($address->getData());

        $instructionLines = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod_Billet_InstructionLines();
        $instructionLines->setLineOne(Mage::getStoreConfig('payment/ricardomartins_pagbank_billet/instruction_line_one', $storeId));
        $instructionLines->setLineTwo(Mage::getStoreConfig('payment/ricardomartins_pagbank_billet/instruction_line_two', $storeId));

        $billet = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod_Billet();
        $expiration = Mage::getStoreConfig('payment/ricardomartins_pagbank_billet/expiration_time', $storeId) ?: 3;
        $billet->setDueDate(date('Y-m-d', strtotime('+' . $expiration . 'day')));
        $billet->setInstructionLines($instructionLines->getData());
        $billet->setHolder($holder->getData());

        $paymentMethod = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod();
        $paymentMethod->setType(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_BILLET);
        $paymentMethod->setBillet($billet->getData());

        $amount = new RicardoMartins_PagBank_Model_Request_Object_Amount();
        $amount->setValue($this->order->getBaseGrandTotal());
        $amount->setCurrency($this->order->getOrderCurrency()->getCode());

        $charges = new RicardoMartins_PagBank_Model_Request_Object_Charge();
        $charges->setCreatedAt();
        $charges->setReferenceId($this->order->getIncrementId());
        $charges->setAmount($amount->getData());
        $charges->setPaymentMethod($paymentMethod->getData());

        $result[self::CHARGES][] = $charges->getData();

        return $result;
    }
}
