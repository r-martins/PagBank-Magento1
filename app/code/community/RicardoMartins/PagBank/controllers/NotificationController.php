<?php

class RicardoMartins_PagBank_NotificationController extends Mage_Core_Controller_Front_Action
{
    const CACHE_KEY_PREFIX = 'pagbank_notif_';
    const CACHE_TTL = 60;

    /**
     * Receives the notification from PagBank and processes it.
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    public function indexAction()
    {
        $orderHash = $this->getRequest()->getParam('hash');
        if (!$orderHash) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Invalid request.');
            return;
        }

        $rawBody = $this->getRequest()->getRawBody();
        $body = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Invalid JSON format.');
            return;
        }
        
        if (!isset($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::PAGBANK_ORDER_ID])
            || !isset($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGES])) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Unexpected notification.');
            return;
        }

        /** @var RicardoMartins_PagBank_Model_Payment_Notification $notificationModel */
        $notificationModel = Mage::getModel('ricardomartins_pagbank/payment_notification');

        $pagbankOrderId = $body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::PAGBANK_ORDER_ID];
        try {
            $body = $notificationModel->checkNotification($pagbankOrderId);
        } catch (Mage_Core_Exception $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody($e->getMessage());
            return;
        }

        if (!isset($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGES]) 
            || !is_array($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGES]) 
            || !isset($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGES][0])) {
            $this->getResponse()->setHttpResponseCode(400);
            // If charges are not set or not an array in the response, return an empty body.
            $this->getResponse()->setBody('Charges not found in PagBank response.');
            return;
        }
        $charges = $body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGES];
        $charge = $charges[0];
        // PagBank Connect may put reference_id on the charge; API v1 / Vindi keep it on the order.
        $orderIncrementId = null;
        if (!empty($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID])) {
            $orderIncrementId = $body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID];
        } elseif (!empty($charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID])) {
            $orderIncrementId = $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID];
        }
        if (!$orderIncrementId) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Missing reference_id in PagBank response.');
            return;
        }
        $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID] = $orderIncrementId;

        $chargeStatus = isset($charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS])
            ? (string) $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS]
            : '';
        $orderStatus = isset($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS])
            ? (string) $body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS]
            : '';
        $resolvedStatus = $notificationModel->resolveNotificationStatus($chargeStatus, $orderStatus);
        $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGE_STATUS] = $resolvedStatus;

        $cacheKey = self::CACHE_KEY_PREFIX . $orderIncrementId;
        $cachedStatus = Mage::app()->getCache()->load($cacheKey);
        if ($cachedStatus !== false && $cachedStatus === $resolvedStatus) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Notificação já recebida a menos de 1 minuto.');
            return;
        }

        $partnerKey = !empty($body['partner'])
            ? strtolower((string) $body['partner'])
            : RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK;
        if ($partnerKey !== RicardoMartins_PagBank_Model_Partner_Partner::VINDI
            && $partnerKey !== RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK
        ) {
            $partnerKey = RicardoMartins_PagBank_Model_Partner_Partner::PAGBANK;
        }
        $partnerName = RicardoMartins_PagBank_Model_Partner_Branding::forOrderPartner($partnerKey)->productName();

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $helper->writeLog(
            sprintf('Notificação recebida do %s com os parâmetros:', $partnerName)
            . var_export($this->getRequest()->getParams(), true)
            . var_export($rawBody, true)
            . sprintf(
                "\nResolved status=%s (charge=%s, order=%s) for reference_id=%s",
                $resolvedStatus,
                $chargeStatus,
                $orderStatus,
                $orderIncrementId
            )
        );

        $hash = Mage::helper('core')->getHash($orderIncrementId);
        $hash = substr($hash, 0, 5);
        if ($hash != $orderHash) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Invalid hash.');
            return;
        }

        $result = $notificationModel->processNotification($charge);

        if (!$result) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Falha ao processar notificação. Veja pbintegracoes.log para mais detalhes.');
            return;
        }

        Mage::app()->getCache()->save(
            $resolvedStatus,
            $cacheKey,
            array('ricardomartins_pagbank_notification'),
            self::CACHE_TTL
        );

        $this->getResponse()->setBody('Notificação recebida para o pedido ' . $orderIncrementId);
    }
}
