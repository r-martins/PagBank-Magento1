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

            $item = new RicardoMartins_PagBank_Model_Request_Object_Item();
            $item->setReferenceId($orderItem->getSku());
            $item->setName($orderItem->getName());
            $item->setQuantity((int) $orderItem->getQtyOrdered());
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
