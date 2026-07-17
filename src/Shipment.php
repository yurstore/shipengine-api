<?php

namespace Yurstore\ShipEngineAPI;

use Yurstore\ShipEngineAPI\Enums\Confirmation;

class Shipment
{
    protected $to;

    protected $from;

    protected $packages = [];

    protected $advanced_options = null;

	protected $service_code = 'ups_ground';

    protected string $confirmation = Confirmation::DEFAULT;

    public function __construct(
        $to, 
        $from, 
        array $packages = [], 
        $advanced_options = null,
        string $confirmation = Confirmation::DEFAULT,
    ) {
        $this->to = $to;
        $this->from = $from;

        foreach ($packages as $package) {
            $this->addPackage($package);
        }

        $this->advanced_options = $advanced_options;
        $this->setConfirmation($confirmation);
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

    public function setConfirmation($confirmation)
    {
        if (Confirmation::tryFrom($confirmation) === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported shipment confirmation "%s". Expected one of: %s.',
                    $confirmation,
                    implode(', ', Confirmation::values())
                )
            );
        }

        $this->confirmation = $confirmation;

        return $this;
    }

    public function getConfirmation(): string
    {
        return $this->confirmation;
    }

    public function createLabel($test = false)
    {
        return ShipEngineRequest\Factory::createLabelWithShipment($this, $test);
    }

    public function toArray()
    {
        $array = [
			'service_code' => $this->service_code,
            'confirmation' => $this->confirmation,
            'ship_to'   => $this->to,
            'ship_from' => $this->from,
            'packages'  => array_map(function ($package) {
                return [
                    'weight' => [
                        'value' => $package->getWeightAmount(),
                        'unit'  => $package->getWeightUnit()
                    ],
                    'dimensions' => [
                        'height' => $package->getHeightAmount(),
                        'width' => $package->getWidthAmount(),
                        'length' => $package->getLengthAmount(),
                        'unit' => $package->getDimensionUnit(),
                    ],
                    'label_messages' => [
                        'reference1' => $package->getReferenceNumber()
                    ]
                ];
            }, $this->packages)
        ];
        if($this->service_code == 'expedited_mail_innovations')
        {
            foreach($array['packages'] as $index => $package)
            {
                $array['packages'][$index]['package_code'] = 'mi_standard_flat';
            }
        }
        if(!empty($this->advanced_options))
        {
            $array['advanced_options'] = $this->advanced_options;
        }

        return $array;
    }
}
