<?php
$controllerPath = Mage::getModuleDir('controllers', 'RicardoMartins_PagSeguro').DS.'TestController.php';

if (Mage::helper('core')->isModuleEnabled('RicardoMartins_PagSeguro') && file_exists($controllerPath)) {
    require_once $controllerPath;
}

if (class_exists('RicardoMartins_PagSeguro_TestController')) {
    class RicardoMartins_PagBank_TestController extends RicardoMartins_PagSeguro_TestController
    {
        public function getConfigAction()
        {
            $info = [];
            $pretty = ($this->getRequest()->getParam('pretty', true) && version_compare(PHP_VERSION, '5.4', '>='))?128:0;

            /** @var RicardoMartins_PagBank_Helper_Data $helperPagbank */
            $helperPagbank = Mage::helper('ricardomartins_pagbank');

            try {
                parent::getConfigAction();
                $info = $helperPagbank->getModuleConfigInfo();
                $getConfigPagseguro = json_decode($this->getResponse()->getBody(), true);
                $info['legacy_module'] = $getConfigPagseguro;
            } catch (Exception $e) {
                $info['error'] = $e->getMessage();
            }

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($info, $pretty));
        }
    }
} else {
    // Handle the case when RicardoMartins_PagSeguro_TestController does not exist
    class RicardoMartins_PagBank_TestController extends Mage_Core_Controller_Front_Action
    {
        public function getConfigAction()
        {
            $info = [];
            /** @var RicardoMartins_PagBank_Helper_Data $helper */
            $helper = Mage::helper('ricardomartins_pagbank');
            $pretty = ($this->getRequest()->getParam('pretty', true) && version_compare(PHP_VERSION, '5.4', '>='))?128:0;

            try {
                $info = $helper->getModuleConfigInfo();
            } catch (Exception $e) {
                $info['error'] = $e->getMessage();
            }

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($info, $pretty));
        }
    }
}