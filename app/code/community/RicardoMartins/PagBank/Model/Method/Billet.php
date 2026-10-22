<?php

// Maho/OpenMage/Magento compatibility: alias Varien_Object when missing (Maho uses Maho\DataObject)
if (!class_exists('Varien_Object', false) && class_exists('Maho\DataObject')) {
    class_alias('Maho\DataObject', 'Varien_Object');
}

class RicardoMartins_PagBank_Model_Method_Billet extends RicardoMartins_PagBank_Model_Method_Abstract
{
    const METHOD_CODE = 'ricardomartins_pagbank_billet';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'ricardomartins_pagbank/form_billet';
    protected $_infoBlockType = 'ricardomartins_pagbank/form_info_billet';
    protected $_canUseForMultishipping  = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    /**
     * @param $data
     * @return $this|RicardoMartins_PagBank_Model_Method_Billet
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $helper->cleanAdditionalInformation($info, 'cc_');

        $info->setAdditionalInformation('tax_id', $helper->normalizeDocument($data->getData('data_tax_id')));

        return $this;
    }

    /**
     * @param Varien_Object|\Maho\DataObject $payment
     * @param $amount
     * @return $this|RicardoMartins_PagBank_Model_Method_Billet
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function order($payment, $amount)
    {
        /** @var RicardoMartins_PagBank_Helper_PaymentData $paymentDataHelper */
        $paymentDataHelper = Mage::helper('ricardomartins_pagbank/paymentData');
        $paymentDataHelper->stampPartner($payment, $this->getOrder()->getStoreId());

        $response = $this->billetPayment($this->getOrder());
        $charges = $response['charges'][0];
        $pay = $charges['payment_method']['boleto'] ?? [];
        // Prefer charge links; Vindi/API v1 may only nest PDF under payment_method.boleto.links
        $links = $charges['links'] ?? ($pay['links'] ?? []);
        if (!is_array($links)) {
            $links = [];
        }

        $addData = unserialize($this->getOrder()->getPayment()->getAdditionalData());
        if (!is_array($addData)) {
            $addData = array();
        }
        $addData[self::ORDER_ID] = $response['id'];

        $paymentResponseCode = $charges['payment_response']['code'] ?? null;
        $chargeStatus = $charges['status'] ?? '';
        $hasBarcode = !empty($pay['barcode']);
        // PagBank: payment_response.code 20000. Vindi/API v1 may omit it — barcode + WAITING/PAID is success.
        $boletoIssued = ($paymentResponseCode == 20000)
            || ($hasBarcode && in_array($chargeStatus, ['WAITING', 'PAID'], true));

        if ($boletoIssued) {
            $addData['billet'][self::IS_SANDBOX] = !empty($response['is_sandbox']) ? 'Yes' : 'No';

            foreach ($links as $link) {
                if (!is_array($link)) {
                    continue;
                }
                if (($link['media'] ?? '') == 'application/pdf' || ($link['rel'] ?? '') === 'BOLETO.PDF') {
                    $addData['billet']['payment_link_boleto_pdf'] = $link['href'];
                }
                if (($link['media'] ?? '') == 'image/png' || ($link['rel'] ?? '') === 'BOLETO.PNG') {
                    $addData['billet']['payment_link_boleto_image'] = $link['href'];
                }
            }
            // Fallback: top-level links from API v1 boleto response
            if (empty($addData['billet']['payment_link_boleto_pdf']) && !empty($response['links']) && is_array($response['links'])) {
                foreach ($response['links'] as $link) {
                    if (is_array($link) && (($link['rel'] ?? '') === 'BOLETO.PDF' || ($link['media'] ?? '') === 'application/pdf')) {
                        $addData['billet']['payment_link_boleto_pdf'] = $link['href'];
                        break;
                    }
                }
            }
            $addData['billet']['barcode'] = $pay['barcode'];
            $addData['billet']['formatted_barcode'] = $pay['formatted_barcode'] ?? $pay['barcode'];
            $addData['billet']['due_date'] = $pay['due_date'] ?? null;
            $addData['charge_id'] = $charges['id'];
            if (!empty($response['partner_bill_id'])) {
                $addData['partner_bill_id'] = $response['partner_bill_id'];
            }

            $partner = $paymentDataHelper->resolveWritePartner($payment);
            $branding = RicardoMartins_PagBank_Model_Partner_Branding::forOrderPartner($partner);
            $portalUrl = $branding->adminPortalUrl(
                $addData['partner_bill_id'] ?? null,
                $charges['id'],
                !empty($response['is_sandbox'])
            );
            if ($portalUrl) {
                $addData['charge_link'] = $portalUrl;
            }

            $payment->setAdditionalData(serialize($addData));
            $payment->setSkipOrderProcessing(true);
            $payment->save();
        } else {
            Mage::throwException($this->_getHelper()->__("Erro na emissão do boleto!.\nPor favor tente novamente mais tarde!"));
        }

        return $this;
    }

    /**
     * @param $order
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    private function billetPayment($order)
    {
        /** @var RicardoMartins_PagBank_Model_Request_Builder_Order $orderBuilder */
        $orderBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_order', $order);
        $orderData = $orderBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Customer $customerBuilder */
        $customerBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_customer', $order);
        $customerData = $customerBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Items $itemsBuilder */
        $itemsBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_items', $order);
        $itemsData = $itemsBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Shipping $shippingBuilder */
        $shippingBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_shipping', $order);
        $shippingData = $shippingBuilder->build();

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Charges_Billet $billetBuilder */
        $billetBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_charges_billet', $order);
        $billetData = $billetBuilder->build();

        $data = array_merge(
            $orderData,
            $customerData,
            $itemsData,
            $shippingData,
            $billetData
        );

        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getOrdersEndpoint();

        return $api->placePostRequest($endpoint, $data);
    }
    /**
     * Refund payment
     * @param Varien_Object|\Maho\DataObject $payment
     * @param float $amount
     * @return Mage_Payment_Model_Method_Abstract
     * @throws Mage_Core_Exception
     */
    public function refund($payment, $amount)
    {
        return parent::refund($payment, $amount);
    }
}