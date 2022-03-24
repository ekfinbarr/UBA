<?php

namespace UBA\DHLExpress\Model\Data\Api\Response\Shipment;

use UBA\DHLExpress\Model\Data\AbstractData;

class Piece extends AbstractData
{

    public $labelId;
    public $trackerCode;
    public $parcelType;
    public $pieceNumber;
    public $labelType;

    // Custom internal field
    public $postalCode;

    // Custom internal field
    public $shipmentRequest;

    // Custom internal field
    public $serviceOptions;
}
