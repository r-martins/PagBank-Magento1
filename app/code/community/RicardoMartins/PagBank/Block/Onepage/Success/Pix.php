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
        $order = $this->getCurrentOrder();
        $remote = isset($this->getAdditionalData()['pix']['qrcode_image'])
            ? (string) $this->getAdditionalData()['pix']['qrcode_image']
            : '';
        if ($remote === '') {
            return null;
        }

        /** @var RicardoMartins_PagBank_Model_Pix_QrImage $qrImage */
        $qrImage = Mage::getModel('ricardomartins_pagbank/pix_qrImage');
        return $qrImage->displayUrl($order, $remote);
    }

    /**
     * @return null|string
     */
    public function getPaymentTextPix()
    {
        return $this->getAdditionalData()['pix']['qrcode_text'];
    }

    /**
     * Returns expiration date as Unix timestamp for use with block's formatDate().
     *
     * @return int|null
     */
    public function getExpirationDate()
    {
        $date = $this->getAdditionalData()['pix']['due_date'];
        try {
            $date = (new DateTime($date))->getTimestamp();
        } catch (Exception $e) {
            $date = null;
        }

        return $date;
    }

    /**
     * Increment ID for PIX status polling on the success page (PB-1083).
     *
     * @return string|null
     */
    public function getOrderIncrementId()
    {
        $order = $this->getCurrentOrder();
        return $order ? $order->getIncrementId() : null;
    }

    /**
     * AJAX URL to poll Magento order status after PIX payment (PB-1083).
     *
     * @return string
     */
    public function getOrderStatusUrl()
    {
        return $this->getUrl('pagbank/ajax/orderStatus', array('_secure' => true));
    }

    /**
     * Whether the order is already paid (e.g. webhook arrived before page render).
     *
     * @return bool
     */
    public function isPaymentConfirmed()
    {
        $order = $this->getCurrentOrder();
        if (!$order) {
            return false;
        }
        return in_array($order->getState(), array(
            Mage_Sales_Model_Order::STATE_PROCESSING,
            Mage_Sales_Model_Order::STATE_COMPLETE,
        ), true);
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