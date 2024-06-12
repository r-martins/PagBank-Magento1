<?php

class RicardoMartins_PagBank_Model_Method_Billet extends RicardoMartins_PagBank_Model_Method_Abstract
{
    const METHOD_CODE = 'ricardomartins_pagbank_billet';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'ricardomartins_pagbank/form_billet';
    protected $_infoBlockType = 'ricardomartins_pagbank/form_info_billet';
    protected $_canUseForMultishipping  = false;

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
        $info->setAdditionalInformation('tax_id', $data->getData('data_tax_id'));

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param $amount
     * @return $this|RicardoMartins_PagBank_Model_Method_Billet
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function order(Varien_Object $payment, $amount)
    {
        $response = $this->billetPayment($this->getOrder());
        $charges = $response['charges'][0];
        $pay = $charges['payment_method']['boleto'];
        $links = $charges['links'];

        if ($charges['payment_response']['code'] == 20000) {
            $addData = unserialize($this->getOrder()->getPayment()->getAdditionalData());
            $addData['billet'][self::IS_SANDBOX] = $response['is_sandbox'] ? 'Yes' : 'No';

            foreach ($links as $link) {
                if ($link['media'] == 'application/pdf') {
                    $addData['billet']['payment_link_boleto_pdf'] = $link['href'];
                }
                if ($link['media'] == 'image/png') {
                    $addData['billet']['payment_link_boleto_image'] = $link['href'];
                }
            }
            $addData['billet']['barcode'] = $pay['barcode'];
            $addData['billet']['formatted_barcode'] = $pay['formatted_barcode'];
            $addData['billet']['due_date'] = $pay['due_date'];
            $addData['charge_id'] = $charges['id'];

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
}