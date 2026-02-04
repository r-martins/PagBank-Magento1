<?php

class RicardoMartins_PagBank_Block_Form_Info_Pix extends Mage_Payment_Block_Info
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ricardomartins/pagbank/form/info/pix.phtml');
    }

    /**
     * @return mixed
     */
    public function getPixAdditionalData()
    {
        $info = $this->getInfo();
        $order = $info->getOrder();
        if (!$order) {
            return null;
        }

        $payment = $order->getPayment();
        $additionalData = $payment->getAdditionalData();
        $additionalData = unserialize($additionalData);
        if (isset($additionalData['pix'])) {
            return $additionalData['pix'];
        }

        return null;
    }

    /**
     * @param string $date
     * @return string
     */
    public function formatDateFromString($date)
    {
        try {
            $dateTime = new DateTime($date);
            return $this->formatDate($dateTime->getTimestamp(), 'short', true);
        } catch (Exception $e) {
            return $date;
        }
    }
}
