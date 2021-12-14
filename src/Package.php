<?php

namespace Yurstore\ShipEngineAPI;

class Package
{
    const UNIT_OUNCE = 'ounce';
    const UNIT_POUND = 'pound';

    protected $weight;

    protected $weightUnit = self::UNIT_POUND;
    
    protected $reference;

    public function __construct($weight, $reference = null, $weightUnit = self::UNIT_POUND)
    {
        $this->weight = $weight;
        $this->reference = $reference;
        $this->weightUnit = $weightUnit;
    }

    public function addToShipment($addressTo, $addressFrom, $service_code = 'ups_ground')
    {        
        $shipment = new Shipment($addressTo, $addressFrom, [$this]);
        $shipment->setService($service_code);
        return $shipment;
    }

    public function getWeightAmount()
    {
        return $this->weight;
    }

    public function getWeightUnit()
    {
        return $this->weightUnit;
    }
    
    public function getReferenceNumber()
    {
        return $this->reference ?? null;
    }
}
