<?php

/**
 * Dynamic comment for payment/ricardomartins_pagbank_cc/cc_3ds.
 * Vindi without credit_card_3ds: show ticket CTA (Woo parity).
 */
class RicardoMartins_PagBank_Model_System_Config_Cc3dsComment
{
    const VINDI_TICKET_URL = 'https://vindi.com.br/atendimento/abrir-chamado/';

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @param mixed $currentValue
     * @return string
     */
    public function getCommentText($element, $currentValue)
    {
        $docs = 'https://ajuda.pbintegracoes.com/hc/pt-br/articles/22375922278157-Autentica%C3%A7%C3%A3o-3DS-Sua-prote%C3%A7%C3%A3o-contra-Chargeback';
        $base = 'Habilita a autenticação 3D Secure para compras com cartão de crédito. '
            . 'A autenticação 3D Secure é um protocolo de segurança que adiciona uma camada extra de proteção '
            . 'para compras online, e evita que chargebacks de compras não reconhecidas sejam cobrados do lojista. '
            . 'Para mais informações, consulte a <a href="' . $docs . '" target="_blank" rel="noopener">documentação</a>.';

        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if (!$detector->isVindi()) {
            return $base;
        }

        $caps = new RicardoMartins_PagBank_Model_Partner_Capabilities($detector);
        if ($caps->has3ds()) {
            return $base;
        }

        $ticket = '<a href="' . self::VINDI_TICKET_URL . '" target="_blank" rel="noopener noreferrer">Abra um chamado</a>';

        return '⚠️ <strong>3D Secure não está ativo em sua conta Vindi.</strong> ' . $ticket . ' para solicitar.';
    }
}
