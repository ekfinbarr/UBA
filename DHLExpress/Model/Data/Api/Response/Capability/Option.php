<?php

namespace UBA\DHLExpress\Model\Data\Api\Response\Capability;

use UBA\DHLExpress\Model\Data\AbstractData;

class Option extends AbstractData
{
    public $key;
    public $description;
    public $rank;
    public $code;
    public $inputType;
    public $inputMax;
    public $optionType;
    /** @var \UBA\DHLExpress\Model\Data\Api\Response\Capability\Option[] */
    public $exclusions;

    protected function getClassArrayMap()
    {
        return [
            'exclusions' => 'UBA\DHLExpress\Model\Data\Api\Response\Capability\Option',
        ];
    }
}
