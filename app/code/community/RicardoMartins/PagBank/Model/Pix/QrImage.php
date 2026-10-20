<?php

/**
 * Vindi serves PIX QR SVGs from CloudFront without a Content-Type.
 * Browsers refuse that in <img>, so the store proxies the file with a MIME type.
 */
class RicardoMartins_PagBank_Model_Pix_QrImage
{
    /**
     * @param string|null $url
     * @return bool
     */
    public function needsProxy($url)
    {
        if ($url === null || $url === '') {
            return false;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        if (preg_match('/\.svgz?$/i', $path)) {
            return true;
        }

        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        if ($host === '') {
            return false;
        }

        return strpos($host, 'cloudfront.net') !== false
            || strpos($host, 'vindi.com.br') !== false
            || strpos($host, 'yapay.com.br') !== false;
    }

    /**
     * URL safe for <img src> (proxied when needed).
     *
     * @param Mage_Sales_Model_Order|null $order
     * @param string $remote
     * @return string
     */
    public function displayUrl($order, $remote)
    {
        $remote = (string) $remote;
        if ($remote === '' || !$this->needsProxy($remote) || !$order || !$order->getId() || !$order->getProtectCode()) {
            return $remote;
        }

        return Mage::app()->getStore($order->getStoreId())->getUrl('pagbank/pix/qr', array(
            'order_id' => (int) $order->getId(),
            'code' => (string) $order->getProtectCode(),
            '_nosid' => true,
            '_secure' => true,
        ));
    }

    /**
     * @param string $url
     * @return array{body:string,content_type:string}|null
     */
    public function fetch($url)
    {
        $url = (string) $url;
        if (!$this->needsProxy($url) || !$this->isHttps($url)) {
            return null;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'Magento / PagBank Integracoes PIX QR',
        ));

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $remoteType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($status < 200 || $status >= 300 || !is_string($body) || $body === '') {
            return null;
        }

        return array(
            'body' => $body,
            'content_type' => $this->detectContentType($url, $body, $remoteType),
        );
    }

    /**
     * @param string $url
     * @return bool
     */
    private function isHttps($url)
    {
        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    /**
     * @param string $url
     * @param string $body
     * @param string $remoteContentType
     * @return string
     */
    private function detectContentType($url, $body, $remoteContentType)
    {
        $trimmed = ltrim($body);
        if (strpos($trimmed, '<?xml') === 0 || strpos($trimmed, '<svg') === 0) {
            return 'image/svg+xml';
        }
        if (strpos($body, "\x89PNG") === 0) {
            return 'image/png';
        }
        if (strpos($body, "\xFF\xD8\xFF") === 0) {
            return 'image/jpeg';
        }

        $remote = strtolower(trim((string) $remoteContentType));
        if (strpos($remote, 'image/') === 0) {
            $mime = strtok($remote, ';');
            return is_string($mime) && $mime !== '' ? $mime : 'image/png';
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        if (preg_match('/\.svgz?$/i', $path)) {
            return 'image/svg+xml';
        }
        if (preg_match('/\.jpe?g$/i', $path)) {
            return 'image/jpeg';
        }

        return 'image/png';
    }
}
