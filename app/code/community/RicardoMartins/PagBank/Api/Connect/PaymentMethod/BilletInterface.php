<?php

/**
 * Interface BilletInterface - Billet payment object.
 * @see https://dev.pagbank.uol.com.br/reference/objeto-order
 */
interface RicardoMartins_PagBank_Api_Connect_PaymentMethod_BilletInterface
{
    /**
     * Payment due date.
     * Receives a string in the format: yyyy-MM-dd
     * Characters limit: 10
     */
    const DUE_DATE = 'due_date';

    /**
     * Billet payment instructions.
     * Receives an array with the following keys:
     * - line_1: string
     * - line_2: string
     * @see RicardoMartins_PagBank_Api_Connect_PaymentMethod_InstructionLinesInterface
     */
    const INSTRUCTION_LINES = 'instruction_lines';

    /**
     * Contains the billet holder data.
     * @see _RicardoMartins_PagBank_Api_Connect_HolderInterface
     */
    const HOLDER = 'holder';

    /**
     * Customer billing address
     * @see _RicardoMartins_PagBank_Api_Connect_AddressInterface
     */
    const HOLDER_ADDRESS = 'address';
}
