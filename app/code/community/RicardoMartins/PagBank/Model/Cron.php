<?php

class RicardoMartins_PagBank_Model_Cron
{
    public function cancelPixOrdersUnpaid()
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $expirationDate = Mage::getStoreConfig('payment/ricardomartins_pagbank_pix/expiration_time') ?: 60;
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
            ->addFieldToFilter('created_at',  ['to' => $toDate, 'date' => true])
            ->getSelect()
            ->order('entity_id');

        if(count($orderCollection) < 1) {
            return;
        }

        foreach( $orderCollection as $order ) {
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
        }
    }
}