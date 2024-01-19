<?php

class RicardoMartins_PagBank_Model_Request_Object_Item extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_ItemInterface
{
    /**
     * @return string
     */
    public function getReferenceId()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ItemInterface::REFERENCE_ID);
    }

    /**
     * @param string $referenceId
     * @return RicardoMartins_PagBank_Api_Connect_ItemInterface
     */
    public function setReferenceId($referenceId)
    {
        $referenceId = substr($referenceId, 0, 255);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_ItemInterface::REFERENCE_ID, $referenceId);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ItemInterface::NAME);
    }

    /**
     * @param string $name
     * @return RicardoMartins_PagBank_Api_Connect_ItemInterface
     */
    public function setName($name)
    {
        $name = substr($name, 0, 64);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_ItemInterface::NAME, $name);
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ItemInterface::QUANTITY);
    }

    /**
     * @param int $quantity
     * @return RicardoMartins_PagBank_Api_Connect_ItemInterface
     */
    public function setQuantity($quantity)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_ItemInterface::QUANTITY, $quantity);
    }

    /**
     * @return int
     */
    public function getUnitAmount()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ItemInterface::UNIT_AMOUNT);
    }

    /**
     * @param int|float $unitAmount
     * @return RicardoMartins_PagBank_Api_Connect_ItemInterface
     */
    public function setUnitAmount($unitAmount)
    {
        $unitAmount = $this->convertAmountToCents($unitAmount);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_ItemInterface::UNIT_AMOUNT, $unitAmount);
    }

    /**
     * @param int|float $amount
     * @return int
     */
    private function convertAmountToCents($amount)
    {
        return round($amount * 100);
    }
}
