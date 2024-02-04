<?php

class RicardoMartins_PagBank_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/cc.phtml');
    }

    /**
     * @return bool
     */
    public function isDocumentFieldVisible()
    {
        $documentFrom = Mage::getStoreConfig('payment/ricardomartins_pagbank/document_from');
        if ($documentFrom == 'payment_form') {
            return true;
        }
        return false;
    }
}
