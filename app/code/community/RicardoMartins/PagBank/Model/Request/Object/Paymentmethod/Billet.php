<?php

class RicardoMartins_PagBank_Model_Request_Object_Paymentmethod_Billet extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface
{
    /**
     * @return string
     */
    public function getDueDate()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface::DUE_DATE);
    }

    /**
     * @param string $dueDate
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface
     */
    public function setDueDate($dueDate)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface::DUE_DATE, $dueDate);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface[]
     */
    public function getInstructionLines()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface::INSTRUCTION_LINES);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface[] $instructionLines
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface
     */
    public function setInstructionLines($instructionLines)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface::INSTRUCTION_LINES, $instructionLines);
    }

    /**
     * @return RicardoMartins_PagBank_Api_Connect_HolderInterface[]
     */
    public function getHolder()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface::HOLDER);
    }

    /**
     * @param RicardoMartins_PagBank_Api_Connect_HolderInterface[] $holder
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface
     */
    public function setHolder($holder)
    {
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface::HOLDER, $holder);
    }
}
