<?php

/**
 * Comment for PIX expiration_time when Connect Key is Vindi.
 */
class RicardoMartins_PagBank_Model_System_Config_PixExpirationComment
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @param mixed $currentValue
     * @return string
     */
    public function getCommentText($element, $currentValue)
    {
        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if (!$detector->isVindi()) {
            return '';
        }

        return RicardoMartins_PagBank_Model_System_Config_VindiAdminNotes::visualExpiryNote('PIX');
    }
}
