<?php

class RicardoMartins_PagBank_Block_Form_Billet extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/billet.phtml');
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

    /**
     * @return mixed
     */
    public function getExpiration()
    {
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_billet/expiration_time') ?: 3;
    }
}
