<?php

class RicardoMartins_PagBank_Model_Request_Object_PaymentMethod_Billet_InstructionLines extends Varien_Object implements RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface
{
    /**
     * @return string
     */
    public function getLineOne()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface::INSTRUCTION_LINE_ONE);
    }

    /**
     * @param string|null $lineOne
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface
     */
    public function setLineOne($lineOne)
    {
        $lineOne = $lineOne ? substr($lineOne, 0, 75): null;
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface::INSTRUCTION_LINE_ONE, $lineOne);
    }

    /**
     * @return string
     */
    public function getLineTwo()
    {
        return $this->getData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface::INSTRUCTION_LINE_TWO);
    }

    /**
     * @param string|null $lineTwo
     * @return RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface
     */
    public function setLineTwo($lineTwo)
    {
        $lineTwo = $lineTwo ? substr($lineTwo, 0, 75) : null;
        return $this->setData(RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface::INSTRUCTION_LINE_TWO, $lineTwo);
    }
}
