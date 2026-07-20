<?php

namespace Yurstore\ShipEngineAPI\Enums;

enum Confirmation: string
{
    public const DEFAULT = 'none';
    
    case None = 'none';
    case Delivery = 'delivery';
    case Signature = 'signature';
    case AdultSignature = 'adult_signature';
    case DirectSignature = 'direct_signature';
    case DeliveryMailed = 'delivery_mailed';
    case VerbalConfirmation = 'verbal_confirmation';
    case DeliveryCode = 'delivery_code';
    case AgeVerification16Plus = 'age_verification_16_plus';

    public static function values(): array
    {
        return array_map(
            static fn (self $confirmation) => $confirmation->value,
            self::cases()
        );
    }
}