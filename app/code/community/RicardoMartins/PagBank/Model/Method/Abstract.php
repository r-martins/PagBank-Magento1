<?php

class RicardoMartins_PagBank_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    const ORDER_ID = 'pagbank_order_id';
    const IS_SANDBOX = 'is_sandbox';

    protected $_order = null;

    /**
     * Process the notification from the gateway
     *
     * @param $order
     * @param $status
     * @param null $charge
     * @return void
     * @throws Mage_Core_Exception
     * @throws Throwable
     */
    public function handleNotification($order, $status, $charge = null)
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

        if (!$order->canCancel() && !$order->canCreditmemo()) {
            Mage::throwException(sprintf("The order #%s cannot be canceled.", $order->getIncrementId()));
        }

        if ($order->canCreditmemo() && $order->hasInvoices()) {
            $service = Mage::getModel('sales/service_order', $order);
            $creditmemo = $service->prepareCreditmemo();
            $creditmemo->setRefundRequested(true);
            $creditmemo->setOfflineRequested(true);
            $creditmemo->register();
            $creditmemo->save();
            $order->save();
            return;
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

        if (!$payment->getLastTransId()) {
            $additionalData = unserialize($payment->getAdditionalData());
            if (is_array($additionalData) && isset($additionalData['charge_id'])) {
                $charge_id = $additionalData['charge_id'];
                $payment->setTransactionId($charge_id)
                    ->setIsTransactionClosed(0)
                    ->save();
            }
        }
        
        /** @var Mage_Sales_Model_Order_Payment $invoice */
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
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
    /**
     * Refund payment
     * @param Varien_Object $payment
     * @param mixed $amount
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $additionalData = @unserialize($payment->getAdditionalData());
        $chargeId = isset($additionalData['charge_id']) ? $additionalData['charge_id'] : null;

        if (!$chargeId) {
            Mage::throwException($this->_getHelper()->__('Não foi possível localizar o ID da transação para reembolso.'));
        }

        $order = $payment->getOrder();
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $amountObj = Mage::getModel('ricardomartins_pagbank/request_object_amount');
        $amountObj->setValue($amount);

        $currencyCode = 'BRL';
        if ($order->getOrderCurrency()) {
            $currencyCode = $order->getOrderCurrency()->getCode();
        }
        if ($order->getQuoteCurrencyCode()) {
            $currencyCode = $order->getQuoteCurrencyCode();
        }
        $amountObj->setCurrency($currencyCode);

        $endpoint = $helper->getRefundEndpoint($chargeId, $order->getStoreId());
        $response = $api->placePostRequest($endpoint, ['amount' => $amountObj->getData()]);

        if (isset($response['errors'])) {
            Mage::throwException($this->_getHelper()->__('Refund failed: %s', $response['errors'][0]['description']));
        }

        $payment->setTransactionId($chargeId . '-refund-' . time())
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);

        return parent::refund($payment, $amount);
    }
}