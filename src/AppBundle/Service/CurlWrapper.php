<?php
/**
 * CurlWrapper
 *
 * Wrapper class for cURL requests
 */

namespace AppBundle\Service;

class CurlWrapper {
    private $http_status_code;
    private $http_headers;
    private $access_token;
    private $cache;
    private $cache_hits;
    private $cache_misses;

    /**
     * Make a HTTP request
     *
     * @param string $uri
     *   The URI of the call excluding the hostname
     * @param array $fields
     *   (optional) An associative array of key-value pairs to be sent with the request
     * @param string $method
     *   (optional) get, post, put, or delete. Default get.
     * @param bypass_cache
     *   (optional) bypass cached values for get requests. Default false.
     */
    public function request($uri, $fields = array(), $method = 'get', $bypass_cache = false) {

        // Check cache for result
        if ($method == 'get') {
            $cache_hash = md5($uri . serialize($fields));
            if ($bypass_cache == FALSE && !empty($this->cache[$cache_hash])) {
                $this->cache_hits++;
                return $this->cache[$cache_hash];
            }
            else {
                $this->cache_misses++;
            }
        }

        // Make the cURL request
        $ch = curl_init();
        if (empty($fields['access_token']) && $uri != 'users/login') {
            if (!empty($this->access_token)) {
                $fields['access_token'] = $this->access_token;
            }
            elseif (!empty($_SESSION['token'])) {
                $fields['access_token'] = $_SESSION['token'];
            }
        }

        if ($method == 'get') {
            $uri .= '?' . http_build_query($fields);
        }

        $curlConfig = array(
            CURLOPT_URL             => $uri,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_HTTPHEADER      => array('Accept: application/json', 'Expect:'),
            CURLOPT_VERBOSE         => 1,
            CURLOPT_HEADER          => 1,
        );

        if ($method == 'post') {
            $curlConfig[CURLOPT_POST] = 1;
            $curlConfig[CURLOPT_POSTFIELDS] = $fields;
        }

        if ($method == 'put') {
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $curlConfig[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }

        if ($method == 'delete') {
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            $curlConfig[CURLOPT_POSTFIELDS] = $fields;
        }

        // allow @ for file uploads
        // @todo: do it the new safe way
        if (version_compare('5.6', phpversion()) < 1) {
            $curlConfig[CURLOPT_SAFE_UPLOAD] = false;               
        }

        curl_setopt_array($ch, $curlConfig);
        $result = curl_exec($ch);
        list($header_blob, $body) = explode("\r\n\r\n", $result, 3);

        $headers = explode("\n", $header_blob);
        $this->http_headers = array();
        foreach ($headers as $header) {
            if (strstr($header, ': ') === FALSE) {
                $this->http_headers[$header] = $header;
            }
            else {
                list($key, $val) = explode(': ', $header, 2);
                $this->http_headers[$key] = $val;
            }
        }

        $this->http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Cache the result
        if ($method == 'get') {
            $this->cache[$cache_hash] = $body;
        }

        return $body;
    }

    /**
     * Get the number of cache hits and misses
     */
    public function cache_status() {
        return array(
            'hits' => $this->cache_hits,
            'misses' => $this->cache_misses,
            'cache' => $this->cache,
        );
    }

    /**
     * Get the http status code of the last call
     */
    public function http_status_code() {
        return $this->http_status_code;
    }

    /**
     * Get the http headers of the last call
     */
    public function http_headers() {
        return $this->http_headers;
    }

    /**
     * Initialize the class
     */
    function __construct() {
        $this->cache = array();
        $this->cache_hits = 0;
        $this->cache_misses = 0;
        $this->http_status_code = 0;
        $this->http_headers = array();
    }
}
