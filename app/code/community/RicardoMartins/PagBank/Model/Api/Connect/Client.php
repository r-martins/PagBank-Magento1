<?php

class RicardoMartins_PagBank_Model_Api_Connect_Client
{
    /**
     * Place a POST request to the PagBank API
     *
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
     * Place a GET request to the PagBank API
     *
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
     * Place a request to the PagBank API
     *
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

        $helper->writeLog(
            sprintf('Sending request to %s with the parameters: %s', $endpoint, $paramsString)
        );

        $ch = curl_init();

        if ($type == CURLOPT_HTTPGET) {
            $paramsString = http_build_query($params);
            $endpoint = strpos($endpoint, '?') === false ? "{$endpoint}?" : "{$endpoint}&";
            $endpoint = $endpoint . $paramsString;
        }

        if ($type == CURLOPT_POST) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);
        }

        curl_setopt($ch, $type, count($params));
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $helper->getHeaders());
        $response = '';

        try {
            $response = curl_exec($ch);
            $helper->writeLog(
                sprintf('Response from the request to %s: %s', $endpoint, $response)
            );
        } catch (Exception $e) {
            $helper->writeLog(
                sprintf('Failure in communication with PagBank: %s', $e->getMessage())
            );
            Mage::throwException(
                sprintf('Falha na comunicação com o PagBank: %s', $e->getMessage())
            );
        }

        if (curl_error($ch)) {
            $helper->writeLog(
                sprintf('Failure when trying to send parameters to PagBank: %s (%s)', curl_error($ch), curl_errno($ch))
            );
            Mage::throwException(
                sprintf('Falha ao tentar enviar parametros ao PagBank: %s (%s)', curl_error($ch), curl_errno($ch))
            );
        }

        $response = json_decode($response, true);

        $isSandbox = false;
        $parsedParams = parse_url($endpoint, PHP_URL_QUERY);
        if ($parsedParams) {
            parse_str($parsedParams, $parsedStr);
            $isSandbox = (bool)$parsedStr['isSandbox'];
        }

        $response['is_sandbox'] = $isSandbox;

        $info = curl_getinfo($ch);
        $httpCode = $info['http_code'];
        if ($httpCode != 200 && $httpCode != 201) {
            if ($response['error_messages']) {
                $errors = $this->getErrors($response['error_messages']);
                $helper->writeLog(
                    sprintf('Failure when trying to send parameters to PagBank: %s', $errors)
                );
                Mage::throwException(
                    sprintf('Falha ao tentar enviar parametros ao PagBank: %s', $errors)
                );
            }

            $helper->writeLog(
                sprintf('Failure when trying to send parameters to PagBank: %s', $response)
            );
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