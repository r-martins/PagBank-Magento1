<?php

class RicardoMartins_PagBank_Model_Api_Connect_Client
{
    /**
     * @param $endpoint
     * @param $params
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function placePostRequest($endpoint, $params)
    {
        return $this->placeRequest(CURLOPT_POST, $endpoint, $params);
    }

    /**
     * @param $endpoint
     * @param $params
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function placeGetRequest($endpoint, $params)
    {
        return $this->placeRequest(CURLOPT_HTTPGET, $endpoint, $params);
    }

    /**
     * @param $type
     * @param $endpoint
     * @param $params
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function placeRequest($type, $endpoint, $params = [])
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $paramsString = json_encode($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, $type, count($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $helper->getHeaders());
        $response = '';

        try {
            $response = curl_exec($ch);
        } catch (Exception $e) {
            Mage::throwException('Falha na comunicação com o PagBank (' . $e->getMessage() . ')');
        }

        if (curl_error($ch)) {
            Mage::throwException(
                sprintf('Falha ao tentar enviar parametros ao PagBank: %s (%s)', curl_error($ch), curl_errno($ch))
            );
        }

        $response = json_decode($response, true);
        $info = curl_getinfo($ch);
        $httpCode = $info['http_code'];
        if ($httpCode != 200 && $httpCode != 201) {
            if ($response['error_messages']) {
                $errors = $this->getErrors($response['error_messages']);
                Mage::throwException(
                    sprintf('Falha ao tentar enviar parametros ao PagBank: %s', $errors)
                );
            }
            Mage::throwException(
                sprintf('Falha ao tentar enviar parametros ao PagBank: %s', $response)
            );
        }

        curl_close($ch);

        return $response;
    }

    /**
     * @param $errors
     * @return string
     */
    private function getErrors($errors)
    {
        $errors = array_map(function ($error) {
            return $error['description'] . ' (' . $error['parameter_name'] . ')';
        }, $errors);
        return implode(', ', $errors);
    }
}