<?php
class RicardoMartins_PagBank_Model_Request_Builder_Charges_CreditCard
{
    /**
     * Represents all data available on a charge.
     * Receives an array of charges.
     */
    const CHARGES = 'charges';

    /**
     * @var $order
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
        $payment = $this->order->getPayment();

        $charges = new RicardoMartins_PagBank_Model_Request_Object_Charge();
        $charges->setReferenceId($this->order->getIncrementId());

        $amount = new RicardoMartins_PagBank_Model_Request_Object_Amount();
        $amount->setValue($this->order->getGrandTotalAmount());
        $amount->setCurrency($this->order->getCurrencyCode());

        $charges->setAmount($amount->getData());

        $holder = new RicardoMartins_PagBank_Model_Request_Object_Holder();
        $holder->setName($payment->getData('cc_owner'));

        $card = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod_Card();
        $card->setHolder($holder->getData());
        $card->setEncrypted($payment->getAdditionalInformation('cc_number_encrypted'));

        $paymentMethod = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod();
        $paymentMethod->setType(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_CREDIT_CARD);
        $paymentMethod->setInstallments((int) $payment->getAdditionalInformation('cc_installments'));
        $paymentMethod->setCapture(true);
        $paymentMethod->setCard($card->getData());

        $softDescriptor = Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/soft_descriptor', $storeId);
        $paymentMethod->setSoftDescriptor($softDescriptor);

        $charges->setPaymentMethod($paymentMethod->getData());

        $result[self::CHARGES][] = $charges->getData();

        return $result;
    }
}
