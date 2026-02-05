<?php

// Maho/OpenMage/Magento compatibility: alias Varien_Object when missing (Maho uses Maho\DataObject)
if (!class_exists('Varien_Object', false) && class_exists('Maho\DataObject')) {
    class_alias('Maho\DataObject', 'Varien_Object');
}

class RicardoMartins_PagBank_Model_Request_Object_Interest extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_InterestInterface
{
    /**
     * @return int
     */
    public function getInstallments()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_InterestInterface::INSTALLMENTS);
    }

    /**
     * @param int $installmentes
     * @return RicardoMartins_PagBank_Api_Connect_BuyerInterface
     */
    public function setInstallments($installmentes)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_InterestInterface::INSTALLMENTS, $installmentes);
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_InterestInterface::INSTALLMENTS);
    }

    /**
     * @param int $total
     *
     * @return RicardoMartins_PagBank_Api_Connect_InterestInterface
     */
    public function setTotal($total)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_InterestInterface::TOTAL, $total);
    }
}
