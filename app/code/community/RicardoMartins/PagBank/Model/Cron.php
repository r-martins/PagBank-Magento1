<?php

class RicardoMartins_PagBank_Model_Cron
{
    /**
     * Grace period after Magento-configured PIX expiry before cancel (minutes).
     */
    const PIX_CANCEL_GRACE_MINUTES = 5;

    public function cancelPixOrdersUnpaid()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $orderCollection = Mage::getResourceModel('sales/order_collection');
        $orderCollection
            ->join(
                array('payment' => 'sales/order_payment'),
                'main_table.entity_id = payment.parent_id',
                array('payment_method' => 'payment.method')
            )
            ->addFieldToFilter('status', 'pending_payment')
            ->addFieldToFilter('method', 'ricardomartins_pagbank_pix')
            ->getSelect()
            ->order('entity_id');

        if (count($orderCollection) < 1) {
            return;
        }

        $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));

        foreach ($orderCollection as $order) {
            $orderLabel = $order->getIncrementId() . ' (#' . $order->getId() . ')';
            try {
                $additionalData = @unserialize($order->getPayment()->getAdditionalData());
                if (!is_array($additionalData)) {
                    $additionalData = array();
                }
                $pixData = isset($additionalData['pix']) && is_array($additionalData['pix'])
                    ? $additionalData['pix']
                    : array();

                // Ignore API/Vindi due_date (expiration_date): it often diverges from Magento config.
                // Deadline = created_at + payment/ricardomartins_pagbank_pix/expiration_time + grace.
                $deadline = $this->resolvePixCancelDeadline($order, $pixData);
                if (!$deadline instanceof DateTime) {
                    $helper->writeLog(
                        'PIX cancel skipped (no usable created_at): ' . $orderLabel
                    );
                    continue;
                }

                if ($deadline > $now) {
                    $helper->writeLog(
                        'Order cannot be canceled yet: ' . $orderLabel
                        . ' (deadline ' . $deadline->format(DateTime::ATOM) . ')'
                    );
                    continue;
                }

                if (!$order->canCancel()) {
                    $helper->writeLog('Order cannot be canceled anymore: ' . $orderLabel);
                    continue;
                }

                $order->cancel();
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
                $history = $order->addStatusHistoryComment(
                    'Pedido cancelado depois de finalizado o prazo para pagamento com PIX.',
                    false
                );
                $history->setIsCustomerNotified(false);
                $order->save();
                $helper->writeLog(
                    'Order canceled after the deadline for payment with PIX: ' . $orderLabel
                    . ' (deadline ' . $deadline->format(DateTime::ATOM) . ')'
                );
            } catch (Exception $e) {
                $helper->writeLog(
                    'Error canceling order: ' . $orderLabel . ' - ' . $e->getMessage()
                );
            }
        }
    }

    /**
     * Magento-configured PIX expiry from order creation time (+ grace).
     * Does not use qr_codes.expiration_date / pix.due_date from the partner.
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $pixData
     * @return DateTime|null
     */
    protected function resolvePixCancelDeadline($order, array $pixData)
    {
        $tz = new DateTimeZone('America/Sao_Paulo');
        $created = $this->resolvePixCreatedAt($order, $pixData, $tz);
        if (!$created instanceof DateTime) {
            return null;
        }

        $minutes = (int) Mage::getStoreConfig(
            'payment/ricardomartins_pagbank_pix/expiration_time',
            $order->getStoreId()
        );
        if ($minutes < 1) {
            $minutes = 60;
        }

        $created->add(new DateInterval('PT' . $minutes . 'M'));
        $created->add(new DateInterval('PT' . (int) self::PIX_CANCEL_GRACE_MINUTES . 'M'));
        return $created;
    }

    /**
     * Prefer pix.created_at when present and parseable; otherwise Magento order created_at (GMT).
     * Never treat empty/null as epoch (that canceled Vindi PIX immediately).
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $pixData
     * @param DateTimeZone $tz
     * @return DateTime|null
     */
    protected function resolvePixCreatedAt($order, array $pixData, DateTimeZone $tz)
    {
        if (!empty($pixData['created_at'])) {
            try {
                $created = new DateTime($pixData['created_at']);
                $created->setTimezone($tz);
                // Guard against bogus/epoch values from strtotime(null) legacy data
                if ((int) $created->format('Y') >= 2000) {
                    return $created;
                }
            } catch (Exception $e) {
                // fall through to order created_at
            }
        }

        $orderCreated = $order->getCreatedAt();
        if ($orderCreated === null || $orderCreated === '') {
            return null;
        }

        try {
            $created = new DateTime($orderCreated, new DateTimeZone('GMT'));
            $created->setTimezone($tz);
            return $created;
        } catch (Exception $e) {
            return null;
        }
    }
}
