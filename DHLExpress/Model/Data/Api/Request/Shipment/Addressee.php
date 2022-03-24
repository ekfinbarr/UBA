<?php

namespace UBA\DHLExpress\Model\Data\Api\Request\Shipment;

use UBA\DHLExpress\Model\Data\AbstractData;

class Addressee extends AbstractData
{
    /** @var \UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee\Name */
    public $name;
    /** @var \UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee\Address */
    public $address;
    public $email;
    public $phoneNumber;

    protected function getClassMap()
    {
        return [
            'name'    => 'UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee\Name',
            'address' => 'UBA\DHLExpress\Model\Data\Api\Request\Shipment\Addressee\Address',
        ];
    }
}
