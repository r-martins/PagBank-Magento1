<?php

class RicardoMartins_PagBank_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get the orders endpoint
     *
     * @param $storeId
     * @return string
     */
    public function getOrdersEndpoint($storeId = null)
    {
        $endpoint = RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_ORDERS;
        if ($this->isSandbox($storeId)) {
            return $endpoint . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return $endpoint;
    }

    /**
     * Get the public key endpoint
     *
     * @param $storeId
     * @return string
     */
    public function getPublicKeyEndpoint($storeId = null)
    {
        $endpoint = RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_PUBLIC_KEY;
        if ($this->isSandbox($storeId)) {
            return $endpoint . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return $endpoint;
    }

    /**
     * Get the interest endpoint
     *
     * @param $storeId
     * @return string
     */
    public function getInterestEndpoint($storeId = null)
    {
        $endpoint = RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_INTEREST;
        if ($this->isSandbox($storeId)) {
            return $endpoint . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return $endpoint;
    }

    /**
     * Get the orders consult endpoint
     *
     * @param $storeId
     * @return string
     */
    public function getPaymentInfoEndpoint($storeId = null)
    {
        $endpoint = RicardoMartins_PagBank_Api_Connect_ConnectInterface::WS_ENDPOINT_PAYMENT_INFO . '/{pagbankOrderId}/';
        if ($this->isSandbox($storeId)) {
            return $endpoint . '?' . RicardoMartins_PagBank_Api_Connect_ConnectInterface::SANDBOX_PARAM;
        }

        return $endpoint;
    }

    /**
     * Get if the connect key is from sandbox
     *
     * @param $storeId
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
     * Get the connect key
     *
     * @param $storeId
     * @return string
     */
    public function getConnectKey($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank/connect_key', $storeId);
    }

    /**
     * Get the public key
     *
     * @param $storeId
     * @return mixed
     */
    public function getPublicKey($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank/public_key', $storeId);
    }

    /**
     * Get the installments options
     *
     * @param $storeId
     * @return mixed
     */
    public function getInstallmentsOptions($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_options', $storeId);
    }

    /**
     * Get the number of installments without interest
     *
     * @param $storeId
     * @return mixed
     */
    public function getInstallmentsWithoutInterestNumber($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_options_fixed', $storeId);
    }

    /**
     * Get the minimum amount for installments
     *
     * @param $storeId
     * @return mixed
     */
    public function getInstallmentsMinAmount($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_options_min_total', $storeId);
    }

    /**
     * Check if the installments limit is enabled
     *
     * @param $storeId
     * @return mixed
     */
    public function isEnabledInstallmentsLimit($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/enable_installments_limit', $storeId);
    }

    /**
     * Get the installments limit
     *
     * @param $storeId
     * @return mixed
     */
    public function getInstallmentsLimit($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/installments_limit', $storeId);
    }

    /**
     * Get the maximum number of installments without interest
     *
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
     * Get the headers for the API request
     *
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
     * Get the document value from different sources
     *
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
     *
     * @param $storeId
     * @return false|string
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
     * Write something to pagbank.log
     *
     * @param $obj mixed|string
     */
    public function writeLog($obj)
    {
        if ($this->isDebugActive()) {
            if (is_string($obj)) {
                Mage::log($obj, Zend_Log::DEBUG, 'pagbank.log', true);
            } else {
                Mage::log(var_export($obj, true), Zend_Log::DEBUG, 'pagbank.log', true);
            }
        }
    }

    /**
     * Check if the debug mode is active
     *
     * @return mixed
     */
    public function isDebugActive()
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank/debug');
    }

    /**
     * @param $minTotal
     * @param $amount
     * @return int
     */
    private function calculeInstallmentsNumberWithMinTotal($minTotal, $amount)
    {
        $installments = floor($amount / $minTotal);
        return (int)min($installments, 18);
    }
}