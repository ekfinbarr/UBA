<?php

namespace UBA\DHLExpress\Model\Data\Api\Response;

use UBA\DHLExpress\Model\Data\AbstractData;

class ServicePoint extends AbstractData
{
    public $id;
    public $name;
    public $keyword;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\ServicePoint\Address */
    public $address;
    public $geoLocation;
    public $distance;
    public $openingTimes;
    public $shopType;
    public $country;

    protected function getClassMap()
    {
        return [
            'address' => 'UBA\DHLExpress\Model\Data\Api\Response\ServicePoint\Address',
        ];
    }
}
