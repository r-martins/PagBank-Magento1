<?php

// Maho/OpenMage/Magento compatibility: alias Varien_Object when missing (Maho uses Maho\DataObject)
if (!class_exists('Varien_Object', false) && class_exists('Maho\DataObject')) {
    class_alias('Maho\DataObject', 'Varien_Object');
}

class RicardoMartins_PagBank_Model_Request_Object_Charge extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_ChargeInterface
{
    /**
     * @return string
     */
    public function getReferenceId()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::REFERENCE_ID);
    }

    /**
     * @param string $referenceId
     * @return RicardoMartins_PagBank_Api_Connect_ChargeInterface
     */
    public function setReferenceId($referenceId)
    {
        $referenceId = substr($referenceId, 0, 64);
        return $this->setData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::REFERENCE_ID, $referenceId);
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::CREATED_AT);
    }

    /**
     * @param string $date
     * @return RicardoMartins_PagBank_Api_Connect_ChargeInterface
     */
    public function setCreatedAt($date = '')
    {
        $createdAt = null;

        try {
            $createdAt = new \DateTime($date);
            $createdAt = $createdAt->format(self::DATETIME_FORMAT);
        } catch (\Exception $e) {}

        return $this->setData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::CREATED_AT, $createdAt);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface
     */
    public function getAmount()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::AMOUNT);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface[] $amount
     * @return RicardoMartins_PagBank_Api_Connect_ChargeInterface
     */
    public function setAmount($amount)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::AMOUNT, $amount);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface[]
     */
    public function getPaymentMethod()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::PAYMENT_METHOD);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_PaymentMethodInterface[] $paymentMethod
     * @return RicardoMartins_PagBank_Api_Connect_ChargeInterface
     */
    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_ChargeInterface::PAYMENT_METHOD, $paymentMethod);
    }
}
