<?php

class RicardoMartins_PagBank_Model_Request_Builder_Order
{
    /**
     * The unique order identifier.
     * Receives the order increment id string.
     */
    const REFERENCE_ID = 'reference_id';

    /**
     * The notification urls.
     * Receives an array of strings.
     */
    const NOTIFICATION_URLS = 'notification_urls';

    /**
     * @var Mage_Sales_Model_Order $order
     */
    protected $order;

    /**
     * @param $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function build()
    {
        $orderIncrementId = $this->getIncrementId();
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $enableProxy = $helper->isProxyEnabled($this->order->getStoreId());
        
        return [
            self::REFERENCE_ID => $orderIncrementId,
            self::NOTIFICATION_URLS => $this->getNotificationUrls(),
            'enable_proxy' => $enableProxy
        ];
    }

    /**
     * @return string[]
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    private function getNotificationUrls()
    {
        $orderIncrementId = $this->getIncrementId();
        $hash = Mage::helper('core')->getHash($orderIncrementId);
        $hash = substr($hash, 0, 5);
        $baseUrl = Mage::app()->getStore()->getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_LINK, true);
        return [
            $baseUrl . RicardoMartins_PagBank_Api_Connect_ConnectInterface::NOTIFICATION_ENDPOINT . '?hash=' . $hash
        ];
    }
    
    /**
    * Returns the order increment ID or reserved order ID.
    * @return string|integer
    */
    private function getIncrementId()
    {
        $quote = $this->order->getQuote();
        if (empty($quote)) {
            return $this->order->getReservedOrderId();
        }
        return $this->order->getIncrementId() ?: $quote->getReservedOrderId();
    }
}
