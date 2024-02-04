<?php

class RicardoMartins_PagBank_Model_Request_Object_Installments extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_InstallmentsInterface
{
    /**
     * @return array|null
     */
    public function getPaymentMethods()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::PAYMENT_METHODS);
    }

    /**
     * @param string $paymentMethods
     * @return RicardoMartins_PagBank_Api_Connect_InstallmentsInterface
     */
    public function setPaymentMethods($paymentMethods)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::PAYMENT_METHODS, $paymentMethods);
    }

    /**
     * @return ?int
     */
    public function getCreditCardBin()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::CREDIT_CARD_BIN);
    }

    /**
     * @param ?int $creditCardBin
     * @return RicardoMartins_PagBank_Api_Connect_InstallmentsInterface
     */
    public function setCreditCardBin($creditCardBin)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::CREDIT_CARD_BIN, $creditCardBin);
    }

    /**
     * @return int|null
     */
    public function getMaxInstallments()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::MAX_INSTALLMENTS);
    }

    /**
     * @param string|null $maxInstallments
     * @return RicardoMartins_PagBank_Api_Connect_InstallmentsInterface
     */
    public function setMaxInstallments($maxInstallments)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::MAX_INSTALLMENTS, $maxInstallments);
    }

    /**
     * @return int|null
     */
    public function getMaxInstallmentsNoInterest()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::MAX_INSTALLMENTS_NO_INTEREST);
    }

    /**
     * @param int|null $maxInstallmentsNoInterest
     * @return RicardoMartins_PagBank_Api_Connect_InstallmentsInterface
     */
    public function setMaxInstallmentsNoInterest($maxInstallmentsNoInterest)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::MAX_INSTALLMENTS_NO_INTEREST, $maxInstallmentsNoInterest);
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::VALUE);
    }

    /**
     * @param float|int $value
     * @return RicardoMartins_PagBank_Api_Connect_InstallmentsInterface
     */
    public function setValue($value)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_InstallmentsInterface::VALUE, $this->convertAmountToCents($value));
    }

    /**
     * @param $amount
     * @return int
     */
    private function convertAmountToCents($amount)
    {
        return (int) round($amount * 100);
    }
}
