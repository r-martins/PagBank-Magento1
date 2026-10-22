<?php

/**
 * Display names by partner (settings key vs payment additional_data).
 */
class RicardoMartins_PagBank_Model_Partner_Branding
{
    /** @var RicardoMartins_PagBank_Model_Partner_Detector */
    private $detector;

    /**
     * @param RicardoMartins_PagBank_Model_Partner_Detector|null $detector
     */
    public function __construct($detector = null)
    {
        $this->detector = $detector
            ? $detector
            : RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
    }

    /**
     * @param string $partner
     * @return RicardoMartins_PagBank_Model_Partner_Branding
     */
    public static function forOrderPartner($partner)
    {
        $fake = ($partner === RicardoMartins_PagBank_Model_Partner_Partner::VINDI) ? 'CONVD' : 'CON';
        return new self(new RicardoMartins_PagBank_Model_Partner_Detector($fake));
    }

    /**
     * @param Mage_Sales_Model_Order_Payment|null $payment
     * @return string
     */
    public static function notePrefix($payment = null)
    {
        if ($payment) {
            /** @var RicardoMartins_PagBank_Helper_PaymentData $paymentData */
            $paymentData = Mage::helper('ricardomartins_pagbank/paymentData');
            $partner = $paymentData->readPartner($payment);
            if ($partner !== '') {
                return self::forOrderPartner($partner)->productName();
            }
        }
        return (new self())->productName();
    }

    /**
     * @return string
     */
    public function productName()
    {
        return $this->detector->isVindi() ? 'Vindi' : 'PagBank';
    }

    /**
     * @return string
     */
    public function methodTitlePrefix()
    {
        return $this->detector->isVindi() ? 'via Vindi' : 'via PagBank';
    }

    /**
     * @return string
     */
    public function softDescriptorDefault()
    {
        return $this->detector->isVindi() ? 'CompraViaVindi' : 'CompraViaPagBank';
    }

    /**
     * Label for connectInfo (generic — any partner).
     *
     * @return string
     */
    public function accountLabel()
    {
        return 'Conta Gateway';
    }

    /**
     * Admin payment-info label for opening the charge in the partner portal.
     * Translate via locale CSV ("View on PagBank" / "View on Vindi").
     *
     * @return string
     */
    public function viewChargeLabel()
    {
        return $this->detector->isVindi() ? 'View on Vindi' : 'View on PagBank';
    }

    /**
     * Vindi admin bill URL (fatura). Prefer this over charges for unpaid PIX/boleto.
     *
     * @param string $billId
     * @param bool $isSandbox
     * @return string|null
     */
    public function adminBillUrl($billId, $isSandbox)
    {
        $billId = trim((string) $billId);
        if ($billId === '' || !$this->detector->isVindi()) {
            return null;
        }

        $host = $isSandbox ? 'sandbox-app.vindi.com.br' : 'app.vindi.com.br';
        return 'https://' . $host . '/admin/bills/' . rawurlencode($billId);
    }

    /**
     * Admin portal URL for a charge, or null when not linkable (PagBank sandbox).
     *
     * @param string $chargeId
     * @param bool $isSandbox
     * @return string|null
     */
    public function adminChargeUrl($chargeId, $isSandbox)
    {
        $chargeId = trim((string) $chargeId);
        if ($chargeId === '') {
            return null;
        }

        if ($this->detector->isVindi()) {
            $host = $isSandbox ? 'sandbox-app.vindi.com.br' : 'app.vindi.com.br';
            return 'https://' . $host . '/admin/charges/' . rawurlencode($chargeId);
        }

        if ($isSandbox) {
            return null;
        }

        $transaction = str_replace('CHAR_', '', $chargeId);
        return RicardoMartins_PagBank_Api_Connect_ConnectInterface::PAGBANK_TRANSACTION_DETAILS_URL
            . '/' . rawurlencode($transaction);
    }

    /**
     * Prefer Vindi bill URL (works for unpaid PIX/boleto); charge as fallback / PagBank.
     *
     * @param string|null $billId
     * @param string|null $chargeId
     * @param bool $isSandbox
     * @return string|null
     */
    public function adminPortalUrl($billId, $chargeId, $isSandbox)
    {
        $billUrl = $this->adminBillUrl($billId, $isSandbox);
        if ($billUrl) {
            return $billUrl;
        }

        return $this->adminChargeUrl((string) $chargeId, $isSandbox);
    }
}
