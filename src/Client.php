<?php

namespace Mvaessen\KrakenFutureApi;

/**
 * Reference implementation for Kraken's Future REST API.
 *
 * See https://support.kraken.com/hc/en-us/categories/360001806372-Futures-API for more info.
 *
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2019 Max Vaessen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class KrakenFutureApiException extends \ErrorException{};

class Client
{
    private $key;
    private $secret;

    protected $url;
    protected $version;
    protected $curl;

    /**
     * Client constructor.
     *
     * @param        $key
     * @param        $secret
     * @param string $url
     * @param string $version
     * @param bool   $sslverify
     */
    public function __construct(
        $key,
        $secret,
        $url = 'https://futures.kraken.com/derivatives',
        $version = 'v3',
        $sslverify = true
    ) {
        $this->key = $key;
        $this->secret = $secret;
        $this->url = $url;
        $this->version = $version;
        $this->curl = curl_init();

        curl_setopt_array($this->curl, array(
            CURLOPT_SSL_VERIFYPEER => $sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Kraken Future PHP API wrapper',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20
        ));
    }

    /**
     * @param       $method
     * @param       $url
     * @param array $request
     *
     * @return mixed|void
     * @throws KrakenFutureApiException
     */
    public function queryPublic(
        $method,
        $url,
        array $request = array()
    ) {
        try {
            return $this->request(
                $method,
                $url,
                $request,
                false
            );
        } catch (KrakenFutureApiException $e) {
            return $this->processException($e, $method, $url, $request);
        }
    }

    /**
     * @param       $method
     * @param       $url
     * @param array $request
     *
     * @return mixed|void
     * @throws KrakenFutureApiException
     */
    public function queryPrivate(
        $method,
        $url,
        array $request = array()
    ) {
        try {
            return $this->request(
                $method,
                $url,
                $request,
                true
            );
        } catch (KrakenFutureApiException $e) {
            return $this->processException($e, $method, $url, $request);
        }
    }

    /**
     * @param $response
     * @param $method
     * @param $url
     * @param $request
     *
     * @throws KrakenFutureApiException
     */
    protected function processErrorCode($response, $method, $url, $request)
    {
        throw new KrakenFutureApiException($response['msg']);
    }

    /**
     * @param $e
     * @param $method
     * @param $url
     * @param $request
     *
     * @throws KrakenFutureApiException
     */
    protected function processException($e, $method, $url, $request)
    {
        throw new KrakenFutureApiException($e->getMessage());
    }

    /**
     * @param       $method
     * @param       $url
     * @param array $request
     * @param bool  $signed
     *
     * @return mixed|void
     * @throws KrakenFutureApiException
     */
    private function request(
        $method,
        $url,
        array $request = array(),
        $signed = false
    ) {
        $postdata = http_build_query($request, '', '&');

        //determin & set request path
        $path = '/api/' . $this->version . '/' . $url;

        if (! isset($request['nonce'])) {
            // generate a 64 bit nonce using a timestamp at microsecond resolution
            // string functions are used to avoid problems on 32 bit systems
            $nonce = explode(' ', microtime());
            $request['nonce'] = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');
        }

        $sign = hash_hmac(
            'sha512',
            hash('sha256', $postdata . $request['nonce'] . $path, true),
            base64_decode($this->secret),
            true);

        $headers = array(
            'apiKey: ' . $this->key,
            'nonce: ' . $request['nonce'],
            'authent: ' . base64_encode($sign)
        );

        if ($postdata) {
            $path .= '?' . $postdata;
        }

        // make request
        curl_setopt($this->curl, CURLOPT_URL, $this->url . $path);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        //set method
        switch ($method) {
            case 'POST':
            case 'post':
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);
                curl_setopt($this->curl, CURLOPT_POST, true);
                break;

            case 'DELETE':
            case 'delete':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'GET':
            case 'get':
                break;

            default:
                throw new KrakenFutureApiException('Unsupported method');
        }

        //execute request
        $result = curl_exec($this->curl);
        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($result === false) {
            throw new KrakenFutureApiException('CURL error: ' . curl_error($this->curl), $httpcode);
        }

        if ($httpcode != 200) {
            throw new KrakenFutureApiException($result, $httpcode);
        }

        // decode results
        $output = json_decode($result, true);
        if (! is_array($output)) {
            throw new KrakenFutureApiException('JSON decode error');
        }

        if (isset($output['code'])) {
            return $this->processErrorCode($output, $method, $url, $request);
        }

        return $output;
    }
}