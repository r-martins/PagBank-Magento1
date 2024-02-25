<?php
class RicardoMartins_PagBank_Model_System_Config_Source_Connect_Ccbrand
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '42x20','label' => '42x20px'],
            ['value' => '68x30', 'label' => '68x30px'],
            ['value' => '', 'label' => Mage::helper('ricardomartins_pagbank')->__('Display only text')]
        ];
    }
}