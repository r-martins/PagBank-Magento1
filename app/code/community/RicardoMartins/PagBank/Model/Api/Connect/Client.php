<?php

class RicardoMartins_PagBank_Model_Api_Connect_Client
{
    /**
     * Place a POST request to the gateway API
     *
     * @param string $endpoint
     * @param array $params
     * @param bool $log Unused when debug is on — request/response always logged then
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function placePostRequest($endpoint, $params = [], $log = true)
    {
        return $this->placeRequest(CURLOPT_POST, $endpoint, $params, $log);
    }

    /**
     * Place a GET request to the gateway API
     *
     * @param string $endpoint
     * @param array $params
     * @param bool $log Unused when debug is on — request/response always logged then
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function placeGetRequest($endpoint, $params = [], $log = true)
    {
        return $this->placeRequest(CURLOPT_HTTPGET, $endpoint, $params, $log);
    }

    /**
     * Place a request to the gateway API
     *
     * @param int $type CURLOPT_POST|CURLOPT_HTTPGET
     * @param string $endpoint
     * @param array $params
     * @param bool $log
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function placeRequest($type, $endpoint, $params = [], $log = true)
    {
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        if (!is_array($params)) {
            $params = array();
        }

        $method = ($type == CURLOPT_HTTPGET) ? 'GET' : 'POST';
        $bodyJson = $params ? json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $requestUrl = $endpoint;

        $ch = curl_init();

        if ($type == CURLOPT_HTTPGET) {
            $query = http_build_query($params);
            if ($query !== '') {
                $requestUrl .= (strpos($endpoint, '?') === false ? '?' : '&') . $query;
            }
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        if ($type == CURLOPT_POST) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson !== '' ? $bodyJson : '[]');
            curl_setopt($ch, CURLOPT_POST, true);
        }

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $helper->shouldVerifySsl() ? 2 : 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $helper->shouldVerifySsl() ? 1 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $helper->getHeaders());

        $shouldLog = $helper->isDebugActive() || $log;
        if ($shouldLog) {
            $helper->writeLog(sprintf(
                '[%s] %s%s',
                $method,
                $requestUrl,
                $bodyJson !== '' ? "\nBody: " . $bodyJson : ''
            ));
        }

        $rawResponse = '';
        try {
            $rawResponse = curl_exec($ch);
        } catch (Exception $e) {
            $helper->writeLog('Failure in communication with the gateway: ' . $e->getMessage());
            Mage::throwException(
                $helper->__('Failure in communication with the gateway: %s', $e->getMessage())
            );
        }

        $curlErr = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $info = curl_getinfo($ch);
        $httpCode = isset($info['http_code']) ? (int) $info['http_code'] : 0;

        if ($curlErr) {
            $helper->writeLog(sprintf(
                '[%s] %s — cURL error %s: %s',
                $method,
                $requestUrl,
                $curlErrno,
                $curlErr
            ));
            throw new \Mage_Payment_Model_Info_Exception(
                $helper->__('Failure when trying to send parameters to the gateway: %s (%s)', $curlErr, $curlErrno)
            );
        }

        if ($shouldLog) {
            $helper->writeLog(sprintf(
                '[%s] %s — HTTP %s\nResponse: %s',
                $method,
                $requestUrl,
                $httpCode,
                is_string($rawResponse) ? $rawResponse : var_export($rawResponse, true)
            ));
        }

        $response = json_decode((string) $rawResponse, true);
        if (!is_array($response)) {
            $response = array();
        }

        $isSandbox = false;
        $parsedParams = parse_url($requestUrl, PHP_URL_QUERY);
        if ($parsedParams) {
            parse_str($parsedParams, $parsedStr);
            $isSandbox = isset($parsedStr['isSandbox']) && $parsedStr['isSandbox'] == 1;
        }
        if (!$isSandbox && $helper->isSandbox()) {
            $isSandbox = true;
        }

        $response['is_sandbox'] = $isSandbox;

        if ($httpCode != 200 && $httpCode != 201) {
            $errors = '';
            if (!empty($response['error_messages'])) {
                $errors = $helper->handleErrorMessages($response['error_messages']);
                $helper->writeLog('API error: ' . $errors);
                throw new \Mage_Payment_Model_Info_Exception(
                    $helper->__('Failure when trying to send parameters to the gateway: %s', $errors)
                );
            }

            if (!empty($response['message'])) {
                $errors = $response['message'];
                $helper->writeLog('API error: ' . $errors);
                Mage::throwException(
                    $helper->__('Failure when trying to send parameters to the gateway: %s', $errors)
                );
            }
            if ($httpCode == 401) {
                $errors = $helper->__('Invalid or expired Connect Key');
                $helper->writeLog('API error: ' . $errors);
                throw new \Mage_Payment_Model_Info_Exception(
                    $helper->__('Failure when trying to send parameters to the gateway: %s', $errors)
                );
            }

            $errors = var_export($response, true);
            $helper->writeLog('API error (HTTP ' . $httpCode . '): ' . $errors);
            throw new \Mage_Payment_Model_Info_Exception(
                $helper->__('Failure when trying to send parameters to the gateway: see pbintegracoes.log')
            );
        }

        return $response;
    }
}
