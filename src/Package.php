<?php

namespace Yurstore\ShipEngineAPI;

class Package
{
    const UNIT_OUNCE = 'ounce';
    const UNIT_POUND = 'pound';
    const UNIT_INCH = 'inch';

    protected $weight;

    protected $weightUnit = self::UNIT_POUND;

    protected $height = 1;

    protected $width = 8;

    protected $length = 12;

    protected $dimensionUnit = self::UNIT_INCH;
    
    protected $reference;

    public function __construct($weight, $reference = null, $weightUnit = self::UNIT_POUND)
    {
        $this->weight = $weight;
        $this->reference = $reference;
        $this->weightUnit = $weightUnit;
    }

    public function addDimensions($height, $width, $length)
    {
        $this->height = $height;
        $this->width = $width;
        $this->length = $length;
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

    public function getHeightAmount()
    {
        return $this->height;
    }

    public function getWidthAmount()
    {
        return $this->width;
    }

    public function getLengthAmount()
    {
        return $this->length;
    }

    public function getDimensionUnit()
    {
        return $this->dimensionUnit;
    }
    
    public function getReferenceNumber()
    {
        return $this->reference ?? null;
    }
}
