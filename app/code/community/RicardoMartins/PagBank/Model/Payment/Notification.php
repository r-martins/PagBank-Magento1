<?php

class RicardoMartins_PagBank_Model_Payment_Notification
{
    /**
     * Prefer charge status; if charge is still waiting/analysis but order-level status is final, use order status.
     *
     * @param string $chargeStatus
     * @param string $orderStatus
     * @return string
     */
    public function resolveNotificationStatus($chargeStatus, $orderStatus = '')
    {
        $chargeStatus = (string) $chargeStatus;
        $orderStatus = (string) $orderStatus;

        $pendingStatuses = array(
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_WAITING,
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_IN_ANALYSIS,
            '',
        );
        $finalStatuses = array(
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_PAID,
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_CANCELED,
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_DECLINED,
            RicardoMartins_PagBank_Api_Connect_ResponseInterface::STATUS_DENIED,
        );

        if (in_array($chargeStatus, $pendingStatuses, true) && in_array($orderStatus, $finalStatuses, true)) {
            return $orderStatus;
        }

        return $chargeStatus !== '' ? $chargeStatus : $orderStatus;
    }

    /**
     * Process the notification
     *
     * @param $charge
     * @return bool
     */
    public function processNotification($charge)
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');

        $incrementId = isset($charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID])
            ? $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID]
            : null;
        try {
            if (!$incrementId) {
                throw new Exception('Missing reference_id in notification charge');
            }
            $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
            if (!$order->getId()) {
                throw new Exception("Order {$incrementId} not found");
            }

            $payment = $order->getPayment();
            $method = $payment->getMethod();
            if ($method !== RicardoMartins_PagBank_Model_Method_Pix::METHOD_CODE) {
                $info = unserialize($payment->getAdditionalData());
                if (!isset($charge['id']) || !is_array($info) || !isset($info['charge_id'])) {
                    throw new Exception("Charge id not found in order or charge");
                }
                if ((string) $info['charge_id'] !== (string) $charge['id']) {
                    throw new Exception("Mismatch between notification charge ID and order charge ID for order ID {$order->getId()}");
                }
            }
            $status = isset($charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS])
                ? $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS]
                : '';
            $helper->writeLog(sprintf(
                'Processing notification for order %s with status %s',
                $incrementId,
                $status
            ));
            $methodInstance = $order->getPayment()->getMethodInstance();
            $methodInstance->handleNotification($order, $status, $charge);
            return true;
        } catch (Exception $e) {
            $helper->writeLog("Failed to process notification: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Check the notification
     *
     * @param $pagbankOrderId
     * @return false|mixed
     * @throws Mage_Core_Exception
     */
    public function checkNotification($pagbankOrderId)
    {
        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getPaymentInfoEndpoint();
        $endpoint = str_replace('{pagbankOrderId}', $pagbankOrderId, $endpoint);

        return $api->placeGetRequest($endpoint, [], false);
    }
}
