<?php

// Maho/OpenMage/Magento compatibility: alias Varien_Object when missing (Maho uses Maho\DataObject)
if (!class_exists('Varien_Object', false) && class_exists('Maho\DataObject')) {
    class_alias('Maho\DataObject', 'Varien_Object');
}

class RicardoMartins_PagBank_Model_Request_Object_Paymentmethod extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
{
    /**
     * @return string
     */
    public function getType()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE);
    }

    /**
     * @param string $type
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    public function setType($type)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE, $type);
    }

    /**
     * @return ?int
     */
    public function getInstallments()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::INSTALLMENTS);
    }

    /**
     * @param int|null $installments
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    public function setInstallments($installments)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::INSTALLMENTS, $installments);
    }

    /**
     * @return ?string
     */
    public function getSoftDescriptor()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::SOFT_DESCRIPTOR);
    }

    /**
     * @param string $softDescriptor
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    public function setSoftDescriptor($softDescriptor)
    {
        $softDescriptor = mb_substr($softDescriptor, 0, 17, RicardoMartins_PagBank_Api_Connect_ConnectInterface::ENCODING);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::SOFT_DESCRIPTOR, $softDescriptor);
    }

    /**
     * @return bool|null
     */
    public function getCapture()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::CAPTURE);
    }

    /**
     * @param bool|null $capture
     * @return RicardoMartins_PagBank_Model_Request_Object_PaymentMethod
     */
    public function setCapture($capture)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::CAPTURE, $capture);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface[]
     */
    public function getCard()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_CREDIT_CARD_OBJECT);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_PaymentMethod_CardInterface[] $card
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    public function setCard($card)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_CREDIT_CARD_OBJECT, $card);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface[]
     */
    public function getBillet()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_BILLET_OBJECT);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface[] $billet
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    public function setBillet(array $billet)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::TYPE_BILLET_OBJECT, $billet);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface[]
     */
    public function getAuthenticationMethod()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::AUTHENTICATION_METHOD);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_PaymentMethod_Card_AuthenticationMethodInterface[] $authenticationMethod
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    public function setAuthenticationMethod(array $authenticationMethod)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface::AUTHENTICATION_METHOD, $authenticationMethod);
    }
}
