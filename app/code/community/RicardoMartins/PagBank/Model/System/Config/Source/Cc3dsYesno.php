<?php

/**
 * Yes/No for cc_3ds. PagBank: full Yes/No. Vindi without credit_card_3ds: only No.
 */
class RicardoMartins_PagBank_Model_System_Config_Source_Cc3dsYesno
    extends Mage_Adminhtml_Model_System_Config_Source_Yesno
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();

        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if (!$detector->isVindi()) {
            return $options;
        }

        $caps = new RicardoMartins_PagBank_Model_Partner_Capabilities($detector);
        if ($caps->has3ds()) {
            return $options;
        }

        // Only "No" — 3DS not active on the Vindi account.
        return array_values(array_filter($options, static function ($option) {
            return isset($option['value']) && (string) $option['value'] === '0';
        }));
    }
}
