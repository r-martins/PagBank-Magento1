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
}
