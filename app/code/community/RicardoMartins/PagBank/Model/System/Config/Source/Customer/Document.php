<?php

class RicardoMartins_PagBank_Model_System_Config_Source_Customer_Document
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'taxvat',
                'label' => Mage::helper('ricardomartins_pagbank')->__('Customer: TAX/VAT number (taxvat)')
            ),
            array(
                'value' => 'vat_id',
                'label' => Mage::helper('ricardomartins_pagbank')->__('Billing Address: VAT Number (vat_id)')
            ),
            array(
                'value' => 'payment_form',
                'label' => Mage::helper('ricardomartins_pagbank')->__('Checkout: Request in the payment form')
            ),
        );
    }
}
