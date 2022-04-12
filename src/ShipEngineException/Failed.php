<?php
namespace Yurstore\ShipEngineAPI\ShipEngineException;

class Failed extends Exception
{	
    public function __construct($message)
    {
        return parent::__construct($message);
    }
}