<?php

namespace UBA\DHLExpress\Model\Data\Api\Response;

use UBA\DHLExpress\Model\Data\AbstractData;

class Shipment extends AbstractData
{
    public $shipmentId;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\Shipment\Piece[] */
    public $pieces;

    protected function getClassArrayMap()
    {
        return [
            'pieces' => 'UBA\DHLExpress\Model\Data\Api\Response\Shipment\Piece',
        ];
    }
}
