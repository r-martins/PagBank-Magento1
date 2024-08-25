<?php

class RicardoMartins_PagBank_Model_System_Config_Comment
{
    public function getCommentText($element, $currentValue)
    {
        if (!$currentValue) {
            return '';
        }

        if (strpos($currentValue, 'CONSANDBOX') !== false) {
            return '⚠️ Você está usando o <strong>modo de testes</strong>. Veja <a href="https://dev.pagbank.uol.com.br/reference/simulador" target="_blank">documentação</a>.' .
                '<br/>Para usar o modo de produção, altere suas credenciais.' .
                '<br/>Lembre-se: pagamentos em Sandbox não aparecerão em seu painel, mesmo no ambiente Sandbox.';
        }

        return '';
    }
}
