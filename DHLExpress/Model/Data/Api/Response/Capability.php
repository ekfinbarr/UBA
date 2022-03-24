<?php

namespace UBA\DHLExpress\Model\Data\Api\Response;

use UBA\DHLExpress\Model\Data\AbstractData;

class Capability extends AbstractData
{
    public $rank;
    public $fromCountryCode;
    public $toCountryCode;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\Capability\Product */
    public $product;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\Capability\ParcelType */
    public $parcelType;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\Capability\Option[] */
    public $options;

    protected function getClassMap()
    {
        return [
            'product'    => 'UBA\DHLExpress\Model\Data\Api\Response\Capability\Product',
            'parcelType' => 'UBA\DHLExpress\Model\Data\Api\Response\Capability\ParcelType',
        ];
    }

    protected function getClassArrayMap()
    {
        return [
            'options' => 'UBA\DHLExpress\Model\Data\Api\Response\Capability\Option',
        ];
    }
}
