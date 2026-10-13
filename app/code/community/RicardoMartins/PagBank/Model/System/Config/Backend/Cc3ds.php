<?php

/**
 * Blocks enabling cc_3ds when Vindi account lacks credit_card_3ds.
 */
class RicardoMartins_PagBank_Model_System_Config_Backend_Cc3ds
    extends Mage_Core_Model_Config_Data
{
    /**
     * @return $this
     */
    protected function _beforeSave()
    {
        if (!(int) $this->getValue()) {
            return parent::_beforeSave();
        }

        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if (!$detector->isVindi()) {
            return parent::_beforeSave();
        }

        $caps = new RicardoMartins_PagBank_Model_Partner_Capabilities($detector);
        if ($caps->has3ds()) {
            return parent::_beforeSave();
        }

        $this->setValue(0);
        $ticket = '<a href="'
            . RicardoMartins_PagBank_Model_System_Config_Cc3dsComment::VINDI_TICKET_URL
            . '" target="_blank" rel="noopener noreferrer">Abra um chamado</a>';
        Mage::getSingleton('adminhtml/session')->addNotice(
            '3D Secure não está ativo em sua conta Vindi. '
            . $ticket . ' para solicitar. A opção foi mantida desativada.'
        );

        return parent::_beforeSave();
    }
}
