<?php

class RicardoMartins_PagBank_Model_Request_Object_Customer_Address extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_AddressInterface
{
    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::STREET);
    }

    /**
     * @param string $street
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setStreet($street)
    {
        $street = substr($street, 0, 160);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::STREET, $street);
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::NUMBER);
    }

    /**
     * @param string $number
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setNumber($number)
    {
        $number = substr($number, 0, 20);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::NUMBER, $number);
    }

    /**
     * @return string|null
     */
    public function getComplement()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::COMPLEMENT);
    }

    /**
     * @param string $complement
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setComplement($complement)
    {
        $complement = substr($complement, 0, 40);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::COMPLEMENT, $complement);
    }

    /**
     * @return string
     */
    public function getLocality()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::LOCALITY);
    }

    /**
     * @param string $locality
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setLocality($locality)
    {
        $locality = substr($locality, 0, 60);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::LOCALITY, $locality);
    }


    /**
     * @return string
     */
    public function getCity()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::CITY);
    }

    /**
     * @param string $city
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setCity($city)
    {
        $city = substr($city, 0, 90);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::CITY, $city);
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::REGION);
    }

    /**
     * @param string $region
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setRegion($region)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::REGION, $region);
    }

    /**
     * @return string
     */
    public function getRegionCode()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::REGION_CODE);
    }

    /**
     * @param string $regionCode
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setRegionCode($regionCode)
    {
        $regionCode = substr($regionCode, 0, 2);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::REGION_CODE, $regionCode);
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::COUNTRY);
    }

    /**
     * @param string|null $country
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setCountry($country = null)
    {
        $country = $country ?: RicardoMartins_PagBank_Api_Connect_AddressInterface::COUNTRY_CODE_BRAZIL;
        $country = substr($country, 0, 3);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::COUNTRY, $country);
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_AddressInterface::POSTAL_CODE);
    }

    /**
     * @param string $postalCode
     * @return RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    public function setPostalCode($postalCode)
    {
        $postalCode = substr($postalCode, 0, 8);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_AddressInterface::POSTAL_CODE, $postalCode);
    }
}
