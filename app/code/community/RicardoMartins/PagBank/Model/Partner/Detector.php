<?php

/**
 * Detects payment partner from Connect Key prefix.
 *
 * Vindi keys (new): fixed length 40 for prod and sandbox (same as PagBank CON… keys).
 * Legacy shorter CONVD… keys remain valid.
 */
class RicardoMartins_PagBank_Model_Partner_Detector
{
    /** @var string */
    private $partner;

    /** @var bool */
    private $sandbox;

    /** @var string|null */
    private $templateCode;

    /** @var string */
    private $connectKey;

    /**
     * @param string $connectKey
     */
    public function __construct($connectKey = '')
    {
        $this->connectKey = trim((string) $connectKey);
        $this->sandbox = false;
        $this->templateCode = null;
        $this->partner = RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK;
        $this->parse();
    }

    /**
     * @param string $connectKey
     * @return RicardoMartins_PagBank_Model_Partner_Detector
     */
    public static function fromKey($connectKey)
    {
        return new self($connectKey);
    }

    /**
     * @param int|null $storeId
     * @return RicardoMartins_PagBank_Model_Partner_Detector
     */
    public static function fromSettings($storeId = null)
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        return new self($helper->getConnectKey($storeId));
    }

    private function parse()
    {
        $key = strtoupper($this->connectKey);
        if ($key === '') {
            return;
        }

        if (strpos($key, 'CONVDSANDBOX') === 0 || strpos($key, 'CONVD') === 0) {
            $this->partner = RicardoMartins_PagBank_Model_Partner_Partner::VINDI;
            $this->sandbox = strpos($key, 'CONVDSANDBOX') === 0;
            if (preg_match('/^CONVD(?:SANDBOX)?(30|14|FLX)/', $key, $m)) {
                $map = array('30' => '30dias', '14' => '14dias', 'FLX' => 'flex');
                $this->templateCode = isset($map[$m[1]]) ? $map[$m[1]] : null;
            }
            return;
        }

        $this->partner = RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK;
        $this->sandbox = strpos($key, 'CONSANDBOX') === 0;
    }

    /**
     * @return string
     */
    public function getPartner()
    {
        return $this->partner;
    }

    /**
     * @return bool
     */
    public function isVindi()
    {
        return $this->partner === RicardoMartins_PagBank_Model_Partner_Partner::VINDI;
    }

    /**
     * @return bool
     */
    public function isPagBank()
    {
        return $this->partner === RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK;
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return $this->sandbox;
    }

    /**
     * @return string|null
     */
    public function getTemplateCode()
    {
        return $this->templateCode;
    }

    /**
     * @return string
     */
    public function getConnectKey()
    {
        return $this->connectKey;
    }

    /**
     * Last 4 chars for fingerprint / notices (never log full key).
     *
     * @return string
     */
    public function fingerprint()
    {
        if (strlen($this->connectKey) < 4) {
            return '';
        }
        return substr($this->connectKey, -4);
    }
}
