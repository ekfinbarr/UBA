<?php

namespace UBA\DHLExpress\Model\Data;

class DeliveryTime extends AbstractData
{
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\TimeWindow */
    public $source;

    public $date;
    public $weekDay;
    public $day;
    public $month;
    public $year;

    public $startTime;
    public $endTime;

    public $displayLabel;
    public $identifier;

    protected function getClassMap()
    {
        return [
            'source' => 'UBA\DHLExpress\Model\Data\Api\Response\TimeWindow',
        ];
    }
}
