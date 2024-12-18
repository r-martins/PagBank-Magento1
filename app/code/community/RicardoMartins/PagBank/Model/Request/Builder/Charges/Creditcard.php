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
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function build()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $result = [];
        $storeId = $this->order->getStoreId();
        $payment = $this->order->getPayment();

        /** @var RicardoMartins_PagBank_Model_Request_Object_Holder $holder */
        $holder = Mage::getModel('ricardomartins_pagbank/request_object_holder');
        $holder->setName($payment->getAdditionalInformation('cc_owner'));

        /** @var RicardoMartins_PagBank_Model_Request_Object_PaymentMethod_Card $card */
        $card = Mage::getModel('ricardomartins_pagbank/request_object_paymentmethod_card');
        $card->setHolder($holder->getData());
        $card->setEncrypted($payment->getAdditionalInformation('cc_number_encrypted'));

        $selectedInstallments = (int) $payment->getAdditionalInformation('cc_installments');

        /** @var RicardoMartins_PagBank_Model_Request_Object_Paymentmethod $paymentMethod */
        $paymentMethod = Mage::getModel('ricardomartins_pagbank/request_object_paymentmethod');
        $paymentMethod->setType(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_CREDIT_CARD);
        $paymentMethod->setInstallments($selectedInstallments);
        $paymentMethod->setCapture(true);
        $paymentMethod->setCard($card->getData());

        if ($helper->isCc3dsEnabled()) {
            $authenticationMethodData = $this->getAuthenticationMethodData($payment);

            if ($authenticationMethodData) {
                $paymentMethod->setAuthenticationMethod($authenticationMethodData);
            }
        }

        $softDescriptor = Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/soft_descriptor', $storeId);
        $paymentMethod->setSoftDescriptor($softDescriptor);

        $totalAmount = $this->order->getBaseGrandTotal();
        
        /** @var RicardoMartins_PagBank_Model_Request_Object_Amount $amount */
        $amount = Mage::getModel('ricardomartins_pagbank/request_object_amount');
        
        if ($selectedInstallments > 1) {
            $creditCardBin = $payment->getAdditionalInformation('cc_bin');
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
                $helper->writeLog('Erro ao obter as condições de parcelamento. Por favor, tente novamente. ' . $e->getMessage());
                Mage::throwException($helper->__('Erro ao obter as condições de parcelamento. Por favor, tente novamente. ' . $e->getMessage()));
            }

            $installment = $helper->extractInstallment($installmentsPlans, $selectedInstallments);
            if (!$installment['interest_free']) {
                $totalAmount = (int) $installment['amount']['value'];
                $totalAmount = $totalAmount / 100;
            }

            if (isset($installment['amount']['fees'])) {
                /** @var RicardoMartins_PagBank_Model_Request_Object_Interest $interest */
                $interest = Mage::getModel('ricardomartins_pagbank/request_object_interest');
                $interest->setInstallments($installment['amount']['fees']['buyer']['interest']['installments']);
                $interest->setTotal($installment['amount']['fees']['buyer']['interest']['total']);
                
                /** @var RicardoMartins_PagBank_Model_Request_Object_Buyer $buyer */
                $buyer = Mage::getModel('ricardomartins_pagbank/request_object_buyer');
                $buyer->setInterest($interest->getData());
                
                /** @var RicardoMartins_PagBank_Model_Request_Object_Fees $fees */
                $fees = Mage::getModel('ricardomartins_pagbank/request_object_fees');
                $fees->setBuyer($buyer->getData());
                
                $amount->setFees($fees->getData());
            }
        }
        
        $amount->setValue($totalAmount);

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

        $orderIncrementId = $this->order->getIncrementId() ? $this->order->getIncrementId() : $this->order->getReservedOrderId();
        $charges->setReferenceId($orderIncrementId);
        $charges->setAmount($amount->getData());
        $charges->setPaymentMethod($paymentMethod->getData());

        $result[self::CHARGES][] = $charges->getData();

        return $result;
    }

    /**
     * @param $payment
     * @return array|mixed|null
     * @throws Mage_Core_Exception
     */
    private function getAuthenticationMethodData($payment)
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $has3DsSession = $payment->getAdditionalInformation('cc_has_session');
        $allowContinue = $helper->allowContinueWithout3ds();
        $cc3dsCardId = $payment->getAdditionalInformation('cc_3ds_id');

        if (!$has3DsSession && !$allowContinue) {
            Mage::throwException($helper->__('Erro ao obter a sessão 3D Secure PagBank. Por favor, utilize outro cartão ou método de pagamento.'));
        }

        if (!$cc3dsCardId && !$allowContinue) {
            Mage::throwException($helper->__('Erro ao obter o 3D Secure PagBank ID. Por favor, utilize outro cartão ou método de pagamento.'));
        }

        if (!$cc3dsCardId) {
            return null;
        }

        /** @var RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Card_Authenticationmethod $authenticationMethod */
        $authenticationMethod = Mage::getModel('ricardomartins_pagbank/request_object_paymentmethod_card_authenticationmethod');
        $authenticationMethod->setType(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface::AUTHENTICATION_METHOD_TYPE_VALUE);
        $authenticationMethod->setCardId($cc3dsCardId);

        return $authenticationMethod->getData();
    }
}
