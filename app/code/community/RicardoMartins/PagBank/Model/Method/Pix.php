<?php

class RicardoMartins_PagBank_Model_Method_Pix extends RicardoMartins_PagBank_Model_Method_Abstract
{
    protected $_code = 'ricardomartins_pagbank_pix';
    protected $_formBlockType = 'ricardomartins_pagbank/form_pix';
    protected $_infoBlockType = 'ricardomartins_pagbank/form_info_pix';
    protected $_canUseForMultishipping  = false;

    /**
     * @param $data
     * @return $this|RicardoMartins_PagBank_Model_Method_Pix
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('tax_id', $data->getData('data_tax_id'));

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param $amount
     * @return $this|RicardoMartins_PagBank_Model_Method_Pix
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function order(Varien_Object $payment, $amount)
    {
        $response = $this->pixPayment($this->getOrder());

        $addData = unserialize($this->getOrder()->getPayment()->getAdditionalData());
        $addData[self::ORDER_ID] = $response['id'];

        if (isset($response['qr_codes'])) {
            $qrCodes = $response['qr_codes'][0];
            $addData['pix'][self::IS_SANDBOX] = $response['is_sandbox'] ? 'Yes' : 'No';

            foreach ($qrCodes['links'] as $link) {
                if ($link['media'] == 'image/png') {
                    $addData['pix']['qrcode_image'] = $link['href'];
                }
            }
            $addData['pix']['payment_id'] = $qrCodes['id'];
            $addData['pix']['qrcode_text'] = $qrCodes['text'];
            $addData['pix']['due_date'] = $qrCodes['expiration_date'];

            $addData['pix']['created_at'] = $response['created_at'];

            $payment->setAdditionalData(serialize($addData));
            $payment->setSkipOrderProcessing(true);
            $payment->save();
        } else {
            Mage::throwException($this->_getHelper()->__("Erro na geração do QR Code!.\nPor favor tente novamente mais tarde!"));
        }

        return $this;
    }

    /**
     * @param $order
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    private function pixPayment($order)
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

        /** @var RicardoMartins_PagBank_Model_Request_Builder_Charges_Pix $pixBuilder */
        $pixBuilder = Mage::getModel('ricardomartins_pagbank/request_builder_charges_pix', $order);
        $pix = $pixBuilder->build();

        $data = array_merge(
            $orderData,
            $customerData,
            $itemsData,
            $shippingData,
            $pix
        );

        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getOrdersEndpoint();

        return $api->placePostRequest($endpoint, $data);
    }
}