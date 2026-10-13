<?php

/**
 * Server-side proxies for Vindi tokenize/3DS via API PB v1.
 */
class RicardoMartins_PagBank_Model_Vindi_Ajax
{
    /**
     * @return array
     */
    public function jsonBody()
    {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return is_array($_POST) ? $_POST : array();
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function assertVindi()
    {
        $detector = RicardoMartins_PagBank_Model_Partner_Detector::fromSettings();
        if (!$detector->isVindi()) {
            Mage::throwException(Mage::helper('ricardomartins_pagbank')->__('Operação Vindi indisponível para esta Connect Key.'));
        }
    }

    /**
     * @param array $body
     * @return array
     * @throws Mage_Core_Exception
     */
    public function createPaymentProfile(array $body)
    {
        $this->assertVindi();
        $token = isset($body['gateway_token']) ? (string) $body['gateway_token'] : '';
        $company = isset($body['payment_company_code']) ? (string) $body['payment_company_code'] : 'mastercard';
        $checkout = isset($body['checkout']) && is_array($body['checkout']) ? $body['checkout'] : array();
        if ($token === '') {
            Mage::throwException(Mage::helper('ricardomartins_pagbank')->__('Token do cartão ausente.'));
        }

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        $payload = array(
            'gateway_token' => $token,
            'payment_company_code' => $company,
            'payment_method_code' => 'credit_card',
            'customer' => $this->buildCustomerFromCheckout($checkout),
        );

        $resp = $api->placePostRequest($helper->getPaymentProfilesEndpoint(), $payload);
        $id = isset($resp['payment_profile']['id'])
            ? $resp['payment_profile']['id']
            : (isset($resp['id']) ? $resp['id'] : null);
        if (!$id) {
            Mage::throwException(Mage::helper('ricardomartins_pagbank')->__('Não foi possível criar o perfil de pagamento.'));
        }

        return array(
            'payment_profile_id' => (int) $id,
            'customer_id' => (int) (
                isset($resp['customer_id'])
                    ? $resp['customer_id']
                    : (isset($resp['payment_profile']['customer_id']) ? $resp['payment_profile']['customer_id'] : 0)
            ),
            'payment_profile' => isset($resp['payment_profile']) ? $resp['payment_profile'] : $resp,
        );
    }

    /**
     * @param array $body
     * @return array
     * @throws Mage_Core_Exception
     */
    public function threeDsSetup(array $body)
    {
        $this->assertVindi();
        $profileId = isset($body['payment_profile_id']) ? (int) $body['payment_profile_id'] : 0;
        if ($profileId <= 0) {
            Mage::throwException(Mage::helper('ricardomartins_pagbank')->__('payment_profile_id inválido.'));
        }

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        $resp = $api->placePostRequest($helper->getThreeDsEndpoint('setup'), array(
            'payment_profile_id' => $profileId,
            'payment_method_code' => 'credit_card_3ds',
        ));
        $setup = isset($resp['setup']) ? $resp['setup'] : $resp;
        if (empty($setup['session_id']) || empty($setup['access_token'])) {
            Mage::throwException(Mage::helper('ricardomartins_pagbank')->__('Resposta de setup 3DS inválida.'));
        }
        return array('setup' => $setup);
    }

    /**
     * @param array $body
     * @return array
     * @throws Mage_Core_Exception
     */
    public function threeDsEnroll(array $body)
    {
        $this->assertVindi();
        $sessionId = isset($body['session_id']) ? (string) $body['session_id'] : '';
        $amount = isset($body['amount']) ? (int) $body['amount'] : 0;
        $installments = max(1, isset($body['installments']) ? (int) $body['installments'] : 1);
        $device = isset($body['device_info']) && is_array($body['device_info']) ? $body['device_info'] : array();
        $checkout = isset($body['checkout']) && is_array($body['checkout']) ? $body['checkout'] : array();

        if ($sessionId === '' || $amount < 100) {
            Mage::throwException(Mage::helper('ricardomartins_pagbank')->__('Dados de enroll incompletos.'));
        }

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        $payload = $this->buildEnrollPayload($sessionId, $amount, $installments, $device, $checkout);
        $resp = $api->placePostRequest($helper->getThreeDsEndpoint('enroll'), $payload);
        $enroll = isset($resp['enroll']) ? $resp['enroll'] : $resp;
        if (is_array($enroll) && isset($enroll['enroll']) && is_array($enroll['enroll'])) {
            $enroll = $enroll['enroll'];
        }
        return array('enroll' => $enroll, 'raw' => $resp);
    }

    /**
     * @param array $body
     * @return array
     * @throws Mage_Core_Exception
     */
    public function threeDsValidate(array $body)
    {
        $this->assertVindi();
        $txid = isset($body['authentication_transaction_id'])
            ? (string) $body['authentication_transaction_id']
            : '';
        $cardType = isset($body['card_type']) ? (string) $body['card_type'] : 'visa';
        if ($txid === '') {
            Mage::throwException(Mage::helper('ricardomartins_pagbank')->__('TransactionId ausente.'));
        }

        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        /** @var RicardoMartins_PagBank_Model_Api_Connect_Client $api */
        $api = Mage::getModel('ricardomartins_pagbank/api_connect_client');

        $resp = $api->placePostRequest($helper->getThreeDsEndpoint('validate'), array(
            'authentication_transaction_id' => $txid,
            'card_type' => $cardType,
        ));
        $validate = isset($resp['validate']) ? $resp['validate'] : $resp;
        return array('validate' => $validate, 'raw' => $resp);
    }

    /**
     * @param array $checkout
     * @return array
     */
    public function buildCustomerFromCheckout(array $checkout)
    {
        $name = isset($checkout['customerName']) ? trim((string) $checkout['customerName']) : '';
        if ($name === '') {
            $name = 'Cliente';
        }
        $email = isset($checkout['email']) ? strtolower(trim((string) $checkout['email'])) : '';
        /** @var RicardoMartins_PagBank_Helper_Data $helper */
        $helper = Mage::helper('ricardomartins_pagbank');
        $taxId = $helper->normalizeDocument(isset($checkout['tax_id']) ? $checkout['tax_id'] : '');
        $phone = isset($checkout['phone']) ? preg_replace('/\D+/', '', (string) $checkout['phone']) : '';
        if ($phone !== '' && strpos($phone, '55') !== 0) {
            $phone = '55' . $phone;
        }

        return array(
            'name' => $name,
            'email' => $email,
            'registry_code' => $taxId,
            'mobile_phone' => $phone,
        );
    }

    /**
     * @param string $sessionId
     * @param int $amountCents
     * @param int $installments
     * @param array $device
     * @param array $checkout
     * @return array
     */
    public function buildEnrollPayload($sessionId, $amountCents, $installments, array $device, array $checkout)
    {
        $customer = $this->buildCustomerFromCheckout($checkout);
        $name = $customer['name'];
        $email = $customer['email'];
        $taxId = $customer['registry_code'];
        $phone = $customer['mobile_phone'];
        $street = isset($checkout['street']) ? trim((string) $checkout['street']) : '';
        $number = isset($checkout['number']) ? (string) $checkout['number'] : 'S/N';
        $city = isset($checkout['city']) ? (string) $checkout['city'] : '';
        $state = isset($checkout['regionCode']) ? (string) $checkout['regionCode'] : '';
        $postcode = isset($checkout['postalCode'])
            ? preg_replace('/\D+/', '', (string) $checkout['postalCode'])
            : '';

        $ip = isset($device['ip_address']) ? (string) $device['ip_address'] : $this->clientIp();
        $ua = isset($device['user_agent'])
            ? (string) $device['user_agent']
            : (isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0');

        $returnUrl = Mage::getUrl('pagbank/ajax/vindi3dsreturn', array('_secure' => true));

        return array(
            'session_id' => $sessionId,
            'amount' => $amountCents,
            'currency' => 'BRL',
            'installments' => $installments,
            'return_url' => $returnUrl,
            'client_reference_code' => 'm1_' . time(),
            'device_info' => array(
                'ip_address' => $ip,
                'user_agent' => $ua,
                'screen_height' => isset($device['screen_height']) ? (int) $device['screen_height'] : 1080,
                'screen_width' => isset($device['screen_width']) ? (int) $device['screen_width'] : 1920,
                'color_depth' => isset($device['color_depth']) ? (int) $device['color_depth'] : 24,
                'timezone_offset' => isset($device['timezone_offset']) ? (int) $device['timezone_offset'] : -180,
                'language' => isset($device['language']) ? (string) $device['language'] : 'pt-BR',
                'java_enabled' => !empty($device['java_enabled']),
            ),
            'billing_address' => array(
                'name' => $name,
                'email' => $email !== '' ? $email : 'cliente@exemplo.test',
                'street' => $street !== '' ? $street . ($number !== '' ? ', ' . $number : '') : 'Nao informado',
                'city' => $city !== '' ? $city : 'Sao Paulo',
                'state' => $state !== '' ? $state : 'SP',
                'postal_code' => $postcode !== '' ? $postcode : '01310100',
                'country' => 'BR',
            ),
            'buyer_information' => array(
                'registry_code' => $taxId !== '' ? $taxId : '00000000000',
                'mobile_phone' => $phone !== '' ? $phone : '5511999999999',
            ),
            'merchant_information' => array(
                'merchant_name' => substr(Mage::app()->getStore()->getFrontendName(), 0, 40) ?: 'Loja',
                'url' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            ),
        );
    }

    /**
     * @return string
     */
    private function clientIp()
    {
        foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
            if (!empty($_SERVER[$key])) {
                $parts = explode(',', (string) $_SERVER[$key]);
                return trim($parts[0]);
            }
        }
        return '127.0.0.1';
    }
}
