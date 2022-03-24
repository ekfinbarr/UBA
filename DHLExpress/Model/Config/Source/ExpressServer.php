<?php

namespace UBA\DHLExpress\Model\Config\Source;

class ExpressServer implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            'https://api-mock.dhl.com/mydhlapi' => 'https://api-mock.dhl.com/mydhlapi' .  __(' - Mock Server (simulate API responses with fixed test data)'),
            'https://express.api.dhl.com/mydhlapi/test' => 'https://express.api.dhl.com/mydhlapi/test' . __(' - Test Environment'),
            'https://express.api.dhl.com/mydhlapi' => 'https://express.api.dhl.com/mydhlapi' . __(' - Production Environment'),
        ];
    }
}
