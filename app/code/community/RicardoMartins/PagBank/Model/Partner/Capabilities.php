<?php

/**
 * Feature flags by partner (avoids scattered if CONVD).
 */
class RicardoMartins_PagBank_Model_Partner_Capabilities
{
    /** @var RicardoMartins_PagBank_Model_Partner_Detector */
    private $detector;

    /** @var bool|null */
    private $threeDsAvailable;

    /**
     * @param RicardoMartins_PagBank_Model_Partner_Detector|null $detector
     */
    public function __construct($detector = null)
    {
        $this->detector = $detector
            ? $detector
            : RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        $this->threeDsAvailable = null;
    }

    /**
     * @return RicardoMartins_PagBank_Model_Partner_Detector
     */
    public function detector()
    {
        return $this->detector;
    }

    /**
     * @return bool
     */
    public function usesGatewayToken()
    {
        return $this->detector->isVindi();
    }

    /**
     * @return bool
     */
    public function usesEncryptedCard()
    {
        return $this->detector->isPagBank();
    }

    /**
     * PagBank: always can attempt 3DS via SDK.
     * Vindi: only if credit_card_3ds is active (cached).
     *
     * @return bool
     */
    public function has3ds()
    {
        if ($this->detector->isPagBank()) {
            return true;
        }
        if ($this->threeDsAvailable !== null) {
            return $this->threeDsAvailable;
        }
        $this->threeDsAvailable = $this->probeVindi3ds();
        return $this->threeDsAvailable;
    }

    /**
     * @return bool
     */
    private function probeVindi3ds()
    {
        $cacheKey = 'pagbank_vindi_3ds_' . $this->detector->fingerprint();
        $cache = Mage::app()->getCache();
        $cached = $cache->load($cacheKey);
        if ($cached === '1' || $cached === '0') {
            return $cached === '1';
        }

        try {
            /** @var RicardoMartins_PagBank_Helper_Data $helper */
            $helper = Mage::helper('ricardomartins_pagbank');
            /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
            $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');
            $endpoint = $helper->getPaymentMethodsEndpoint();
            $resp = $api->placeGetRequest($endpoint, array('query' => 'code:credit_card_3ds'), false);
            $available = !empty($resp['credit_card_3ds']['available']);
            $cache->save($available ? '1' : '0', $cacheKey, array('ricardomartins_pagbank'), 3600);
            return $available;
        } catch (Exception $e) {
            $cache->save('0', $cacheKey, array('ricardomartins_pagbank'), 900);
            return false;
        }
    }
}
