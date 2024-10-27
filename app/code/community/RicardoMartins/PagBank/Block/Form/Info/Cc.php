<?php

class RicardoMartins_PagBank_Block_Form_Info_Cc extends Mage_Payment_Block_Info
{
    private $hideSpecificInfo = [
        RicardoMartins_PagBank_Model_Method_Cc::CC_PAGBANK_SESSION
    ];

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
        $prefix3DS = '3DS_';
        $result = array_filter($data, function($valor) use ($prefix3DS) {
            return strpos($valor, $prefix3DS) === 0;
        });

        if (count($result) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSpecificInformation()
    {
        $result = parent::getSpecificInformation();
        foreach ($this->hideSpecificInfo as $key) {
            unset($result[$key]);
        }

        return $result;
    }
}
