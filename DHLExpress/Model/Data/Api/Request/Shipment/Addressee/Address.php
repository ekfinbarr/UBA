<?php

namespace UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee;

use UBA\DHLExpress\Model\Data\AbstractData;

class Address extends AbstractData
{
    public $countryCode;
    public $postalCode;
    public $city;
    public $street;
    public $number;
    public $isBusiness;
    public $addition;
}
