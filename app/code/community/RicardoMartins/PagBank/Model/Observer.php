<?php

// Maho/OpenMage/Magento compatibility: alias Varien_Object when missing (Maho uses Maho\DataObject)
if (!class_exists('Varien_Object', false) && class_exists('Maho\DataObject')) {
    class_alias('Maho\DataObject', 'Varien_Object');
}

class RicardoMartins_PagBank_Model_Observer
{
    /**
     * Generate public key on save connect key
     *
     * @param Varien_Event_Observer|Maho\Event\Observer $observer
     * @return void
     * @throws Exception
     */
    public function generatePublicKey($observer)
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $connectKey = $helper->getConnectKey();
        if (!$connectKey) {
            return;
        }

        if (preg_match('/^PUB[\w]{0,39}$/', $connectKey)) {
            $link = '<a target="_blank" href="https://pbintegracoes.com/autorizar/?utm_source=magentoadmin">obtenha uma Connect Key</a>';
            $msg = sprintf(
                Mage::helper('ricardomartins_pagbank')->__(
                    'It looks like you entered a Public Key. To use the next generation of our integrations, %s for free.'
                ),
                $link
            );

            $helper->writeLog($msg);
            Mage::throwException($msg);
        }

        $detector = $helper->getPartnerDetector();
        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        try {
            if ($detector->isVindi()) {
                // Vindi browser tokenize uses public_api_key from tokenize/config (not PagBank RSA).
                $response = $api->placeGetRequest($helper->getTokenizeConfigEndpoint(), array(), false);
                $parsed = $helper->parseTokenizeConfigResponse($response);
                if ($parsed['public_key'] === '') {
                    throw new Exception('tokenize/config sem public_api_key');
                }
                Mage::getConfig()->saveConfig(
                    RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::PUBLIC_KEY_CONFIG_PATH,
                    $parsed['public_key']
                );
                Mage::app()->getCache()->clean('matchingTag', array('ricardomartins_pagbank'));
                return;
            }

            $endpoint = $helper->getPublicKeyEndpoint();
            $body = array(
                RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::TYPE => RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::TYPE_CARD
            );
            $response = $api->placePostRequest($endpoint, $body);
            $publicKey = $response[RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::PUBLIC_KEY];
            Mage::getConfig()->saveConfig(
                RicardoMartins_PagBank_Api_Connect_PublicKeyInterface::PUBLIC_KEY_CONFIG_PATH,
                $publicKey
            );
            Mage::app()->getCache()->clean('matchingTag', array('ricardomartins_pagbank'));
        } catch (\Exception $e) {
            $helper->writeLog(sprintf('Error validating Connect Key / public key: %s', $e->getMessage()));
            if ($helper->isVindi()) {
                throw new \Exception(
                    $helper->__('Error validating Vindi Connect Key (tokenize/config): %s', $e->getMessage())
                );
            }
            throw new \Exception($helper->__('Error generating public key: %s', $e->getMessage()));
        }
    }

    /**
     * Assign additional data to specific information payment info block
     *
     * @param $observer
     * @return $this
     */
    public function paymentInfoBlockPrepareSpecificInformation($observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $transport = $observer->getEvent()->getTransport();
        $helper = Mage::helper('ricardomartins_pagbank');

        $additionalData = unserialize($payment->getAdditionalData());
        if (!is_array($additionalData)) {
            $additionalData = [];
        }

        // Card brand / last4 / expiry are rendered as "Cartão" + "Expiração" in the info template.
        $info = [
            RicardoMartins_PagBank_Model_Method_Cc::CC_OWNER,
            RicardoMartins_PagBank_Model_Method_Cc::CC_INSTALLMENTS
        ];

        if (!$observer->getEvent()->getBlock()->getIsSecureMode()) {
            $info[] = RicardoMartins_PagBank_Model_Method_Abstract::ORDER_ID;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::CHARGE_ID;
            // CHARGE_LINK handled below with partner-aware "Ver na Vindi" / "Ver no PagBank"
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::AUTHORIZATION_CODE;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::NSU;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::CC_PAGBANK_SESSION;
            $info[] = RicardoMartins_PagBank_Model_Method_Cc::CC_3DS_ID;
        }

        $last4 = $payment->getAdditionalInformation(RicardoMartins_PagBank_Model_Method_Cc::CC_LAST_4)
            ?: (isset($additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_LAST_4])
                ? $additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_LAST_4] : '');
        $brand = $payment->getAdditionalInformation(RicardoMartins_PagBank_Model_Method_Cc::CC_BRAND)
            ?: (isset($additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_BRAND])
                ? $additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_BRAND] : '');
        $expMonth = $payment->getAdditionalInformation(RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_MONTH)
            ?: (isset($additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_MONTH])
                ? $additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_MONTH] : '');
        $expYear = $payment->getAdditionalInformation(RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_YEAR)
            ?: (isset($additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_YEAR])
                ? $additionalData[RicardoMartins_PagBank_Model_Method_Cc::CC_EXP_YEAR] : '');

        if ($last4 !== '' || $brand !== '') {
            // Marker for template: last4|brand (HTML built in block/template)
            $transport->setData(
                $helper->__('Cartão'),
                'CARD|' . $last4 . '|' . $brand
            );
        }
        if ($expMonth !== '' && $expYear !== '') {
            $transport->setData(
                $helper->__('Expiração'),
                sprintf('%02d/%04d', (int) $expMonth, (int) $expYear)
            );
        }

        foreach ($info as $key) {
            if ($value = $payment->getAdditionalInformation($key)) {
                $transport->setData($helper->getInfoLabels($key), $value);
            } elseif (isset($additionalData[$key])) {
                $transport->setData($helper->getInfoLabels($key), $additionalData[$key]);
            }
        }

        if (!$observer->getEvent()->getBlock()->getIsSecureMode()) {
            /** @var RicardoMartins_PagBank_Helper_PaymentData $paymentData */
            $paymentData = Mage::helper('ricardomartins_pagbank/paymentData');
            $chargeId = (string) $paymentData->get($payment, 'charge_id', '');
            if ($chargeId === '' && !empty($additionalData['charge_id'])) {
                $chargeId = (string) $additionalData['charge_id'];
            }
            $billId = '';
            if (!empty($additionalData['partner_bill_id'])) {
                $billId = (string) $additionalData['partner_bill_id'];
            }

            $partner = $paymentData->readPartner($payment);
            if ($partner === '') {
                $storedLink = !empty($additionalData[RicardoMartins_PagBank_Model_Method_Cc::CHARGE_LINK])
                    ? (string) $additionalData[RicardoMartins_PagBank_Model_Method_Cc::CHARGE_LINK]
                    : '';
                if ($storedLink !== '' && strpos($storedLink, 'vindi.com.br') !== false) {
                    $partner = RicardoMartins_PagBank_Model_Partner_Partner::VINDI;
                } elseif (strpos($chargeId, 'CHAR_') === 0) {
                    $partner = RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK;
                } else {
                    $partner = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings()->getPartner();
                }
            }
            $branding = RicardoMartins_PagBank_Model_Partner_Branding::forOrderPartner($partner);

            $isSandbox = $this->isPaymentSandbox($payment, $additionalData);
            $portalUrl = $branding->adminPortalUrl($billId, $chargeId, $isSandbox);
            if (!$portalUrl && !empty($additionalData[RicardoMartins_PagBank_Model_Method_Cc::CHARGE_LINK])) {
                $portalUrl = (string) $additionalData[RicardoMartins_PagBank_Model_Method_Cc::CHARGE_LINK];
            }
            if ($portalUrl) {
                $transport->setData($helper->__($branding->viewChargeLabel()), $portalUrl);
            }
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param array $additionalData
     * @return bool
     */
    private function isPaymentSandbox($payment, array $additionalData)
    {
        /** @var RicardoMartins_PagBank_Helper_PaymentData $paymentData */
        $paymentData = Mage::helper('ricardomartins_pagbank/paymentData');
        $flag = $paymentData->get($payment, 'is_sandbox', '');
        if ($flag === '' && isset($additionalData[RicardoMartins_PagBank_Model_Method_Abstract::IS_SANDBOX])) {
            $flag = $additionalData[RicardoMartins_PagBank_Model_Method_Abstract::IS_SANDBOX];
        }
        if ($flag === '' && !empty($additionalData['pix'][RicardoMartins_PagBank_Model_Method_Abstract::IS_SANDBOX])) {
            $flag = $additionalData['pix'][RicardoMartins_PagBank_Model_Method_Abstract::IS_SANDBOX];
        }
        if ($flag === '' && !empty($additionalData['billet'][RicardoMartins_PagBank_Model_Method_Abstract::IS_SANDBOX])) {
            $flag = $additionalData['billet'][RicardoMartins_PagBank_Model_Method_Abstract::IS_SANDBOX];
        }

        return $flag === 'Yes' || $flag === true || $flag === 1 || $flag === '1';
    }

    /**
     * @param $observer
     * @return $this
     * @throws Throwable
     */
    public function salesOrderPaymentPlaceEnd($observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        $method = $payment->getMethodInstance();

        if ($method instanceof RicardoMartins_PagBank_Model_Method_Cc) {
            try {
                $additionalData = unserialize($payment->getAdditionalData());
                $statusPagbank = $additionalData['status_pagbank'];
                if ($statusPagbank) {
                    $method->handleNotification($order, $statusPagbank);
                }
            } catch (\Exception $e) {
                $helper = Mage::helper('ricardomartins_pagbank');
                $helper->writeLog(sprintf('Error handling PagBank return: %s', $e->getMessage()));
            }
        }

        if ($method instanceof RicardoMartins_PagBank_Model_Method_Pix || $method instanceof RicardoMartins_PagBank_Model_Method_Billet) {
            try {
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
            } catch (\Exception $e) {
                $helper = Mage::helper('ricardomartins_pagbank');
                $helper->writeLog(sprintf('Error handling PagBank return: %s', $e->getMessage()));
            }
        }

        return $this;
    }

    /**
     * @param $observer
     */
    public function salesModelServiceQuoteSubmitFailure($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $payment = $quote->getPayment();
        if (!$payment) {
            return;
        }

        $methodCode = $payment->getMethod();
        if ($methodCode == RicardoMartins_PagBank_Model_Method_Cc::METHOD_CODE) {
            $payment->unsAdditionalInformation();
        }
    }

    /**
     * @param Varien_Event_Observer|Maho\Event\Observer $observer
     * @return void
     */
    public function addMoipCompatibility($observer)
    {
        $layout = $observer->getEvent()->getLayout();

        if (!$layout) {
            return;
        }

        $moipConfig = Mage::getConfig()->getModuleConfig('MOIP_Transparente');
        $isMoipEnabled = $moipConfig && $moipConfig->is('active', 'true');

        if (!$isMoipEnabled) {
            return;
        }

        $update = $layout->getUpdate();
        $handles = $update->getHandles();

        if (!in_array('checkout_onepage_index', $handles)) {
            return;
        }

        $update->addHandle('ricardomartins_pagbank_moip');
    }

    /**
     * Capture taxvat directly from estimateBilling POST request
     * This intercepts the data BEFORE it's processed by estimateBillingAction
     * Only active when document_from is set to 'billing_taxvat'
     * 
     * @param Varien_Event_Observer|Maho\Event\Observer $observer
     * @return void
     */
    public function captureTaxvatFromEstimateBillingRequest($observer)
    {
        // Only process if billing_taxvat option is selected
        $documentFrom = Mage::getStoreConfig('payment/ricardomartins_pagbank/document_from');
        if ($documentFrom !== 'billing_taxvat') {
            return;
        }
        
        $controller = $observer->getEvent()->getControllerAction();
        if (!$controller || !$controller->getRequest()->isPost()) {
            return;
        }

        $billingData = $controller->getRequest()->getPost('billing', []);
        if (empty($billingData)) {
            return;
        }

        // Extract taxvat from POST data
        $taxvat = null;
        if (isset($billingData['taxvat']) && !empty($billingData['taxvat'])) {
            $taxvat = $billingData['taxvat'];
        } elseif (isset($billingData['vat_id']) && !empty($billingData['vat_id'])) {
            $taxvat = $billingData['vat_id'];
        }

        if (!$taxvat) {
            return;
        }

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $taxvat = $helper->normalizeDocument($taxvat);

        // Get quote and ensure payment exists
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote || !$quote->getId()) {
            return;
        }

        // Ensure payment exists
        $payment = $quote->getPayment();
        if (!$payment) {
            $payment = Mage::getModel('sales/quote_payment');
            $payment->setQuote($quote);
            $quote->setPayment($payment);
        }

        // Save taxvat to payment additional information
        // This is where getDocumentValue() will retrieve it from when billing_taxvat is selected
        $payment->setAdditionalInformation('tax_id', $taxvat);
        $payment->setAdditionalInformation('taxvat', $taxvat);
        
        // Also save directly to additional_data field as backup (serialized)
        $additionalData = $payment->getAdditionalData();
        if (empty($additionalData)) {
            $additionalData = array();
        } else {
            $additionalData = unserialize($additionalData);
            if (!is_array($additionalData)) {
                $additionalData = array();
            }
        }
        $additionalData['tax_id'] = $taxvat;
        $additionalData['taxvat'] = $taxvat;
        $payment->setAdditionalData(serialize($additionalData));
        
        $payment->save();
    }

    /**
     * Admin CTA for PagBank → Vindi migration (display only — never hard-blocks).
     *
     * @param Varien_Event_Observer|Maho\Event\Observer $observer
     * @return void
     */
    public function addMigrationNotice($observer)
    {
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return;
        }

        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if (!$detector->isPagBank() || $detector->getConnectKey() === '') {
            return;
        }

        $session = Mage::getSingleton('adminhtml/session');
        $flag = 'ricardomartins_pagbank_migration_notice';
        if ($session->getData($flag)) {
            return;
        }
        $session->setData($flag, true);

        $url = RicardoMartins_PagBank_Model_Partner_Cutoff::MIGRATE_URL;
        $past = RicardoMartins_PagBank_Model_Partner_Cutoff::isPast();
        if ($past) {
            $msg = 'PagBank — Ação necessária: o prazo para migrar sua Connect Key já passou. '
                . 'Migre agora para continuar vendendo com melhores taxas. '
                . '<a href="' . $url . '" target="_blank" rel="noopener">Migrar agora</a>';
            $session->addError($msg);
        } else {
            $days = (int) floor(RicardoMartins_PagBank_Model_Partner_Cutoff::secondsRemaining() / 86400);
            $msg = 'PagBank — Você tem cerca de ' . $days . ' dias para migrar sua Connect Key '
                . '(prazo ' . RicardoMartins_PagBank_Model_Partner_Cutoff::SHORT . '). '
                . '<a href="' . $url . '" target="_blank" rel="noopener">Migrar agora</a>';
            $session->addNotice($msg);
        }
    }
}