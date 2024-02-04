<?php

class RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Card extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface
{
    /**
     * @return string|null
     */
    public function getEncrypted()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::ENCRYPTED);
    }

    /**
     * @param string|null $encrypted
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface
     */
    public function setEncrypted($encrypted)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::ENCRYPTED, $encrypted);
    }

    /**
     * @return string|null
     */
    public function getCardId()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::CARD_ID);
    }

    /**
     * @param string|null $cardId
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface
     */
    public function setCardId($cardId)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::CARD_ID, $cardId);
    }

    /**
     * @return string|null
     */
    public function getSecurityCode()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::SECURITY_CODE);
    }

    /**
     * @param int|null $securityCode
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface
     */
    public function setSecurityCode($securityCode)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::SECURITY_CODE, $securityCode);
    }

    /**
     * @return array|null
     */
    public function getHolder()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::HOLDER);
    }

    /**
     * @param array|null $holder
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface
     */
    public function setHolder($holder)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface::HOLDER, $holder);
    }
}
