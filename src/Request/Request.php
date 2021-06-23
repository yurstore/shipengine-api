<?php

namespace Yurstore\ShipEngineAPI\ShipEngineRequest;

use Yurstore\ShipEngineAPI\ShipEngineException\Exception;

class Request
{
    protected $curl_handle;

    public function __construct(array $params)
    {
        $curl_handle = curl_init();
        curl_setopt_array($curl_handle, $params);

        $this->curl_handle = $curl_handle;
    }

    public function send()
    {
        $response = curl_exec($this->curl_handle);
        if ($response == false) {
            $message = curl_error($this->curl_handle) ?? null;
			throw Exception::failed($message);
        }

        $data = json_decode($response, true);

        if (isset($data['errors']) && !empty($data['errors']) && count($data['errors']) > 0) {
            throw Exception::response($data);
        }
		
        return $data;
    }
}
