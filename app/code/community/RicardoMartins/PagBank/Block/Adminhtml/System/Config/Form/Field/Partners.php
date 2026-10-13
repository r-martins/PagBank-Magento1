<?php

/**
 * Admin header: PB Integrações logo (→ pbintegracoes.com) and Connect Key CTAs.
 */
class RicardoMartins_PagBank_Block_Adminhtml_System_Config_Form_Field_Partners
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        $element->setCanUseStoreValue(false);

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $html = '<tr id="row_' . $element->getHtmlId() . '"><td colspan="4" style="padding:4px 0 12px;">';
        $html .= $helper->getAdminPartnersHeaderHtml();
        $html .= $this->getConnectKeyButtonsHtml();
        $html .= '</td></tr>';
        return $html;
    }

    /**
     * @return string
     */
    protected function getConnectKeyButtonsHtml()
    {
        $btn = 'display:inline-block;background:#f8f9fa;color:#495057;padding:8px 16px;'
            . 'text-decoration:none;border:1px solid #dee2e6;border-radius:3px;font-weight:500;margin:0 8px 8px 0;';

        return '<p style="margin:0 0 10px;">Para usar esta integração você precisa obter uma Connect Key:</p>'
            . '<a href="https://pbintegracoes.com/autorizar/?utm_source=magentoadmin" target="_blank" rel="noopener" style="' . $btn . '">Obter Connect Key</a>'
            . '<a href="https://pbintegracoes.com/sandbox/?utm_source=magentoadmin" target="_blank" rel="noopener" style="' . $btn . '">Connect Key para Testes</a>'
            . '<a href="https://ajuda.pbintegracoes.com/hc/pt-br/?utm_source=magentoadmin" target="_blank" rel="noopener" style="color:#6c757d;text-decoration:none;font-size:13px;">Central de Ajuda</a>';
    }
}
