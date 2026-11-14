<?php

class RicardoMartins_PagBank_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    /**
     * Set block template
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/cc.phtml');
    }

    /**
     * Insert module's javascript on rendering, only if it wasn't inserted before
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $head = Mage::app()->getLayout()->getBlock('after_body_start');

        if (!$head) {
            return parent::_prepareLayout();
        }

        if (!$head->getChild('ricardomartins.pagbank.style')) {
            $scriptBlock = $helper->getPagBankStyleBlock();
            $head->append($scriptBlock);
        }

        if (!$head->getChild('ricardomartins.pagbank.js')) {
            $scriptBlock = $helper->getPagBankScriptBlock();
            $head->append($scriptBlock);
        }

        if (!$head->getChild('ricardomartins.pagbank.cc.js')) {
            $scriptCcBlock = $helper->getPagBankScriptCcBlock();
            $head->append($scriptCcBlock);
        }

        return parent::_prepareLayout();
    }

    /**
     * @return bool
     */
    public function isDocumentFieldVisible()
    {
        $documentFrom = Mage::getStoreConfig('payment/ricardomartins_pagbank/document_from');
        if ($documentFrom == 'payment_form') {
            return true;
        }
        return false;
    }

    /**
     * @return mixed|string
     */
    public function getPagSeguroConnect3dSession()
    {
        $session = '';

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        // Vindi 3DS uses MPI proxies, not PagBank checkout-sdk sessions.
        if ($helper->isVindi() || !$helper->isCc3dsEnabled()) {
            return $session;
        }

        try {
            /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
            $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');
            $endpoint = $helper->get3DSecureSessionEndpoint();
            $result = $api->placePostRequest($endpoint);
            $session = isset($result['session']) ? $result['session'] : '';
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $session;
    }

    /**
     * Pre-calculated installment plans for the payment form HTML (generic BIN).
     * OSC often re-renders the form; options must already be in the markup, not only via JS.
     * Uses Mage cache (same pattern as connectInfo/tokenize) — not checkout session.
     *
     * @return array
     */
    public function getInstallmentPlans()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper('checkout/cart')->getQuote();
        $storeId = $quote->getStoreId();
        $totalCents = (int) round(((float) $quote->getGrandTotal()) * 100);

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        // Generic Mastercard BIN for preview; PagBank sandbox still needs its fixed BIN.
        $bin = '555555';
        if ($helper->isSandbox($storeId) && !$helper->isVindi($storeId)) {
            $bin = $helper->getSandboxCcBin();
        }

        $detector = $helper->getPartnerDetector($storeId);
        $cacheKey = 'pagbank_cc_installments_' . $detector->fingerprint() . '_' . $totalCents . '_' . $bin;
        $cache = Mage::app()->getCache();
        $cached = $cache->load($cacheKey);
        // Reject "", "[]", "null", false and other non-usable payloads — never treat API failures as cache hits.
        if (is_string($cached) && $cached !== '') {
            $decoded = json_decode($cached, true);
            if ($this->isValidInstallmentPlans($decoded)) {
                return $decoded;
            }
            $cache->remove($cacheKey);
        }

        try {
            /** @var RicardoMartins_PagBank_Model_Request_Builder_Installments $installmentsBuilder */
            $installmentsBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_installments', $bin);
            $params = $installmentsBuilder->build();

            /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
            $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');
            $endpoint = $helper->getInterestEndpoint($storeId);
            $response = $api->placeGetRequest($endpoint, $params, false);

            if (!isset($response['payment_methods']['credit_card']) || !is_array($response['payment_methods']['credit_card'])) {
                return array();
            }
            $creditCard = reset($response['payment_methods']['credit_card']);
            if (!is_array($creditCard) || empty($creditCard['installment_plans']) || !is_array($creditCard['installment_plans'])) {
                return array();
            }

            $plans = $creditCard['installment_plans'];
            if (!$this->isValidInstallmentPlans($plans)) {
                return array();
            }

            $payload = json_encode($plans);
            // Only persist a real JSON array of plans — never "", false, or error placeholders.
            if (is_string($payload) && $payload !== '' && $payload !== '[]' && $payload !== 'null') {
                $cache->save($payload, $cacheKey, array('ricardomartins_pagbank'), 600);
            }

            return $plans;
        } catch (Exception $e) {
            $helper->writeLog('Cc form installment preview failed: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * @param mixed $plans
     * @return bool
     */
    protected function isValidInstallmentPlans($plans)
    {
        if (!is_array($plans) || empty($plans)) {
            return false;
        }
        foreach ($plans as $plan) {
            if (!is_array($plan) || !isset($plan['installments'], $plan['installment_value'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Label matching creditcard.js installment option text.
     *
     * @param array $plan
     * @return string
     */
    public function formatInstallmentOptionLabel($plan)
    {
        if (!is_array($plan)) {
            return '';
        }

        $n = isset($plan['installments']) ? (int) $plan['installments'] : 1;
        $installmentCents = isset($plan['installment_value']) ? (int) $plan['installment_value'] : 0;
        $installmentAmount = number_format($installmentCents / 100, 2, ',', '.');
        $text = $n . 'x de R$ ' . $installmentAmount;

        $interestFree = !empty($plan['interest_free']);
        if ($interestFree) {
            return $text . ' (sem juros)';
        }

        $totalCents = 0;
        if (isset($plan['amount']['value'])) {
            $totalCents = (int) $plan['amount']['value'];
        }
        $totalAmount = number_format($totalCents / 100, 2, ',', '.');

        return $text . ' (Total R$ ' . $totalAmount . ')';
    }
}
