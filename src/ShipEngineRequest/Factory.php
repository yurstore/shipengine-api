<?php

namespace Yurstore\ShipEngineAPI\ShipEngineRequest;

use Yurstore\ShipEngineAPI\Package;
use Yurstore\ShipEngineAPI\Shipment;

class Factory
{
    private static $api_url = "https://api.shipengine.com/v1/";

    public function __construct()
    {
    }

    public static function validateAddresses(array $addresses)
    {		
        $url = self::buildUrl("addresses/validate");

        return self::initRequest($url, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($addresses)
        ]);
    }

    public static function trackShipping($carrier, $tracking_number)
    {
        $url = self::buildUrl("tracking?carrier_code=".$carrier."&tracking_number=".$tracking_number);

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }

    public static function getShipment($shipment_id)
    {
        $url = self::buildUrl("shipments/".$shipment_id);

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }		
	
    public static function getLabelFromTracking($tracking_number)
    {		
        $url = self::buildUrl('labels?$tracking_number='.$tracking_number);

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }

    public static function getRates($weight, $addressTo, $addressFrom, $options)
    {
		$package = new Package($weight);
        $shipment = new Shipment($addressTo, $addressFrom, [$package]);
		
        $url = self::buildUrl("rates");

        return self::initRequest($url, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode([
                'shipment'     => $shipment->toArray(),
                'rate_options' => $options
            ])
        ]);
    }
	
    public static function createLabel($weight, $addressTo, $addressFrom, $service_code, $reference, $test = false)
    {
        $package = new Package($weight, $reference);
        $shipment = new Shipment($addressTo, $addressFrom, [$package]);
        $shipment->setService($service_code);

	    $url = self::buildUrl('labels');

        return self::initRequest($url, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode([
            'label_format' => 'pdf',
            'label_layout' => '4x6',
	    	'label_download_type' => 'url',
                'shipment'   => $shipment->toArray(),
                'test_label' => $test
            ])
        ]);
    }
	
    public static function createLabelWithShipment($shipment, $test = false)
    {
	    $url = self::buildUrl('labels');

        return self::initRequest($url, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode([
            'label_format' => 'pdf',
            'label_layout' => '4x6',
	    	'label_download_type' => 'url',
                'shipment'   => $shipment->toArray(),
                'test_label' => $test
            ])
        ]);
    }
	
    public static function listCarriers()
    {
        $url = self::buildUrl("carriers");

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }
	
    public static function getCarrier($carrier_id)
    {
        $endpoint = sprintf("carriers/%s", $carrier_id);
        $url = self::buildUrl($endpoint);

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }

    public static function listCarrierServices($carrier_id)
    {
        $endpoint = sprintf("carriers/%s/services", $carrier_id);
        $url = self::buildUrl($endpoint);

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }

    public static function listCarrierPackageTypes($carrier_id)
    {
        $endpoint = sprintf("carriers/%s/packages", $carrier_id);
        $url = self::buildUrl($endpoint);

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }

    public static function getCarrierOptions($carrier_id)
    {
        $endpoint = sprintf("carriers/%s/options", $carrier_id);
        $url = self::buildUrl($endpoint);

        return self::initRequest($url, [
            CURLOPT_HTTPGET => true
        ]);
    }
	
    private static function initRequest(string $url, array $params = [])
    {
        $params = $params + [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json",
                    "api-key: " . config('shipengine.api_key')
                ]
            ];

        return (new Request($params))->send();
    }
	
    private static function buildUrl(string $endpoint)
    {
        return self::$api_url . $endpoint;
    }

    public static function package($weight, $reference)
    {
        return new Package($weight, $reference);
    }

    public static function shipment($weights, $addressTo, $addressFrom, $reference = null, $advanced_options = null) 
    {
        $shipment = new Shipment($addressTo, $addressFrom, [], $advanced_options);

        foreach($weights as $weight)
        {
            $shipment->addPackage(New Package($weight, $reference));
        }

        return $shipment;
    }
}
