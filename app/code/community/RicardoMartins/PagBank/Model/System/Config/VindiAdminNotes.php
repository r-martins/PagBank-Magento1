<?php

/**
 * Shared Vindi admin notes for Magento system.xml comment models (Woo parity).
 */
class RicardoMartins_PagBank_Model_System_Config_VindiAdminNotes
{
    /** Same URL used by Woo PIX/boleto Vindi notes */
    const SALES_SETTINGS_URL = 'https://portal.vindi.com.br/pt-br/settings/sales';

    /**
     * @param string $methodLabel e.g. "PIX" or "boleto"
     * @return string HTML
     */
    public static function visualExpiryNote($methodLabel)
    {
        $url = self::SALES_SETTINGS_URL;
        return '<em>Obs.: o valor acima é apenas visual. A real validade do '
            . htmlspecialchars((string) $methodLabel, ENT_QUOTES, 'UTF-8')
            . ' é configurada <a href="' . $url . '" target="_blank" rel="noopener noreferrer">aqui</a> '
            . '(Portal Vindi).</em>';
    }

    /**
     * @return string HTML
     */
    public static function boletoInstructionsNote()
    {
        return 'As linhas de instrução e logo podem ser configuradas '
            . '<a href="' . self::SALES_SETTINGS_URL . '" target="_blank" rel="noopener noreferrer">aqui</a> '
            . '(Portal Vindi).';
    }

    /**
     * @return string HTML
     */
    public static function softDescriptorNote()
    {
        return 'Com Connect Key Vindi, o nome na fatura é configurado no '
            . '<a href="' . self::SALES_SETTINGS_URL . '" target="_blank" rel="noopener noreferrer">Portal Vindi</a> '
            . '(Configurações → Métodos de Pagamento → Cartão de Crédito).';
    }
}
