<?php

namespace UBA\DHLExpress\Model\Service;

use UBA\DHLExpress\Model\Api\Connector;

class Authentication
{

    protected $connector;

    public function __construct(
        Connector $connector
    ) {
        $this->connector = $connector;
    }

    public function test($userId, $key)
    {
        return $this->connector->testAuthenticate($userId, $key);
    }
}
