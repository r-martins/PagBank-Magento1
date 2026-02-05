<?php

// Maho/OpenMage/Magento compatibility: alias Varien_Object when missing (Maho uses Maho\DataObject)
if (!class_exists('Varien_Object', false) && class_exists('Maho\DataObject')) {
    class_alias('Maho\DataObject', 'Varien_Object');
}

class RicardoMartins_PagBank_Model_Request_Object_Amount extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_AmountInterface
{
    /**
     * @return int
     */
    public function getValue()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AmountInterface::VALUE);
    }

    /**
     * @param int|float $value
     * @return RicardoMartins_PagBank_Api_Connect_AmountInterface
     */
    public function setValue($value)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AmountInterface::VALUE, $this->convertAmountToCents($value));
    }

    /**
     * @return ?string
     */
    public function getCurrency()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AmountInterface::CURRENCY);
    }

    /**
     * @param string|null $currency
     * @return RicardoMartins_PagBank_Api_Connect_AmountInterface
     */
    public function setCurrency($currency)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AmountInterface::CURRENCY, $currency);
    }

    /**
     * @param $amount
     * @return int
     */
    private function convertAmountToCents($amount)
    {
        return (int) round($amount * 100);
    }

    public function getFees()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AmountInterface::FEES);
    }

    /**
     * @param $fees RicardoMartins_PagBank_Api_Connect_FeesInterface
     * @return RicardoMartins_PagBank_Model_Request_Object_Amount
     */
    public function setFees($fees)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AmountInterface::FEES, $fees);
    }
}
