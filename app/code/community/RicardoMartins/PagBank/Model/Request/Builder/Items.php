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
        $hideItems = $helper->hideOrderItems($storeId);
        $method = $this->order->getPayment()->getMethod();
        if ($hideItems && $method !== RicardoMartins_PagBank_Model_Method_Cc::METHOD_CODE) {
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

            $qtyOrdered = (float) ($orderItem->getQtyOrdered() ? $orderItem->getQtyOrdered() : $orderItem->getQty());
            $name = $orderItem->getName();
            $quantity = (int) $qtyOrdered;
            $unitAmount = $orderItem->getPrice();

            if ($this->shouldSendFractionalItemAsSingleUnit($orderItem, $qtyOrdered)) {
                $quantity = 1;
                $name = $name . ' - ' . $this->formatOrderedQtyForItemName($qtyOrdered) . ' un.';
                $unitAmount = $orderItem->getRowTotal();
            }

            /** @var RicardoMartins_PagBank_Model_Request_Object_Item $item */
            $item = Mage::getModel('ricardomartins_pagbank/request_object_item');
            $item->setReferenceId($orderItem->getSku());
            $item->setName($name);
            $item->setQuantity($quantity);
            $item->setUnitAmount($unitAmount);
            $items[] = $item->getData();
        }

        if (empty($items)) {
            return $result;
        }

        $result[self::ITEMS] = $items;

        return $result;
    }

    /**
     * PagBank expects an integer quantity; casting decimals with (int) turned values like 0.7 into 0.
     * For products that allow decimal quantities: send quantity 1, unit_amount = line row total,
     * and append " - X un." to the name (X = ordered quantity).
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param float $qtyOrdered
     * @return bool
     */
    private function shouldSendFractionalItemAsSingleUnit($orderItem, $qtyOrdered)
    {
        return $qtyOrdered > 0 && $this->orderItemAllowsFractionalQty($orderItem);
    }

    /**
     * Same criterion as the catalog (sales_flat_order_item.is_qty_decimal / stock is_qty_decimal).
     * Avoids relying only on Mage_Catalog_Model_Product_Type_Abstract::canUseQtyDecimals(), which can
     * be true by default for types without an explicit flag in config.xml.
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return bool
     */
    private function orderItemAllowsFractionalQty($orderItem)
    {
        if ($orderItem->getIsQtyDecimal()) {
            return true;
        }
        $product = $orderItem->getProduct();
        if (!$product || !$product->getId()) {
            $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
        }
        if (!$product || !$product->getId()) {
            return false;
        }
        $product->setStoreId($this->order->getStoreId());
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            foreach ($orderItem->getChildrenItems() as $child) {
                $childProduct = $child->getProduct();
                if (!$childProduct || !$childProduct->getId()) {
                    $childProduct = Mage::getModel('catalog/product')->load($child->getProductId());
                }
                if ($childProduct && $childProduct->getId()) {
                    $childProduct->setStoreId($this->order->getStoreId());
                    $stockItem = $childProduct->getStockItem();
                    if ($stockItem && $stockItem->getIsQtyDecimal()) {
                        return true;
                    }
                }
            }
        }
        $stockItem = $product->getStockItem();
        return $stockItem && $stockItem->getIsQtyDecimal();
    }

    /**
     * Formats ordered quantity for the item name suffix (integers without decimals; up to 4 decimal places trimmed).
     *
     * @param float $qtyOrdered
     * @return string
     */
    private function formatOrderedQtyForItemName($qtyOrdered)
    {
        $qtyOrdered = (float) $qtyOrdered;
        if (abs($qtyOrdered - round($qtyOrdered)) < 0.00001) {
            return (string) (int) round($qtyOrdered);
        }
        return rtrim(rtrim(sprintf('%.4F', $qtyOrdered), '0'), '.');
    }
}
