<?php

namespace Yurstore\ShipEngineAPI;

class Shipment
{
    protected $to;

    protected $from;

    protected $packages = [];
	
	protected $service_code = 'ups_ground';

    public function __construct($to, $from, array $packages = [])
    {
        $this->to = $to;
        $this->from = $from;

        foreach ($packages as $package) {
            $this->addPackage($package);
        }
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

    public function toArray()
    {
        return [
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
    }
}
