<?php

namespace UBA\DHLExpress\Model\Data\Capability;

use UBA\DHLExpress\Model\Data\AbstractData;

class Product extends AbstractData
{
    public $key;
    public $minWeightKg;
    public $maxWeightKg;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\Capability\ParcelType\Dimension */
    public $dimensions;
    public $productKey;

    protected function getClassMap()
    {
        return [
            'dimensions' => 'UBA\DHLExpress\Model\Data\Api\Response\Capability\ParcelType\Dimension',
        ];
    }
}
