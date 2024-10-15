<?php

class RicardoMartins_PagBank_Model_Request_Object_Buyer extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_BuyerInterface
{
    /**
     * @return RicardoMartins_PagBank_Api_Connect_InterestInterface
     */
    public function getInterest()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_BuyerInterface::INTEREST);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_InterestInterface $buyer
     * @return RicardoMartins_PagBank_Api_Connect_InterestInterface
     */
    public function setInterest($interest)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_BuyerInterface::INTEREST, $interest);
    }
}
