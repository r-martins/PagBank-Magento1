<?php

class RicardoMartins_PagBank_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    /**
     * Set block template
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/cc.phtml');
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

        if (!$head->getChild('ricardomartins.pagbank.js')) {
            $scriptBlock = $helper->getPagBankScriptBlock();
            $head->append($scriptBlock);
        }

        if (!$head->getChild('ricardomartins.pagbank.cc.js')) {
            $scriptCcBlock = $helper->getPagBankScriptCcBlock();
            $head->append($scriptCcBlock);
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
     * @return mixed|string
     */
    public function getPagSeguroConnect3dSession()
    {
        $session = '';

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        if (!$helper->isCc3dsEnabled()) {
            return $session;
        }

        try {
            /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
            $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');
            $endpoint = $helper->get3DSecureSessionEndpoint();
            $result = $api->placePostRequest($endpoint);
            $session = isset($result['session']) ? $result['session'] : '';
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $session;
    }
}
