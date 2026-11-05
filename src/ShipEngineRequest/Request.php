<?php

namespace Yurstore\ShipEngineAPI\ShipEngineRequest;

use Yurstore\ShipEngineAPI\ShipEngineException\Exception;

class Request
{
    public const DEFAULT_CONNECT_TIMEOUT = 5;
    public const DEFAULT_TIMEOUT = 30;

    protected $curl_handle;

    protected $params;

    public function __construct(array $params)
    {
        $params += [
            CURLOPT_CONNECTTIMEOUT => self::DEFAULT_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::DEFAULT_TIMEOUT,
        ];

        foreach ([CURLOPT_CONNECTTIMEOUT, CURLOPT_TIMEOUT] as $option) {
            $timeout = is_int($params[$option]) || is_string($params[$option])
                ? filter_var($params[$option], FILTER_VALIDATE_INT)
                : false;
            if ($timeout === false || $timeout <= 0) {
                throw new \InvalidArgumentException(
                    'ShipEngine connection and total timeouts must be positive integers in seconds.'
                );
            }
            $params[$option] = $timeout;
        }

        $this->params = $params;
    }

    public function send()
    {
        $curl_handle = curl_init();
        if ($curl_handle === false) {
            throw Exception::failed('Unable to initialize the ShipEngine cURL request.');
        }

        $this->curl_handle = $curl_handle;

        try {
            if (!curl_setopt_array($curl_handle, $this->params)) {
                throw Exception::failed('Unable to configure the ShipEngine cURL request.');
            }

            $response = curl_exec($curl_handle);
            if ($response === false) {
                throw Exception::failed(curl_error($curl_handle));
            }

            $data = json_decode($response, true);

            if (isset($data['errors']) && !empty($data['errors']) && count($data['errors']) > 0) {
                \Log::info($data);
                throw Exception::response($data);
            }

            return $data;
        } finally {
            curl_close($curl_handle);
            $this->curl_handle = null;
        }
    }
}
