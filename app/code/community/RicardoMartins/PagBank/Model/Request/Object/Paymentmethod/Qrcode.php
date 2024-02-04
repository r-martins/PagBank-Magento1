<?php

class RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Qrcode extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface
{
    /**
     * @return RicardoMartins_PagBank_Api_Connect_AmountInterface[]
     */
    public function getAmount()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface::AMOUNT);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_AmountInterface[] $amount
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface
     */
    public function setAmount(array $amount)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface::AMOUNT, $amount);
    }

    /**
     * @return string
     */
    public function getExpirationDate()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface::EXPIRATION_DATE);
    }

    /**
     * @param string $modifier
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface
     */
    public function setExpirationDate($modifier = '60')
    {
        $modifier = sprintf('+%s minutes', $modifier);

        try {
            $timezone = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
        } catch (Exception $e) {
            $timezone = 'America/Sao_Paulo';
        }

        $dateTimezone = new \DateTimeZone($timezone);

        try {
            $expirationDate = new \DateTime('now', $dateTimezone);
        } catch (Exception $e) {
            $expirationDate = new \DateTime('now');
        }

        $expirationDate->modify($modifier);

        return $this->setData(
            RicardoMartins_PagBank_Api_Connect_PaymentMethod_QrCodeInterface::EXPIRATION_DATE,
            $expirationDate->format(self::DATETIME_FORMAT)
        );
    }
}
