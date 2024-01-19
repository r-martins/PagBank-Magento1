<?php

class RicardoMartins_PagBank_Block_Form_Info_Billet extends Mage_Payment_Block_Info
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/info/billet.phtml');
    }

    /**
     * @return mixed
     */
    public function getBilletAdditionalData()
    {
        $info = $this->getInfo();
        $order = $info->getOrder();
        if (!$order) {
            return null;
        }

        $payment = $order->getPayment();
        $additionalData = $payment->getAdditionalData();
        $additionalData = unserialize($additionalData);
        if (isset($additionalData['billet'])) {
            return $additionalData['billet'];
        }

        return null;
    }

    /**
     * @param $date
     * @return string
     */
    public function formatDateFromString($date)
    {
        try {
            $date = new Zend_Date($date);
        } catch (Exception $e) {
            return $date;
        }

        return $this->formatDate($date);
    }
}
