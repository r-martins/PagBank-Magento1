<?php

class RicardoMartins_PagBank_Block_Form_Info_Cc extends Mage_Payment_Block_Info
{
    private $hideSpecificInfo = [
        RicardoMartins_PagBank_Model_Method_Cc::CC_PAGBANK_SESSION
    ];

    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/info/cc.phtml');
    }

    /**
     * @return mixed
     */
    public function isSandbox()
    {
        $info = $this->getInfo();
        $order = $info->getOrder();
        if (!$order) {
            return null;
        }

        $payment = $order->getPayment();
        $additionalData = $payment->getAdditionalData();
        $additionalData = unserialize($additionalData);

        return isset($additionalData['is_sandbox']) ? $additionalData['is_sandbox'] : null;
    }

    /**
     * @return bool
     */
    public function showSecureIcon()
    {
        $data = parent::getSpecificInformation();
        $prefix3DS = '3DS_';
        $result = array_filter($data, function ($valor) use ($prefix3DS) {
            return strpos($valor, $prefix3DS) === 0;
        });

        if (count($result) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSpecificInformation()
    {
        $result = parent::getSpecificInformation();
        foreach ($this->hideSpecificInfo as $key) {
            unset($result[$key]);
        }

        return $result;
    }

    /**
     * Map brand name to credit-cards/*.svg slug (same map as Woo).
     *
     * @param string $brand
     * @return string
     */
    public function normalizeCcBrandSlug($brand)
    {
        $brand = strtolower(trim((string) $brand));
        if ($brand === '') {
            return '';
        }
        $map = array(
            'american_express' => 'amex',
            'americanexpress' => 'amex',
            'amex' => 'amex',
            'diners_club' => 'diners',
            'dinersclub' => 'diners',
            'dinnersclub' => 'diners',
            'diners' => 'diners',
            'mastercard' => 'mastercard',
            'master' => 'mastercard',
            'visa' => 'visa',
            'elo' => 'elo',
            'hipercard' => 'hipercard',
            'hiper' => 'hipercard',
            'jcb' => 'jcb',
            'aura' => 'aura',
        );
        if (isset($map[$brand])) {
            return $map[$brand];
        }
        return preg_replace('/[^a-z0-9_-]/', '', $brand);
    }

    /**
     * Skin URL for brand SVG, or empty if file missing.
     *
     * @param string $brand
     * @return string
     */
    public function getCcBrandImageUrl($brand)
    {
        $slug = $this->normalizeCcBrandSlug($brand);
        if ($slug === '') {
            return '';
        }
        $rel = 'images/ricardomartins/pagbank/credit-cards/' . $slug . '.svg';
        $paths = array(
            Mage::getBaseDir('skin') . '/adminhtml/base/default/' . $rel,
            Mage::getBaseDir('skin') . '/frontend/base/default/' . $rel,
        );
        foreach ($paths as $path) {
            if (is_file($path)) {
                return $this->getSkinUrl($rel);
            }
        }
        return '';
    }

    /**
     * Render "final 5557" + brand image or "(mastercard)".
     * Value format from observer: CARD|{last4}|{brand}
     *
     * @param string $markerValue
     * @return string HTML (already escaped text parts)
     */
    public function formatCardSummaryHtml($markerValue)
    {
        $markerValue = (string) $markerValue;
        if (strpos($markerValue, 'CARD|') !== 0) {
            return $this->escapeHtml($markerValue);
        }
        $parts = explode('|', $markerValue, 3);
        $last4 = isset($parts[1]) ? $parts[1] : '';
        $brand = isset($parts[2]) ? $parts[2] : '';

        $html = '';
        if ($last4 !== '') {
            $html .= $this->escapeHtml($this->__('final %s', $last4));
        }
        $img = $this->getCcBrandImageUrl($brand);
        if ($img !== '') {
            $html .= ' <img src="' . $this->escapeUrl($img) . '" alt="' . $this->escapeHtml($brand) . '"'
                . ' title="' . $this->escapeHtml($brand) . '"'
                . ' style="width:32px;height:auto;vertical-align:middle;margin-left:4px;"/>';
        } elseif ($brand !== '') {
            $html .= ' <span>(' . $this->escapeHtml($brand) . ')</span>';
        }
        return $html;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isCardSummaryMarker($value)
    {
        return is_string($value) && strpos($value, 'CARD|') === 0;
    }
}
