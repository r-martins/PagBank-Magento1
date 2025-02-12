<?php

class RicardoMartins_PagBank_Model_Request_Object_Customer extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_CustomerInterface
{
    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::NAME);
    }

    /**
     * @param string $name
     * @return RicardoMartins_PagBank_Api_Connect_CustomerInterface
     */
    public function setName($name)
    {
        $name = mb_substr($name, 0, 30, RicardoMartins_PagBank_Api_Connect_ConnectInterface::ENCODING);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::NAME, $name);
    }

    /**
     * @return string
     */
    public function getTaxId()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::TAX_ID);
    }

    /**
     * @param string $taxId
     * @return RicardoMartins_PagBank_Api_Connect_CustomerInterface
     */
    public function setTaxId($taxId)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::TAX_ID, $taxId);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::EMAIL);
    }

    /**
     * @param string $email
     * @return RicardoMartins_PagBank_Api_Connect_CustomerInterface
     */
    public function setEmail($email)
    {
        $email = strtolower($email);
        $email = substr($email, 0, 255);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::EMAIL, $email);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_PhoneInterface[]
     */
    public function getPhones()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::PHONES);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_PhoneInterface[] $phones
     * @return RicardoMartins_PagBank_Api_Connect_CustomerInterface
     */
    public function setPhones($phones)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_CustomerInterface::PHONES, $phones);
    }
}
