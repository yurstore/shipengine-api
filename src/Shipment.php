<?php

namespace Yurstore\ShipEngineAPI;

class Shipment
{
    protected $to;

    protected $from;

    protected $packages = [];

    protected $advanced_options = null;

	protected $service_code = 'ups_ground';

    public const CONFIRMATION_NONE = 'none';
    public const CONFIRMATION_DELIVERY = 'delivery';
    public const CONFIRMATION_SIGNATURE = 'signature';
    public const CONFIRMATION_ADULT_SIGNATURE = 'adult_signature';
    public const CONFIRMATION_DIRECT_SIGNATURE = 'direct_signature';
    public const CONFIRMATION_DELIVERY_MAILED = 'delivery_mailed';
    public const CONFIRMATION_VERBAL = 'verbal_confirmation';
    public const CONFIRMATION_DELIVERY_CODE = 'delivery_code';
    public const CONFIRMATION_AGE_VERIFICATION_16_PLUS = 'age_verification_16_plus';

    public const CONFIRMATIONS = [
        self::CONFIRMATION_NONE,
        self::CONFIRMATION_DELIVERY,
        self::CONFIRMATION_SIGNATURE,
        self::CONFIRMATION_ADULT_SIGNATURE,
        self::CONFIRMATION_DIRECT_SIGNATURE,
        self::CONFIRMATION_DELIVERY_MAILED,
        self::CONFIRMATION_VERBAL,
        self::CONFIRMATION_DELIVERY_CODE,
        self::CONFIRMATION_AGE_VERIFICATION_16_PLUS,
    ];

    protected $confirmation = self::CONFIRMATION_NONE;

    public function __construct(
        $to, 
        $from, 
        array $packages = [], 
        $advanced_options = null,
        $confirmation = "none",
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
        if (!in_array($confirmation, self::CONFIRMATIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported shipment confirmation "%s". Expected one of: %s.',
                    $confirmation,
                    implode(', ', self::CONFIRMATIONS)
                )
            );
        }

        $this->confirmation = $confirmation;

        return $this;
    }

    public function getConfirmation()
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
