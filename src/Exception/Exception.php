<?php

namespace Yurstore\ShipEngineAPI\ShipEngineException;

class Exception extends \Exception
{
    public static function response(array $response)
    {
        throw new Response($response);
    }

    public static function failed($message = "No response was received.")
    {
        throw new Failed($message);
    }
}