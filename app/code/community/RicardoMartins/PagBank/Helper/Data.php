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
     * Get the 3D Secure session endpoint
     *
     * @param $storeId
     * @return string
     */
    public function get3DSecureSessionEndpoint($storeId = null)
    {
        $endpoint = RicardoMartins_PagBank_Api_Connect_ConnectInterface::CHECKOUT_SDK_SESSION_ENDPOINT;
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
     * Check if the 3DS is enabled
     *
     * @param $storeId
     * @return bool
     */
    public function isCc3dsEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag('payment/ricardomartins_pagbank_cc/cc_3ds', $storeId);
    }

    /**
     * Check if the 3DS is enabled
     *
     * @param $storeId
     * @return mixed
     */
    public function allowContinueWithout3ds($storeId = null)
    {
        return Mage::getStoreConfigFlag('payment/ricardomartins_pagbank_cc/cc_3ds_allow_continue', $storeId);
    }

    /**
     * Get the flag size
     *
     * @param $storeId
     * @return mixed
     */
    public function getFlagSize($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_cc/flag', $storeId);
    }

    /**
     * Check if the STC mirror is enabled
     *
     * @param $storeId
     * @return bool
     */
    public function isStcMirrorEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/stc_mirror', $storeId);
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
     * @return bool
     */
    public function isEnabledInstallmentsLimit($storeId = null)
    {
        return Mage::getStoreConfigFlag('payment/ricardomartins_pagbank_cc/enable_installments_limit', $storeId);
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
     * Get the place order button css selector
     *
     * @return mixed
     */
    public function getPlaceOrderButton($storeId = null)
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank/placeorder_button', $storeId);
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
            'publicKey' => $this->getPublicKey($storeId),
            'installments_endpoint' => $this->_getUrl('pagbank/ajax/getinstallments', ['_secure' => true]),
            'quotedata_endpoint' => $this->_getUrl('pagbank/ajax/getquotedata', ['_secure' => true]),
            'placeorder_button' => $this->getPlaceOrderButton($storeId),
            'enabled_3ds' => $this->isCc3dsEnabled($storeId),
            'cc_3ds_allow_continue' => $this->allowContinueWithout3ds($storeId),
            'environment' => $this->isSandbox($storeId) ? 'SANDBOX' : 'PROD',
            'stc_mirror' => $this->isStcMirrorEnabled($storeId),
            'flag_size' => $this->getFlagSize($storeId)
        ];

        try {
            $config['loader_url'] = Mage::getDesign()->getSkinUrl('images/ricardomartins/pagbank/ajax-loader.gif', ['_secure'=>true]);
        } catch (Exception $e) {
            $config['loader_url'] = '';
        }

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
     * @return bool
     */
    public function isDebugActive()
    {
        return Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/debug');
    }

    /**
     * Load the required js script block
     *
     * @return Mage_Core_Block_Text
     */
    public function getPagBankScriptBlock()
    {
        $scriptBlock = Mage::app()->getLayout()->createBlock('core/text', "ricardomartins.pagbank.js");
        $secure = Mage::getStoreConfigFlag('web/secure/use_in_frontend');

        $scripts = sprintf(
            '<script type="text/javascript" src="%s"></script>',
            Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS, $secure) . 'ricardomartins/pagbank/jquery/jquery.mask.min.js'
        );

        $scriptBlock->setText($scripts);
        return $scriptBlock;
    }

    /**
     * Load the required credit card scripts
     *
     * @return Mage_Core_Block_Text
     */
    public function getPagBankScriptCcBlock()
    {
        $scriptBlock = Mage::app()->getLayout()->createBlock('core/text', "ricardomartins.pagbank.cc.js");
        $secure = Mage::getStoreConfigFlag('web/secure/use_in_frontend');

        $scripts = sprintf(
            '
                <script type="text/javascript" src="%s"></script>
                <script type="text/javascript" src="%s"></script>
                ',
            'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js',
            $this->getModuleJsUrl($secure)
        );

        $scriptBlock->setText($scripts);
        return $scriptBlock;
    }

    /**
     * Load the style block
     *
     * @return Mage_Core_Block_Text
     */
    public function getPagBankStyleBlock()
    {
        $scriptBlock = Mage::app()->getLayout()->createBlock('core/text', "ricardomartins.pagbank.style");
        $secure = Mage::getStoreConfigFlag('web/secure/use_in_frontend');

        $styles = sprintf(
            '<link rel="stylesheet" href="%s">',
            Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN, $secure) . 'frontend/base/default/css/ricardomartins/pagbank/styles.css'
        );

        $scriptBlock->setText($styles);
        return $scriptBlock;
    }

    /**
     * Gets /ricardomartins/pagbank/creditcard.js URL (from this store or from jsDelivr CDNs)
     * @param $secure bool
     *
     * @return string
     */
    public function getModuleJsUrl($secure)
    {
        if (Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/jsdelivr_enabled')) {
            $min = (Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/jsdelivr_minify')) ? '.min' : '';
            $moduleVersion = (string)Mage::getConfig()->getModuleConfig('RicardoMartins_PagBank')->version;
            $url = 'https://cdn.jsdelivr.net/gh/r-martins/PagBank-Magento1@%s/js/ricardomartins/pagbank/creditcard%s.js';
            $url = sprintf($url, $moduleVersion, $min);
            return $url;
        }

        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS, $secure) . 'ricardomartins/pagbank/creditcard.js';
    }

    /**
     * Get response errors from the API and return a string with the friendly error messages
     *
     * @param $responseErrors
     * @return string
     */
    public function handleErrorMessages($responseErrors)
    {
        $errors = [];
        foreach ($responseErrors as $error) {
            $code = $this->getFriendlyCode(isset($error['code']) ? $error['code'] : '');
            $description = $this->getFriendlyDescription($error['description']);
            $parameter = $this->getFriendlyParameter($error['parameter_name']);
            $errors[] = sprintf('%s: %s - %s', $code, $parameter, $description);
        }
        return implode(' ', $errors);
    }

    /**
     * @param $code
     * @return string
     */
    private function getFriendlyCode($code)
    {
        switch ($code) {
            case '40001':
                return 'Parâmetro obrigatório';
            case '40002':
                return 'Parâmetro inválido';
            case '40003':
                return 'Parâmetro desconhecido ou não esperado';
            case '40004':
                return 'Limite de uso da API excedido';
            case '40005':
                return 'Método não permitido';
            default:
                return 'Erro desconhecido';
        }
    }

    /**
     * @param $description
     * @return string
     */
    private function getFriendlyDescription($description)
    {
        switch ($description) {
            case "must match the regex: ^\\p{L}+['.-]?(?:\\s+\\p{L}+['.-]?)+$":
                return 'parece inválido ou fora do padrão permitido';
            case 'cannot be blank':
                return 'não pode estar em branco';
            case 'size must be between 8 and 9':
                return 'deve ter entre 8 e 9 caracteres';
            case 'must be numeric':
                return 'deve ser numérico';
            case 'must be greater than or equal to 100':
                return 'deve ser maior ou igual a 100';
            case 'must be between 1 and 24':
                return 'deve ser entre 1 e 24';
            case 'only ISO 3166-1 alpha-3 values are accepted':
                return 'deve ser um código ISO 3166-1 alpha-3';
            case 'either paymentMethod.card.id or paymentMethod.card.encrypted should be informed':
                return 'deve ser informado o cartão de crédito criptografado ou o id do cartão';
            case 'must be an integer number':
                return 'deve ser um número inteiro';
            case 'card holder name must contain a first and last name':
                return 'o nome do titular do cartão deve conter um primeiro e último nome';
            case 'must be a well-formed email address':
                return 'deve ser um endereço de e-mail válido';
            case 'must be a valid CPF or CNPJ':
                return 'deve ser um CPF ou CNPJ válido';
            case 'credit_card_bin data not found.':
                return 'cartão de crédito não reconhecido';
            default:
                return $description;
        }
    }

    /**
     * @param $parameterName
     * @return string
     */
    private function getFriendlyParameter($parameterName)
    {
        switch ($parameterName) {
            case 'amount.value':
                return 'valor do pedido';
            case 'customer.name':
                return 'nome do cliente';
            case 'customer.phones[0].number':
                return 'número de telefone do cliente';
            case 'customer.phones[0].area':
                return 'DDD do telefone do cliente';
            case 'billingAddress.complement':
                return 'complemento/bairro do endereço de cobrança';
            case 'paymentMethod.installments':
                return 'parcelas';
            case 'billingAddress.country':
                return 'país de cobrança';
            case 'paymentMethod.card':
                return 'cartão de crédito';
            case 'paymentMethod.card.encrypted':
                return 'cartão de crédito criptografado';
            case 'customer.email':
                return 'e-mail';
            case 'customer.tax_id':
                return 'documento (CPF/CNPJ)';
            case 'credit_card_bin':
                return 'número bin do cartão';
            default:
                return $parameterName;
        }
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