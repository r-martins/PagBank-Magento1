<?php

class RicardoMartins_PagBank_Model_System_Config_Source_Order_Installments
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'external',
                'label' => Mage::helper('ricardomartins_pagbank')->__('Follow PagBank account settings (default)')
            ],
            [
                'value' => 'buyer',
                'label' => Mage::helper('ricardomartins_pagbank')->__('Interest paid by the buyer')
            ],
            [
                'value' => 'fixed',
                'label' => Mage::helper('ricardomartins_pagbank')->__('Up to X interest-free installments')
            ],
            [
                'value' => 'min_total',
                'label' => Mage::helper('ricardomartins_pagbank')->__('Up to X interest-free installments depending on the amount of the installment')
            ]
        ];
    }
}
