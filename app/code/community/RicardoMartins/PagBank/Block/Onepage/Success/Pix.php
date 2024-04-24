<?php

class RicardoMartins_PagBank_Block_Onepage_Success_Pix extends Mage_Checkout_Block_Onepage_Success
{
    /** @var mixed */
    private $_order;

    /**
     * @return bool
     */
    public function isVisible()
    {
        $order = $this->getCurrentOrder();
        if (!$order) {
            return false;
        }

        return $order->getPayment()->getMethod() === 'ricardomartins_pagbank_pix';
    }

    /**
     * @return null|string
     */
    public function getPaymentQrcodeImage()
    {
        return $this->getAdditionalData()['pix']['qrcode_image'];
    }

    /**
     * @return null|string
     */
    public function getPaymentTextPix()
    {
        return $this->getAdditionalData()['pix']['qrcode_text'];
    }

    /**
     * @return mixed|Zend_Date
     */
    public function getExpirationDate()
    {
        $date = $this->getAdditionalData()['pix']['due_date'];
        try {
            $date = new Zend_Date($date);
        } catch (Zend_Date_Exception $e) {}

        return $date;
    }

    /**
     * @return mixed
     */
    private function getAdditionalData()
    {
        return unserialize($this->getCurrentOrder()->getPayment()->getAdditionalData() ?: '');
    }

    /**
     * Get current order
     *
     * @return mixed
     */
    private function getCurrentOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->getOrder()) {
            $this->_order = $this->getOrder();
        } else {
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId(
                Mage::getSingleton('checkout/session')->getLastRealOrderId()
            );
        }

        return $this->_order;
    }
}