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
        return [
            self::REFERENCE_ID => $this->order->getIncrementId(),
            self::NOTIFICATION_URLS => $this->getNotificationUrls()
        ];
    }

    /**
     * @return string[]
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    private function getNotificationUrls()
    {
        $hash = Mage::helper('core')->getHash($this->order->getIncrementId(), Mage_Admin_Model_User::HASH_SALT_LENGTH);
        $hash = substr($hash, 0, 5);
        $baseUrl = Mage::app()->getStore()->getBaseUrl();
        return [
            $baseUrl . RicardoMartins_PagBank_Api_Connect_ConnectInterface::NOTIFICATION_ENDPOINT . '?hash=' . $hash
        ];
    }
}
