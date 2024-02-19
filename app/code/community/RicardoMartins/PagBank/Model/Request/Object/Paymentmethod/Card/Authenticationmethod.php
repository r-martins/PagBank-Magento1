<?php

class RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Card_Authenticationmethod extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface
{
    /**
     * @return string
     */
    public function getType()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface::AUTHENTICATION_METHOD_TYPE);
    }

    /**
     * @param string $type
     * @return RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Card_Authenticationmethod
     */
    public function setType($type)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface::AUTHENTICATION_METHOD_TYPE, $type);
    }

    /**
     * @return string
     */
    public function getCardId()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface::AUTHENTICATION_METHOD_ID);
    }

    /**
     * @param string $id
     * @return RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Card_Authenticationmethod
     */
    public function setCardId($id)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface::AUTHENTICATION_METHOD_ID, $id);
    }
}
