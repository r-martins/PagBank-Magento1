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
