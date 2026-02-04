<?php

class RicardoMartins_PagBank_Block_Onepage_Success_Billet extends Mage_Checkout_Block_Onepage_Success
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

        return $order->getPayment()->getMethod() === 'ricardomartins_pagbank_billet';
    }

    /**
     * @return null|string
     */
    public function getPaymentBarcode()
    {
        return $this->getAdditionalData()['billet']['barcode'];
    }

    /**
     * @return null|string
     */
    public function getPaymentLinkBilletPdf()
    {
        return $this->getAdditionalData()['billet']['payment_link_boleto_pdf'];
    }

    /**
     * @return null|string
     */
    public function getPaymentLinkBilletImage()
    {
        return $this->getAdditionalData()['billet']['payment_link_boleto_image'];
    }

    /**
     * Returns expiration date as Unix timestamp for use with block's formatDate().
     *
     * @return int|null
     */
    public function getExpirationDate()
    {
        $date = $this->getAdditionalData()['billet']['due_date'];
        try {
            $date = (new DateTime($date))->getTimestamp();
        } catch (Exception $e) {
            $date = null;
        }

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