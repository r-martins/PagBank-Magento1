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
            /** @var RicardoMartins_PagBank_Helper_Data $helper */
            $helper = Mage::helper('ricardomartins_pagbank');
            $pretty = ($this->getRequest()->getParam('pretty', true) && version_compare(PHP_VERSION, '5.4', '>='))?128:0;

            $info['RicardoMartins_PagBank']['version'] = (string)Mage::getConfig()
                ->getModuleConfig('RicardoMartins_PagBank')->version;

            $info['RicardoMartins_PagBank']['both_modules'] = true;
            $info['RicardoMartins_PagBank']['connect_key'] = strlen($helper->getConnectKey()) == 40 ? 'Good' : 'Wrong size';
            $info['RicardoMartins_PagBank']['key_validate'] = $helper->validateKey();
            $info['RicardoMartins_PagBank']['debug'] = Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/debug');
            $info['RicardoMartins_PagBank']['sandbox'] = Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/sandbox');
            $info['RicardoMartins_PagBank']['settings'] = $helper->getConfig();

            $info['compilation'] = $helper->getCompilerState();


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

            $info['RicardoMartins_PagBank']['version'] = (string)Mage::getConfig()
                ->getModuleConfig('RicardoMartins_PagBank')->version;

            $info['RicardoMartins_PagBank']['both_modules'] = false;
            $info['RicardoMartins_PagBank']['connect_key'] = strlen($helper->getConnectKey()) == 40 ? 'Good' : 'Wrong size';
            $info['RicardoMartins_PagBank']['key_validate'] = $helper->validateKey();
            $info['RicardoMartins_PagBank']['debug'] = Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/debug');
            $info['RicardoMartins_PagBank']['sandbox'] = Mage::getStoreConfigFlag('payment/ricardomartins_pagbank/sandbox');
            $info['RicardoMartins_PagBank']['settings'] = $helper->getConfig();

            $info['compilation'] = $helper->getCompilerState();


            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($info, $pretty));
        }
    }
}