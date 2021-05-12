<?php

namespace Yurstore\ShipEngineAPI\ShipEngineException;

class Error
{
    protected $code;

    protected $message;

    public function __construct(array $error)
    {
        $this->code = $error['error_code'];
        $this->message = $error['message'];
    }

    public function getErrorCode()
    {
        return $this->code;
    }

    public function getErrorMessage()
    {
        return $this->message;
    }

    public function __toString()
    {
        return sprintf("[Error Code: %s] %s", $this->code ?: "N/A", $this->message);
    }
}