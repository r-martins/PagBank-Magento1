<?php

class RicardoMartins_PagBank_Block_Form_Info_Cc extends Mage_Payment_Block_Info
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/info/cc.phtml');
    }

    /**
     * @return mixed
     */
    public function isSandbox()
    {
        $info = $this->getInfo();
        $order = $info->getOrder();
        if (!$order) {
            return null;
        }

        $payment = $order->getPayment();
        $additionalData = $payment->getAdditionalData();
        $additionalData = unserialize($additionalData);

        return $additionalData['is_sandbox'];
    }

    /**
     * @return bool
     */
    public function showSecureIcon()
    {
        $data = parent::getSpecificInformation();
        $key = RicardoMartins_PagBank_Model_Method_Cc::CC_PAGBANK_SESSION;
        if (array_key_exists($key, $data) && $data[$key]) {
            return true;
        }

        return false;
    }
}
