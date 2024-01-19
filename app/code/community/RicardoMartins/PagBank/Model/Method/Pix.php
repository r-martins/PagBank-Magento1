<?php

class RicardoMartins_PagBank_Model_Method_Pix extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'ricardomartins_pagbank_pix';
    protected $_formBlockType = 'ricardomartins_pagbank/form_pix';
    protected $_infoBlockType = 'ricardomartins_pagbank/form_info_pix';
    protected $_order = null;
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

        if (isset($response['qr_codes'])) {
            $qrCodes = $response['qr_codes'][0];
            $addData = unserialize($this->getOrder()->getPayment()->getAdditionalData());
            foreach ($qrCodes['links'] as $link) {
                if ($link['media'] == 'image/png') {
                    $addData['pix']['qrcode_image'] = $link['href'];
                }
            }
            $addData['pix']['payment_id'] = $qrCodes['id'];
            $addData['pix']['qrcode_text'] = $qrCodes['text'];
            $addData['pix']['due_date'] = $qrCodes['expiration_date'];

            $payment->setAdditionalData(serialize($addData));
            $payment->save();
        } else {
            Mage::throwException($this->_getHelper()->__("Erro na geração do QR Code!\nMotivo: " . $pay->error_description . ".\nPor favor tente novamente mais tarde!"));
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
        $orderDataObj = new RicardoMartins_PagBank_Model_Request_Builder_Order($order);
        $orderData = $orderDataObj->build();

        $customerObj = new RicardoMartins_PagBank_Model_Request_Builder_Customer($order);
        $customer = $customerObj->build();

        $itemsObj = new RicardoMartins_PagBank_Model_Request_Builder_Items($order);
        $items = $itemsObj->build();

        $shippingObj = new RicardoMartins_PagBank_Model_Request_Builder_Shipping($order);
        $shipping = $shippingObj->build();

        $pixObj = new RicardoMartins_PagBank_Model_Request_Builder_Charges_Pix($order);
        $pix = $pixObj->build();

        $data = array_merge(
            $orderData,
            $customer,
            $items,
            $shipping,
            $pix
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