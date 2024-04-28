<?php

class RicardoMartins_PagBank_Model_Request_Object_Holder extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_HolderInterface
{
    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_HolderInterface::NAME);
    }

    /**
     * @param string $name
     * @return RicardoMartins_PagBank_Api_Connect_HolderInterface
     */
    public function setName($name)
    {
        $name = mb_substr($name, 0, 30, RicardoMartins_PagBank_Api_Connect_ConnectInterface::ENCODING);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_HolderInterface::NAME, $name);
    }

    /**
     * @return string
     */
    public function getTaxId()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_HolderInterface::TAX_ID);
    }

    /**
     * @param string $taxId
     * @return RicardoMartins_PagBank_Api_Connect_HolderInterface
     */
    public function setTaxId($taxId)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_HolderInterface::TAX_ID, $taxId);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_HolderInterface::EMAIL);
    }

    /**
     * @param string $email
     * @return RicardoMartins_PagBank_Api_Connect_HolderInterface
     */
    public function setEmail($email)
    {
        $email = substr($email, 0, 255);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_HolderInterface::EMAIL, $email);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface[]
     */
    public function getAddress()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_HolderInterface::ADDRESS);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_AddressInterface[] $address
     * @return RicardoMartins_PagBank_Api_Connect_HolderInterface
     */
    public function setAddress(array $address)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_HolderInterface::ADDRESS, $address);
    }
}
