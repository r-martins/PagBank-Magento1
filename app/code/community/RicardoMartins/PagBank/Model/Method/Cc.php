<?php

class RicardoMartins_PagBank_Model_Method_Cc extends RicardoMartins_PagBank_Model_Method_Abstract
{
    const CHARGE_ID = 'charge_id';
    const CHARGE_LINK = 'charge_link';
    const DOCUMENT = 'tax_id';
    const CC_OWNER = 'cc_owner';
    const CC_LAST_4 = 'cc_last_4';
    const CC_EXP_MONTH = 'cc_exp_month';
    const CC_EXP_YEAR = 'cc_exp_year';
    const CC_BRAND = 'cc_brand';
    const CC_INSTALLMENTS = 'cc_installments';
    const CC_NUMBER_ENCRYPTED = 'cc_number_encrypted';
    const CC_BIN = 'cc_bin';
    const CC_3DS_ID = 'cc_3ds_id';
    const CC_PAGBANK_SESSION = 'cc_has_session';
    const AUTHORIZATION_CODE = 'authorization_code';
    const NSU = 'nsu';

    protected $_code = 'ricardomartins_pagbank_cc';
    protected $_formBlockType = 'ricardomartins_pagbank/form_cc';
    protected $_order = null;
    protected $_canUseForMultishipping  = false;

    /**
     * @param $data
     * @return $this|RicardoMartins_PagBank_Model_Method_Cc
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation(self::CC_NUMBER_ENCRYPTED, $data->getData('cc_number_encrypted'));
        $info->setAdditionalInformation(self::CC_OWNER, $data->getData('cc_owner'));
        $info->setAdditionalInformation(self::CC_BIN, $data->getData('cc_bin'));
        $info->setAdditionalInformation(self::CC_INSTALLMENTS, $data->getData('cc_installments'));
        $info->setAdditionalInformation(self::DOCUMENT, $data->getData('data_tax_id'));
        $info->setAdditionalInformation(self::CC_PAGBANK_SESSION, $data->getData('cc_has_session'));

        if ($data->getData('cc_3ds_id')) {
            $info->setAdditionalInformation(self::CC_3DS_ID, $data->getData('cc_3ds_id'));
        }

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param $amount
     * @return $this|RicardoMartins_PagBank_Model_Method_Cc
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function order(Varien_Object $payment, $amount)
    {
        $response = $this->ccPayment($this->getOrder());
        $charges = $response['charges'][0];
        $paymetResponse = $charges['payment_response'];
        $paymetMethod = $charges['payment_method'];

        if ($charges['payment_response']['code'] == 20000) {
            $addData = unserialize($this->getOrder()->getPayment()->getAdditionalData());

            $card = $paymetMethod['card'];
            $addData[self::CC_BRAND] = $card['brand'];
            $addData[self::CC_LAST_4] = $card['last_digits'];
            $addData[self::CC_EXP_MONTH] = $card['exp_month'];
            $addData[self::CC_EXP_YEAR] = $card['exp_year'];
            $addData[self::CC_OWNER] = $card['holder']['name'];
            $addData[self::CC_INSTALLMENTS] = $paymetMethod['installments'];
            $addData[self::CHARGE_ID] = $charges['id'];

            $paymentRawData = $paymetResponse['raw_data'];
            $addData[self::AUTHORIZATION_CODE] = $paymentRawData['authorization_code'];
            $addData[self::NSU] = $paymentRawData['nsu'];

            if (isset($response['is_sandbox']) && !$response['is_sandbox']) {
                $chargeIdWithoutPrefix = str_replace('CHAR_', '', $addData[self::CHARGE_ID]);
                $transactionLink = RicardoMartins_PagBank_Api_Connect_ConnectInterface::PAGBANK_TRANSACTION_DETAILS_URL . $chargeIdWithoutPrefix;
                $addData['charge_link'] = $transactionLink;
            }

            $payment->setAdditionalData(serialize($addData));
            $payment->save();
        } else {
            Mage::throwException($this->_getHelper()->__("Erro no pagamento!\nMotivo: " . $paymetMethod->error_description . ".\nPor favor tente novamente mais tarde!"));
        }

        return $this;
    }

    /**
     * @param $order
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    private function ccPayment($order)
    {
        /** @var RicardoMartins_PagBank_Model_Request_Builder_Order $orderBuilder */
        $orderBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_order', $order);
        $orderData = $orderBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Customer $customerBuilder */
        $customerBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_customer', $order);
        $customerData = $customerBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Items $itemsBuilder */
        $itemsBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_items', $order);
        $itemsData = $itemsBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Shipping $shippingBuilder */
        $shippingBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_shipping', $order);
        $shippingData = $shippingBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Charges_Creditcard $creditCardBuilder */
        $creditCardBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_charges_creditcard', $order);
        $creditCardData = $creditCardBuilder->build();

        $data = array_merge(
            $orderData,
            $customerData,
            $itemsData,
            $shippingData,
            $creditCardData
        );

        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getOrdersEndpoint();

        return $api->placePostRequest($endpoint, $data);
    }

    /**
     * Get current order object
     * @return false|mixed|null
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = Mage::registry('current_order');
            if (!$this->_order) {
                $sessionInstance = Mage::getModel("core/session")->getSessionQuote();
                $this->_order = Mage::getModel($sessionInstance)->getQuote();
                if (!$this->_order) {
                    return false;
                }
            }
        }
        return $this->_order;
    }
}