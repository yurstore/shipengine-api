<?php

namespace Yurstore\ShipEngineAPI;

class Package
{
    const UNIT_OUNCE = 'ounce';
    const UNIT_POUND = 'pound';

    protected $weight;

    protected $weightUnit = self::UNIT_POUND;

    public function __construct($weight, $weightUnit = self::UNIT_POUND)
    {
        $this->weight = $weight;
        $this->weightUnit = $weightUnit;
    }

    public function getWeightAmount()
    {
        return $this->weight;
    }

    public function getWeightUnit()
    {
        return $this->weightUnit;
    }
}