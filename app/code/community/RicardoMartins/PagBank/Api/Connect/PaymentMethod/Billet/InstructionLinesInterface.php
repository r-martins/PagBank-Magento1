<?php

/**
 * Interface InstructionLinesInterface - Payment instructions lines.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_PaymentMethod_Billet_InstructionLinesInterface
{
    /**
     * Payment instructions line 1.
     * Receives a string.
     * Characters limit: 75 characters.
     */
    const INSTRUCTION_LINE_ONE = 'line_1';

    /**
     * Payment instructions line 2.
     * Receives a string.
     * Characters limit: 75 characters.
     */
    const INSTRUCTION_LINE_TWO = 'line_2';
}
