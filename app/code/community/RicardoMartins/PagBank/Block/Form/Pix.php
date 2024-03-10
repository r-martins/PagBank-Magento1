<?php

class RicardoMartins_PagBank_Block_Form_Pix extends Mage_Payment_Block_Form
{
    /**
     * Set block template
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/pix.phtml');
    }

    /**
     * Insert module's javascript on rendering, only if it wasn't inserted before
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $head = Mage::app()->getLayout()->getBlock('after_body_start');

        if (!$head) {
            return parent::_prepareLayout();
        }

        if (!$head->getChild('ricardomartins.pagbank.style')) {
            $scriptBlock = $helper->getPagBankStyleBlock();
            $head->append($scriptBlock);
        }

        if (!$head->getChild('ricardomartins.pagbank.js')) {
            $scriptBlock = $helper->getPagBankScriptBlock();
            $head->append($scriptBlock);
        }

        return parent::_prepareLayout();
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
        return Mage::getStoreConfig('payment/ricardomartins_pagbank_pix/expiration_time') ?: 60;
    }
}
