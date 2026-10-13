<?php

/**
 * Admin select for 3DS: when Vindi lacks credit_card_3ds, lock to No (Woo parity).
 */
class RicardoMartins_PagBank_Block_Adminhtml_System_Config_Form_Field_Cc3ds
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if ($detector->isVindi()) {
            $caps = new RicardoMartins_PagBank_Model_Partner_Capabilities($detector);
            if (!$caps->has3ds()) {
                $element->setValue(0);
                $element->setDisabled(true);
                $html = parent::_getElementHtml($element);
                // Disabled inputs are not posted — persist "No" on Save Config.
                $html .= '<input type="hidden" name="' . $element->getName() . '" value="0" />';
                return $html;
            }
        }

        return parent::_getElementHtml($element);
    }
}
