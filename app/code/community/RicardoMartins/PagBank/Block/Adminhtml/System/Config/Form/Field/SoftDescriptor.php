<?php

/**
 * Soft descriptor: PagBank editable field; Vindi → notice pointing to Vindi admin (Woo parity).
 */
class RicardoMartins_PagBank_Block_Adminhtml_System_Config_Form_Field_SoftDescriptor
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if (!$detector->isVindi()) {
            return parent::render($element);
        }

        $note = RicardoMartins_PagBank_Model_System_Config_VindiAdminNotes::softDescriptorNote();

        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '<td class="label"><label for="' . $element->getHtmlId() . '">'
            . $this->escapeHtml($element->getLabel()) . '</label></td>';
        $html .= '<td class="value"><p class="note" style="margin:0;">' . $note . '</p></td>';
        $html .= '<td class="scope-label">'
            . ($element->getScope() ? '[' . strtoupper($element->getScope()) . ']' : '')
            . '</td>';
        $html .= '<td></td></tr>';

        return $html;
    }
}
