<?php

class RicardoMartins_PagBank_NotificationController extends Mage_Core_Controller_Front_Action
{
    /**
     * Receives the notification from PagBank and processes it.
     *
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Cache_Exception
     * @throws Zend_Controller_Response_Exception
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

        if (!isset($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::PAGBANK_ORDER_ID])
            || !isset($body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::CHARGES])) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Unexpected notification.');
            return;
        }

        /** @var RicardoMartins_PagBank_Model_Payment_Notification $notificationModel */
        $notificationModel = Mage::getModel('ricardomartins_pagbank/payment_notification');

        $pagbankOrderId = $body[RicardoMartins_PagBank_Api_Connect_ResponseInterface::PAGBANK_ORDER_ID];
        $body = $notificationModel->checkNotification($pagbankOrderId);

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
        $orderIncrementId = $charge[RicardoMartins_PagBank_Api_Connect_ResponseInterface::REFERENCE_ID];

        $alreadyReceived = Mage::app()->getCache()->load($orderIncrementId);
        if ($alreadyReceived) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Notificação já recebida a menos de 1 minuto.');
            return;
        }
        Mage::app()->getCache()->save('in_progress', $orderIncrementId, ['ricardomartins_pagbank_notification'], 60);

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $helper->writeLog(
            'Notificação recebida do PagBank com os parâmetros:'
            . var_export($this->getRequest()->getParams(), true)
            . var_export($rawBody, true)
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
            $this->getResponse()->setBody('Falha ao processar notificação. Veja pagbank.log para mais detalhes.');
            return;
        }

        $this->getResponse()->setBody('Notificação recebida para o pedido ' . $orderIncrementId);
    }
}