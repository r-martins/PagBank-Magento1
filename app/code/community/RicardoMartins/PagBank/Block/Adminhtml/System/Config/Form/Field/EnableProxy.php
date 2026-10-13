<?php

/**
 * Hides "Rota Alternativa" when Connect Key is Vindi (notifications already use API v1 forwarder).
 */
class RicardoMartins_PagBank_Block_Adminhtml_System_Config_Form_Field_EnableProxy
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if ($detector->isVindi()) {
            return '';
        }

        return parent::render($element);
    }
}
