<?php

namespace UBA\DHLExpress\Model\Data\Api\Response;

use UBA\DHLExpress\Model\Data\AbstractData;

class Label extends AbstractData
{
    public $labelId;
    public $labelType;
    public $trackerCode;
    public $pieceNumber;
    public $routingCode;
    public $userId;
    public $organizationId;
    public $orderReference;
    public $pdf;
    public $application;
}
