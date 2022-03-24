<?php

namespace UBA\DHLExpress\Model\Data\Api\Response;

use UBA\DHLExpress\Model\Data\AbstractData;

class TimeWindow extends AbstractData
{
    public $postalCode;
    public $deliveryDate;
    public $type;
    public $startTime;
    public $endTime;
    public $status;
}
