<?php

namespace UBA\DHLExpress\Model\Config\Source;

class ExpressCapability implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            'pickup' => __('Pickup'),
            'delivery' => __('Delivery'),
            'auto' => __('Delivery and Pickup'),
        ];
    }
}
