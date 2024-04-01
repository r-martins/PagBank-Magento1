<?php

class RicardoMartins_PagBank_Model_Cron
{
    public function cancelPixOrdersUnpaid()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $expirationDate = Mage::getStoreConfig('payment/ricardomartins_pagbank_pix/expiration_time') ?: 60;
        $expirationDate += 5;
        $toDate = date('Y-m-d H:i:s', strtotime('-' . $expirationDate . 'minutes'));

        $orderCollection = Mage::getResourceModel('sales/order_collection');
        $orderCollection
            ->join(
                ['payment' => 'sales/order_payment'],
                'main_table.entity_id = payment.parent_id',
                ['payment_method' => 'payment.method']
            )
            ->addFieldToFilter('status', 'pending')
            ->addFieldToFilter('method', 'ricardomartins_pagbank_pix')
            ->getSelect()
            ->order('entity_id');

        if(count($orderCollection) < 1) {
            return;
        }

        foreach( $orderCollection as $order ) {
            try {
                $additionalData = unserialize($order->getPayment()->getAdditionalData());
                $createdAt = $additionalData['pix']['created_at'];

                $date = new DateTime();
                $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                $date->setTimestamp(strtotime($createdAt));
                $date->add(new DateInterval('PT' . $expirationDate . 'M'));

                $now = new DateTime();
                $now->setTimezone(new DateTimeZone('America/Sao_Paulo'));

                if($date > $now) {
                    $helper->writeLog('Order cannot be canceled yet: ' . $order->getId());
                    continue;
                }

                if(!$order->canCancel()) {
                    $helper->writeLog('Order cannot be canceled anymore: ' . $order->getId());
                    continue;
                } else {
                    $order->cancel();
                }

                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
                $history = $order->addStatusHistoryComment('Pedido cancelado depois de finalizado o prazo para pagamento com PIX.', false);
                $history->setIsCustomerNotified(false);
                $order->save();
                $helper->writeLog('Order canceled after the deadline for payment with PIX: ' . $order->getId());
            } catch (Exception $e) {
                $helper->writeLog('Error canceling order: ' . $order->getId() . ' - ' . $e->getMessage());
            }
        }
    }
}