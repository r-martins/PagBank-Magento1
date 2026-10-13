<?php

/**
 * Dual-partner payment additional_data keys (Magento 1 analogue of Woo OrderMeta).
 *
 * Write: PagBank → pagbank_*; Vindi → pb_*
 * Read: preferred partner first, then the other (legacy).
 */
class RicardoMartins_PagBank_Helper_PaymentData extends Mage_Core_Helper_Abstract
{
    const BRAND_PAGBANK = 'pagbank';
    const BRAND_PB = 'pb';

    /**
     * @param string $key
     * @return string
     */
    public function toLogical($key)
    {
        if (strpos($key, 'pagbank_') === 0) {
            return substr($key, strlen('pagbank_'));
        }
        if (strpos($key, 'pb_') === 0) {
            return substr($key, strlen('pb_'));
        }
        return $key;
    }

    /**
     * @param string $partner
     * @return string
     */
    public function brandForPartner($partner)
    {
        return $partner === RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK
            ? self::BRAND_PAGBANK
            : self::BRAND_PB;
    }

    /**
     * @param string $logicalOrPhysical
     * @param string $partner
     * @return string
     */
    public function physicalKey($logicalOrPhysical, $partner)
    {
        $logical = $this->toLogical($logicalOrPhysical);
        $brand = $this->brandForPartner($partner);
        return $brand . '_' . $logical;
    }

    /**
     * @param string $logicalOrPhysical
     * @param string|null $preferredPartner
     * @return string[]
     */
    public function candidateKeys($logicalOrPhysical, $preferredPartner = null)
    {
        $logical = $this->toLogical($logicalOrPhysical);
        $pagbank = $this->physicalKey($logical, RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK);
        $pb = $this->physicalKey($logical, RicardoMartins_PagBank_Model_Partner_Partner::VINDI);

        if ($preferredPartner === RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK) {
            return array($pagbank, $pb);
        }
        if ($preferredPartner !== null && $preferredPartner !== '') {
            return array($pb, $pagbank);
        }
        return array($pb, $pagbank);
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return array
     */
    public function getAdditionalDataArray($payment)
    {
        $raw = $payment->getAdditionalData();
        if (!$raw) {
            return array();
        }
        $data = @unserialize($raw);
        return is_array($data) ? $data : array();
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param array $data
     * @return void
     */
    public function saveAdditionalDataArray($payment, array $data)
    {
        $payment->setAdditionalData(serialize($data));
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return string
     */
    public function readPartner($payment)
    {
        $data = $this->getAdditionalDataArray($payment);
        foreach (array('pb_partner', 'pagbank_partner', 'partner') as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return $data[$key] === RicardoMartins_PagBank_Model_Partner_Partner::VINDI
                    ? RicardoMartins_PagBank_Model_Partner_Partner::VINDI
                    : RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK;
            }
        }
        return '';
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string|null $explicit
     * @return string
     */
    public function resolveWritePartner($payment, $explicit = null)
    {
        if ($explicit === RicardoMartins_PagBank_Model_Partner_Partner::VINDI
            || $explicit === RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK
        ) {
            return $explicit;
        }
        $fromPayment = $this->readPartner($payment);
        if ($fromPayment !== '') {
            return $fromPayment;
        }
        return RicardoMartins_PagBank_Model_Partner_Detector::fromSettings()->getPartner();
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $logicalOrPhysical
     * @param mixed $default
     * @return mixed
     */
    public function get($payment, $logicalOrPhysical, $default = '')
    {
        $data = $this->getAdditionalDataArray($payment);
        $logical = $this->toLogical($logicalOrPhysical);
        $partner = $this->readPartner($payment);
        $preferred = $partner !== '' ? $partner : null;

        // Also accept legacy flat keys without brand (e.g. charge_id, is_sandbox)
        if (array_key_exists($logicalOrPhysical, $data) && $data[$logicalOrPhysical] !== '' && $data[$logicalOrPhysical] !== null) {
            return $data[$logicalOrPhysical];
        }
        if (array_key_exists($logical, $data) && $data[$logical] !== '' && $data[$logical] !== null) {
            return $data[$logical];
        }

        foreach ($this->candidateKeys($logical, $preferred) as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== '' && $data[$key] !== null) {
                return $data[$key];
            }
        }

        return $default;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $logicalOrPhysical
     * @param mixed $value
     * @param string|null $partner
     * @return array Updated additional_data array
     */
    public function set($payment, $logicalOrPhysical, $value, $partner = null)
    {
        $data = $this->getAdditionalDataArray($payment);
        $writePartner = $this->resolveWritePartner($payment, $partner);
        $key = $this->physicalKey($logicalOrPhysical, $writePartner);
        $data[$key] = $value;

        // Keep legacy unprefixed aliases for keys historically used without brand
        // so admin/info blocks keep working during transition.
        $logical = $this->toLogical($logicalOrPhysical);
        if (in_array($logical, array('order_id', 'charge_id', 'is_sandbox'), true)) {
            if ($logical === 'order_id') {
                $data['pagbank_order_id'] = $value;
            } else {
                $data[$logical] = $value;
            }
        }

        $this->saveAdditionalDataArray($payment, $data);
        return $data;
    }

    /**
     * Stamp partner + fingerprint on new payments.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param int|null $storeId
     * @return void
     */
    public function stampPartner($payment, $storeId = null)
    {
        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings($storeId);
        $partner = $detector->getPartner();
        $data = $this->getAdditionalDataArray($payment);
        $data[$this->physicalKey('partner', $partner)] = $partner;
        $data['partner'] = $partner;
        $fp = $detector->fingerprint();
        if ($fp !== '') {
            $data[$this->physicalKey('connect_key_fp', $partner)] = $fp;
        }
        $this->saveAdditionalDataArray($payment, $data);
    }
}
