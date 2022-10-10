<?php

namespace Yurstore\ShipEngineAPI;

class Shipment
{
    protected $to;

    protected $from;

    protected $packages = [];

    protected $advanced_options = null;
	
	protected $service_code = 'ups_ground';

    public function __construct($to, $from, array $packages = [], $advanced_options = null)
    {
        $this->to = $to;
        $this->from = $from;

        foreach ($packages as $package) {
            $this->addPackage($package);
        }

        $this->advanced_options = $advanced_options;
    }

    public function addPackage(Package $package)
    {
        $this->packages[] = $package;

        return $this;
    }

    /**
     * @return Package[]
     */
    public function getPackages()
    {
        return $this->packages;
    }
	
	public function setService($service_code)
	{
		$this->service_code = $service_code;
	}

    public function setAdvancedOptions($advanced_options)
    {
        $this->advanced_options = $advanced_options;
    }

    public function createLabel($test = false)
    {
        return ShipEngineRequest\Factory::createLabelWithShipment($this, $test);
    }

    public function toArray()
    {
        $array = [
			'service_code' => $this->service_code,
            'ship_to'   => $this->to,
            'ship_from' => $this->from,
            'packages'  => array_map(function ($package) {
                return [
                    'weight' => [
                        'value' => $package->getWeightAmount(),
                        'unit'  => $package->getWeightUnit()
                    ],
		    'label_messages' => [
			'reference1' => $package->getReferenceNumber()
		    ]
                ];
            }, $this->packages)
        ];
        if(!empty($package->getHeightAmount()))
        {
            $array['packages']['dimensions'] = [
                'height' => $package->getHeightAmount(),
                'width' => $package->getWidthAmount(),
                'length' => $package->getLengthAmount(),
                'unit' => $package->getDimensionUnit(),
            ];
        }
        if(!empty($this->advanced_options))
        {
            $array['advanced_options'] = $this->advanced_options;
        }

        return $array;
    }
}
