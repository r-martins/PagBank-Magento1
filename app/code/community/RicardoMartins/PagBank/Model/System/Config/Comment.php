<?php

class RicardoMartins_PagBank_Model_System_Config_Comment
{
    public function getCommentText($element, $currentValue)
    {
        if (!$currentValue) {
            return 'Aceita Connect Key PagBank (<code>CON…</code>/<code>CONSANDBOX…</code>) ou Vindi (<code>CONVD…</code>/<code>CONVDSANDBOX…</code>).';
        }

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $detector = new RicardoMartins_PagBank_Model_Partner_Detector($currentValue);

        $parts = array();

        if ($detector->isSandbox()) {
            if ($detector->isVindi()) {
                $parts[] = '⚠️ Você está usando o <strong>modo de testes Vindi</strong>.'
                    . ' Pedidos neste ambiente não aparecerão na conta de produção.'
                    . ' <a href="https://app.vindi.com.br/admin/sandbox" target="_blank">Sandbox Vindi</a>';
            } else {
                $parts[] = '⚠️ Você está usando o <strong>modo de testes</strong>. Veja <a href="https://dev.pagbank.uol.com.br/reference/simulador" target="_blank">documentação</a>.'
                    . '<br/>Pagamentos em Sandbox não aparecerão em seu painel, mesmo no ambiente Sandbox.';
            }
        }

        $info = $helper->getCachedConnectInfo($currentValue);
        $accountHtml = $helper->formatConnectInfoHtml($info, $detector);
        if ($accountHtml !== '') {
            $parts[] = $accountHtml;
        }

        if ($detector->isPagBank() && !$detector->isSandbox()) {
            $cutoff = RicardoMartins_PagBank_Model_Partner_Cutoff::SHORT;
            $url = RicardoMartins_PagBank_Model_Partner_Cutoff::MIGRATE_URL;
            $parts[] = 'Migração: migre sua Connect Key até <strong>' . $cutoff . '</strong> — '
                . '<a href="' . $url . '" target="_blank" rel="noopener">Migrar agora</a>.';
        }

        if (!$parts) {
            return 'Não foi possível obter informações da conta. Verifique a Connect Key e tente novamente.';
        }

        return implode('<br/>', $parts);
    }
}
