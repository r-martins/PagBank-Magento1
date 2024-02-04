<?php

class RicardoMartins_PagBank_Model_Method_Billet extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'ricardomartins_pagbank_billet';
    protected $_formBlockType = 'ricardomartins_pagbank/form_billet';
    protected $_infoBlockType = 'ricardomartins_pagbank/form_info_billet';
    protected $_order = null;
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
            $payment->save();
        } else {
            Mage::throwException($this->_getHelper()->__("Erro na emissão do boleto!\nMotivo: " . $pay->error_description . ".\nPor favor tente novamente mais tarde!"));
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
        $orderDataObj = new RicardoMartins_PagBank_Model_Request_Builder_Order($order);
        $orderData = $orderDataObj->build();

        $customerObj = new RicardoMartins_PagBank_Model_Request_Builder_Customer($order);
        $customer = $customerObj->build();

        $itemsObj = new RicardoMartins_PagBank_Model_Request_Builder_Items($order);
        $items = $itemsObj->build();

        $shippingObj = new RicardoMartins_PagBank_Model_Request_Builder_Shipping($order);
        $shipping = $shippingObj->build();

        $billetObj = new RicardoMartins_PagBank_Model_Request_Builder_Charges_Billet($order);
        $billet = $billetObj->build();

        $data = array_merge(
            $orderData,
            $customer,
            $items,
            $shipping,
            $billet
        );

        $api = new RicardoMartins_PagBank_Model_Api_Connect_Client();

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $endpoint = $helper->getOrdersEndpoint();

        return $api->placePostRequest($endpoint, $data);
    }

    /**
     * Get current order object
     * @return false|mixed|null
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = Mage::registry('current_order');
            if (!$this->_order) {
                $sessionInstance = Mage::getModel("core/session")->getSessionQuote();
                $this->_order = Mage::getModel($sessionInstance)->getQuote();
                if (!$this->_order) {
                    return false;
                }
            }
        }
        return $this->_order;
    }
}