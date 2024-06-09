<?php

/**
 * Class Items
 */
class RicardoMartins_PagBank_Model_Request_Builder_Items
{
    /**
     * Contains the information of the items included in the order.
     * Receives an array of items.
     */
    const ITEMS = 'items';

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
     * {@inheritdoc}
     */
    public function build()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $storeId = $this->order->getStoreId();
        if ($helper->hideOrderItems($storeId)) {
            return [];
        }

        $orderItems = $this->order->getAllItems();

        $result = $items = [];

        foreach ($orderItems as $orderItem) {
            if ($orderItem->getParentItem()) {
                continue;
            }

            $price = $orderItem->getPrice();
            if ($price == 0) {
                continue;
            }

            /** @var RicardoMartins_PagBank_Model_Request_Object_Item $item */
            $item = Mage::getModel('ricardomartins_pagbank/request_object_item');
            $item->setReferenceId($orderItem->getSku());
            $item->setName($orderItem->getName());
            $item->setQuantity((int) ($orderItem->getQtyOrdered() ?  $orderItem->getQtyOrdered() : $orderItem->getQty()));
            $item->setUnitAmount($orderItem->getPrice());
            $items[] = $item->getData();
        }

        if (empty($items)) {
            return $result;
        }

        $result[self::ITEMS] = $items;

        return $result;
    }
}
