<?php

class RicardoMartins_PagBank_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return string
     */
    public function getOrdersEndpoint($storeId = null)
    {
        if ($this->isSandbox($storeId)) {
            return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_ORDERS
                . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_ORDERS;
    }

    /**
     * @return string
     */
    public function getPublicKeyEndpoint($storeId = null)
    {
        if ($this->isSandbox($storeId)) {
            return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_PUBLIC_KEY
                . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_PUBLIC_KEY;
    }

    /**
     * @return string
     */
    public function getInterestEndpoint($storeId = null)
    {
        if ($this->isSandbox($storeId)) {
            return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_INTEREST
                . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_INTEREST;
    }

    /**
     * @return bool
     */
    public function isSandbox($storeId = null)
    {
        $connectKey = $this->getConnectKey($storeId);
        if (str_contains($connectKey, RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PREFIX)) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getConnectKey($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank/connect_key', $storeId);
    }

    /**
     * @return mixed
     */
    public function getPublicKey($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank/public_key', $storeId);
    }

    public function getInstallmentsOptions($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_options', $storeId);
    }

    public function getInstallmentsWithoutInterestNumber($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_options_fixed', $storeId);
    }

    public function getInstallmentsMinAmount($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_options_min_total', $storeId);
    }

    public function isEnabledInstallmentsLimit($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/enable_installments_limit', $storeId);
    }

    public function getInstallmentsLimit($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_limit', $storeId);
    }

    /**
     * @param $amount
     * @param $storeId
     * @return int|mixed|null
     */
    public function getMaxInstallmentsNoInterest($amount, $storeId = null)
    {
        $installmentsOptions = $this->getInstallmentsOptions($storeId);
        switch ($installmentsOptions) {
            case 'fixed':
                return $this->getInstallmentsWithoutInterestNumber($storeId);
            case 'buyer':
                return 0;
            case 'min_total':
                return $this->calculeInstallmentsNumberWithMinTotal(
                    $this->getInstallmentsMinAmount($storeId),
                    $amount
                );
            default:
                return null;
        }
    }


    /**
     * @return string[]
     */
    public function getHeaders()
    {
        return [
            'Authorization: Bearer ' . $this->getConnectKey(),
            'Accept: application/json',
            'Content-Type: application/json',
            'Api-Version: 4.0'
        ];
    }

    /**
     * @param $order
     * @return array|string|string[]|null
     */
    public function getDocumentValue($order)
    {
        $payment = $order->getPayment();
        $documentFrom = Mage::getStoreConfig('payment/ricardomartins_pagbank/document_from');

        switch ($documentFrom) {
            case 'taxvat':
                $document = $order->getCustomerTaxvat();
                break;
            case 'vat_id':
                $document = $order->getBillingAddress()->getVatId();
                break;
            default:
                $document = $payment->getAdditionalInformation('tax_id');
                break;
        }

        if (!$document) {
            $document = $payment->getAdditionalInformation('tax_id');
        }

        return preg_replace('/[^0-9]/','', $document);
    }

    /**
     * Serialized (json) string with module configuration
     * return string
     */
    public function getConfigJs($storeId = null)
    {
        $config = [
            'ricardomartins_pagbank' => [
                'publicKey' => $this->getPublicKey($storeId),
                'installmentsEndpoint' => $this->_getUrl('pagbank/ajax/getinstallments', ['_secure' => true]),
            ],
        ];
        return json_encode($config);
    }

    /**
     * Return labels for additional information
     *
     * @param string $fieldName
     * @return string
     */
    public function getInfoLabels($fieldName)
    {
        switch ($fieldName) {
            case RicardoMartins_PagBank_Model_Method_Cc::CC_BRAND:
                return $this->__('Card Brand:');
            case RicardoMartins_PagBank_Model_Method_Cc::CC_LAST_4:
                return $this->__('Card Last 4 Digits:');
            case RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_MONTH:
                return $this->__('Card Expiration Month:');
            case RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_YEAR:
                return $this->__('Card Expiration Year:');
            case RicardoMartins_PagBank_Model_Method_Cc::CC_OWNER:
                return $this->__('Card Owner:');
            case RicardoMartins_PagBank_Model_Method_Cc::CC_INSTALLMENTS:
                return $this->__('Installments:');
            case RicardoMartins_PagBank_Model_Method_Cc::CHARGE_ID:
                return $this->__('Charge ID:');
            case RicardoMartins_PagBank_Model_Method_Cc::CHARGE_LINK:
                return $this->__('Charge Link:');
            case RicardoMartins_PagBank_Model_Method_Cc::AUTHORIZATION_CODE:
                return $this->__('Authorization Code:');
            case RicardoMartins_PagBank_Model_Method_Cc::NSU:
                return $this->__('NSU:');
        }
        return '';
    }

    /**
     * Extracts the installment information from the array returned by the API
     *
     * @param $installments
     * @param $installmentNumber
     *
     * @return false|mixed
     */
    public static function extractInstallment($installments, $installmentNumber)
    {
        foreach ($installments as $installment){
            if ($installment['installments'] == $installmentNumber){
                return $installment;
            }
        }
        return false;
    }

    /**
     * @param $minTotal
     * @param $amount
     * @return int
     */
    private function calculeInstallmentsNumberWithMinTotal($minTotal, $amount)
    {
        $installments = floor($amount / $minTotal);
        return (int) min($installments, 18);
    }
}