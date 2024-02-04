<?php
class RicardoMartins_PagBank_Model_Request_Builder_Charges_Creditcard
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
        $payment = $this->order->getPayment();

        $holder = new RicardoMartins_PagBank_Model_Request_Object_Holder();
        $holder->setName($payment->getAdditionalInformation('cc_owner'));

        $card = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod_Card();
        $card->setHolder($holder->getData());
        $card->setEncrypted($payment->getAdditionalInformation('cc_number_encrypted'));

        $selectedInstallments = (int) $payment->getAdditionalInformation('cc_installments');
        $paymentMethod = new RicardoMartins_PagBank_Model_Request_Object_PaymentMethod();
        $paymentMethod->setType(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_CREDIT_CARD);
        $paymentMethod->setInstallments($selectedInstallments);
        $paymentMethod->setCapture(true);
        $paymentMethod->setCard($card->getData());

        $softDescriptor = Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/soft_descriptor', $storeId);
        $paymentMethod->setSoftDescriptor($softDescriptor);

        $totalAmount = $this->order->getBaseGrandTotal();
        if ($selectedInstallments > 1) {
            $creditCardBin = $payment->getAdditionalInformation('cc_bin');
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

            $installment = $helper->extractInstallment($installmentsPlans, $selectedInstallments);
            if (!$installment['interest_free']) {
                $totalAmount = (int) $installment['amount']['value'];
                $totalAmount = $totalAmount / 100;
            }
        }

        $amount = new RicardoMartins_PagBank_Model_Request_Object_Amount();
        $amount->setValue($totalAmount);
        $amount->setCurrency($this->order->getOrderCurrency()->getCode());

        $charges = new RicardoMartins_PagBank_Model_Request_Object_Charge();
        $charges->setReferenceId($this->order->getIncrementId());
        $charges->setAmount($amount->getData());
        $charges->setPaymentMethod($paymentMethod->getData());

        $result[self::CHARGES][] = $charges->getData();

        return $result;
    }
}
