<?php

class RicardoMartins_PagBank_Model_Request_Object_Customer_Phone extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_PhoneInterface
{
    /**
     * @return int
     */
    public function getCountry()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::COUNTRY);
    }

    /**
     * @param int $country
     * @return RicardoMartins_PagBank_Api_Connect_PhoneInterface
     */
    public function setCountry($country)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::COUNTRY, $country);
    }

    /**
     * @return int
     */
    public function getArea()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::AREA);
    }

    /**
     * @param int $area
     * @return RicardoMartins_PagBank_Api_Connect_PhoneInterface
     */
    public function setArea($area)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::AREA, $area);
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::NUMBER);
    }

    /**
     * @param int $number
     * @return RicardoMartins_PagBank_Api_Connect_PhoneInterface
     */
    public function setNumber($number)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::NUMBER, $number);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::TYPE);
    }

    /**
     * @param string $type
     * @return RicardoMartins_PagBank_Api_Connect_PhoneInterface
     */
    public function setType($type)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PhoneInterface::TYPE, $type);
    }
}
