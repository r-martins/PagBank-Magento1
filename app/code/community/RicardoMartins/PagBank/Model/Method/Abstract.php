<?php

class RicardoMartins_PagBank_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    const IS_SANDBOX = 'is_sandbox';

    protected $_order = null;

    /**
     * Process the notification from the gateway
     *
     * @param $order
     * @param $status
     * @return void
     * @throws Mage_Core_Exception|Throwable
     */
    public function handleNotification($order, $status)
    {
        $comment = '';
        $payment = $order->getPayment();

        switch ($status) {
            case RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_CANCELED:
            case RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_DECLINED:
                $comment = "Order canceled at the payment gateway. Payment status received from the gateway is {$status}";
                $this->denyOrder($payment);
                break;
            case RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_PAID:
                $comment = "Payment status received from the gateway is: {$status}.";
                $this->approveOrder($payment);
                break;
            case RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_IN_ANALYSIS:
                $comment = "Payment is in analysis in the gateway.";
                break;
        }

        if ($comment) {
            $order->addStatusHistoryComment($comment);
        }

        $order->save();
    }

    /**
     * Deny the order
     *
     * @param $payment
     * @return void
     */
    protected function denyOrder($payment)
    {
        $order = $payment->getOrder();

        if (!$order->canCancel()) {
            Mage::throwException(sprintf("The order #%s cannot be canceled.", $order->getIncrementId()));
        }

        // checks if the order state is 'Pending Payment' and changes it
        // so that the order can be cancelled. Orders with STATE_PAYMENT_REVIEW cannot be cancelled by default in
        // Magento. See #181550828 for details
        if ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
            $order->setState(Mage_Sales_Model_Order::STATE_NEW);
        }

        $order->cancel();
        $order->save();
    }

    /**
     * Approve the order
     *
     * @param $payment
     * @return void
     * @throws Mage_Core_Exception|Throwable
     */
    protected function approveOrder($payment)
    {
        $order = $payment->getOrder();

        // checks if order state permits payment confirmation
        $notAllowedStates = array
        (
            Mage_Sales_Model_Order::STATE_CLOSED,
            Mage_Sales_Model_Order::STATE_CANCELED,
        );

        if(in_array($order->getState(), $notAllowedStates)) {
            Mage::throwException(sprintf("Could not confirm payment of the order #%s. The order status is not allowed.", $order->getIncrementId()));
        }

        if($order->hasInvoices()) {
            Mage::throwException(sprintf("Order #%s already has an invoice.", $order->getIncrementId()));
        }

        if (!$order->canInvoice()) {
            Mage::throwException(sprintf("The order #%s cannot be invoiced.", $order->getIncrementId()));
        }

        /** @var Mage_Sales_Model_Order_Payment $invoice */
        $invoice = $order->prepareInvoice();
        $invoice->register()->pay();
        $invoice->sendEmail(true);
        $order->addStatusHistoryComment(sprintf('Fatura #%s criada com sucesso.', $invoice->getIncrementId()));

        $invoice->save();
        $payment->save();

        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);

        $order->save();
    }

    /**
     * Get current order object
     *
     * @return false|mixed|null
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $helper = Mage::helper('ricardomartins_pagbank');
            $this->_order = $helper->getCurrentOrder();
        }

        return $this->_order;
    }
}