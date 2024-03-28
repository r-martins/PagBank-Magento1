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
            echo 'hello rewrited'; //@TODO Implement a call to the same method
        }
    }
} else {
    // Handle the case when RicardoMartins_PagSeguro_TestController does not exist
    class RicardoMartins_PagBank_TestController extends Mage_Core_Controller_Front_Action
    {
        public function getConfigAction()
        {
            echo 'hello pagbank'; //@TODO Implement a call to the same method
        }
    }
}