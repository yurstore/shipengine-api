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
        $url = Yurstore\ShipEngineAPI\ShipEngineRequest\Factory::buildUrl('labels');
        return Yurstore\ShipEngineAPI\ShipEngineRequest\Factory::initRequest($url, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode([
                'label_format' => 'pdf',
                'label_layout' => '4x6',
                'label_download_type' => 'url',
                'shipment'   => $this->toArray(),
                'test_label' => $test
            ])
        ]);
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
        if(!empty($this->advanced_options))
        {
            $array['advanced_options'] = $this->advanced_options;
        }

        return $array;
    }
}
