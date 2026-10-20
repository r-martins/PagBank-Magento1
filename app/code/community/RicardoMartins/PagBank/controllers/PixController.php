<?php

/**
 * Streams the stored PIX QR with a browser-safe Content-Type.
 */
class RicardoMartins_PagBank_PixController extends Mage_Core_Controller_Front_Action
{
    /**
     * GET /pagbank/pix/qr/order_id/…/code/…
     */
    public function qrAction()
    {
        $response = $this->getResponse();
        $orderId = (int) $this->getRequest()->getParam('order_id');
        $code = (string) $this->getRequest()->getParam('code');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) {
            $response->setHttpResponseCode(404);
            return;
        }

        $protect = (string) $order->getProtectCode();
        if ($code === '' || $protect === '' || !hash_equals($protect, $code)) {
            $response->setHttpResponseCode(403);
            return;
        }

        $additional = unserialize($order->getPayment()->getAdditionalData() ?: '');
        $remote = '';
        if (is_array($additional) && !empty($additional['pix']['qrcode_image'])) {
            $remote = (string) $additional['pix']['qrcode_image'];
        }

        /** @var RicardoMartins_PagBank_Model_Pix_QrImage $qrImage */
        $qrImage = Mage::getModel('ricardomartins_pagbank/pix_qrImage');
        $fetched = $remote !== '' ? $qrImage->fetch($remote) : null;
        if ($fetched === null) {
            $response->setHttpResponseCode(404);
            return;
        }

        $response->clearBody();
        $response->setHeader('Content-Type', $fetched['content_type'], true);
        $response->setHeader('Cache-Control', 'private, max-age=3600', true);
        $response->setHeader('X-Content-Type-Options', 'nosniff', true);
        $response->setBody($fetched['body']);
    }
}
