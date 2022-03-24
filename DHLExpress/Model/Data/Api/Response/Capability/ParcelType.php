<?php

namespace UBA\DHLExpress\Model\Data\Api\Response\Capability;

use UBA\DHLExpress\Model\Data\AbstractData;

class ParcelType extends AbstractData
{
    public $key;
    public $minWeightKg;
    public $maxWeightKg;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\Capability\ParcelType\Dimension */
    public $dimensions;

    protected function getClassMap()
    {
        return [
            'dimensions' => 'UBA\DHLExpress\Model\Data\Api\Response\Capability\ParcelType\Dimension',
        ];
    }
}
