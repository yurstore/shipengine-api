<?php

namespace Yurstore\ShipEngineAPI\ShipEngineException;

class Response extends Exception
{
    protected $request_id;

    protected $errors = [];

    public function __construct(array $response)
    {
        $this->request_id = $response['request_id'];
        $this->setErrors($response['errors']);


        $message = sprintf("ShipEngine Request ID %s response contained %d error(s). ", $this->request_id, count($this->errors));
        return parent::__construct($message . implode(' ', $this->errors));
    }

    private function setErrors(array $errors)
    {
        foreach ($errors as $error) {
            $this->errors[] = new Error($error);
        }

        return $this;
    }

    public function getRequestId()
    {
        return $this->request_id;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}