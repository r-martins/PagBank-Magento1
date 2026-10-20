<?php

// Maho/OpenMage/Magento compatibility: alias Varien_Object when missing (Maho uses Maho\DataObject)
if (!class_exists('Varien_Object', false) && class_exists('Maho\DataObject')) {
    class_alias('Maho\DataObject', 'Varien_Object');
}

class RicardoMartins_PagBank_Model_Method_Cc extends RicardoMartins_PagBank_Model_Method_Abstract
{
    const METHOD_CODE = 'ricardomartins_pagbank_cc';
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
    const STATUS_PAGBANK = 'status_pagbank';

    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'ricardomartins_pagbank/form_cc';
    protected $_infoBlockType = 'ricardomartins_pagbank/form_info_cc';
    protected $_canUseForMultishipping  = false;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

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
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $info->setAdditionalInformation(self::CC_NUMBER_ENCRYPTED, $data->getData('cc_number_encrypted'));
        $info->setAdditionalInformation(self::CC_BRAND, $data->getData('cc_brand'));
        $info->setAdditionalInformation(self::CC_OWNER, $data->getData('cc_owner'));
        $info->setAdditionalInformation(self::CC_BIN, $data->getData('cc_bin'));
        $info->setAdditionalInformation(self::CC_INSTALLMENTS, $data->getData('cc_installments'));
        $info->setAdditionalInformation(self::DOCUMENT, $helper->normalizeDocument($data->getData('data_tax_id')));
        $info->setAdditionalInformation(self::CC_PAGBANK_SESSION, $data->getData('cc_has_session'));

        if ($data->getData('cc_3ds_id')) {
            $info->setAdditionalInformation(self::CC_3DS_ID, $data->getData('cc_3ds_id'));
        }
        if ($data->getData('payment_profile_id')) {
            $info->setAdditionalInformation('payment_profile_id', (int) $data->getData('payment_profile_id'));
        }
        if ($data->getData('payment_company_code')) {
            $info->setAdditionalInformation('payment_company_code', $data->getData('payment_company_code'));
        }
        if ($data->getData('cc_3ds_payload')) {
            $info->setAdditionalInformation('cc_3ds_payload', $data->getData('cc_3ds_payload'));
        }

        return $this;
    }

    /**
     * @param Varien_Object|\Maho\DataObject $payment
     * @param $amount
     * @return $this|RicardoMartins_PagBank_Model_Method_Cc
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function order($payment, $amount)
    {
        /** @var RicardoMartins_PagBank_Helper_PaymentData $paymentData */
        $paymentData = Mage::helper('ricardomartins_pagbank/paymentData');
        $paymentData->stampPartner($payment, $this->getOrder()->getStoreId());

        $response = $this->ccPayment($this->getOrder());
        $charges = isset($response['charges'][0]) ? $response['charges'][0] : array();
        $paymetResponse = isset($charges['payment_response']) ? $charges['payment_response'] : array();
        $paymetMethod = isset($charges['payment_method']) ? $charges['payment_method'] : array();

        $addData = unserialize($this->getOrder()->getPayment()->getAdditionalData());
        if (!is_array($addData)) {
            $addData = array();
        }
        $addData[self::ORDER_ID] = $response['id'];
        $partner = $paymentData->resolveWritePartner($payment);
        $addData[$paymentData->physicalKey('order_id', $partner)] = $response['id'];

        $paymentResponseCode = isset($paymetResponse['code']) ? $paymetResponse['code'] : null;
        $chargeStatus = isset($charges['status'])
            ? $charges['status']
            : (isset($response['status']) ? $response['status'] : '');
        // PagBank: 20000 = approved. Vindi antifraud may return WAITING + 20002 ("Em Monitoramento")
        // with HTTP 201 — charge exists; webhook/cron will move to PAID or DECLINED.
        $acceptedStatuses = array(
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_PAID,
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_WAITING,
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_IN_ANALYSIS,
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_AUTHORIZED,
        );
        $paymentAccepted = ($paymentResponseCode == 20000)
            || in_array($chargeStatus, $acceptedStatuses, true);

        if ($paymentAccepted) {
            $addData[self::IS_SANDBOX] = !empty($response['is_sandbox']) ? 'Yes' : 'No';

            $card = isset($paymetMethod['card']) ? $paymetMethod['card'] : array();
            $addData[self::CC_BRAND] = isset($card['brand']) ? $card['brand'] : $payment->getAdditionalInformation(self::CC_BRAND);
            $addData[self::CC_LAST_4] = isset($card['last_digits']) ? $card['last_digits'] : '';
            $addData[self::CC_EXP_MONTH] = isset($card['exp_month']) ? $card['exp_month'] : '';
            $addData[self::CC_EXP_YEAR] = isset($card['exp_year']) ? $card['exp_year'] : '';
            $addData[self::CC_OWNER] = isset($card['holder']['name'])
                ? $card['holder']['name']
                : $payment->getAdditionalInformation(self::CC_OWNER);
            $addData[self::CC_INSTALLMENTS] = isset($paymetMethod['installments'])
                ? $paymetMethod['installments']
                : $payment->getAdditionalInformation(self::CC_INSTALLMENTS);
            $addData[self::CHARGE_ID] = $charges['id'];
            $addData[$paymentData->physicalKey('charge_id', $partner)] = $charges['id'];
            $addData[self::STATUS_PAGBANK] = $chargeStatus;

            $paymentRawData = isset($paymetResponse['raw_data']) ? $paymetResponse['raw_data'] : array();
            $addData[self::AUTHORIZATION_CODE] = isset($paymentRawData['authorization_code'])
                ? $paymentRawData['authorization_code'] : '';
            $addData[self::NSU] = isset($paymentRawData['nsu']) ? $paymentRawData['nsu'] : '';

            $branding = RicardoMartins_PagBank_Model_Partner_Branding::forOrderPartner($partner);
            $portalUrl = $branding->adminChargeUrl(
                $charges['id'],
                !empty($response['is_sandbox'])
            );
            if ($portalUrl) {
                $addData[self::CHARGE_LINK] = $portalUrl;
            }

            $payment->setAdditionalData(serialize($addData));
            $payment->save();
        } else {
            $reason = isset($paymetResponse['message']) && $paymetResponse['message'] !== ''
                ? $paymetResponse['message']
                : '';
            $declinedStatuses = array(
                RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_DECLINED,
                RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_CANCELED,
                RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_DENIED,
            );
            $isDeclined = in_array($chargeStatus, $declinedStatuses, true)
                || $paymentResponseCode == 20001;

            if ($isDeclined) {
                // Hard decline/cancel — do not suggest "try again later" (implies outage).
                $statusLabel = $chargeStatus !== ''
                    ? $chargeStatus
                    : RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_DECLINED;
                $statusHint = 'DECLINED';
                if ($statusLabel === RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_CANCELED) {
                    $statusHint = 'CANCELED';
                } elseif ($statusLabel === RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_DENIED) {
                    $statusHint = 'DENIED';
                }
                $helper = $this->_getHelper();
                $message = $helper->__('Payment failed!')
                    . "\n" . $helper->__('Status: %s (%s)', $statusLabel, $helper->__($statusHint));
                if ($reason !== '') {
                    $message .= "\n" . $this->_getHelper()->__('Reason: %s.', $reason);
                }
                Mage::throwException($message);
            }

            $fallback = $reason !== '' ? $reason : ($chargeStatus !== '' ? $chargeStatus : 'unknown');
            Mage::throwException($this->_getHelper()->__(
                "Payment error!\nReason: %s.\nPlease check your card details or use another payment method.",
                $fallback
            ));
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
     * Refund payment
     * @param Varien_Object|\Maho\DataObject $payment
     * @param float $amount
     * @return Mage_Payment_Model_Method_Abstract
     * @throws Mage_Core_Exception
     */
    public function refund($payment, $amount)
    {
        return parent::refund($payment, $amount);
    }
}