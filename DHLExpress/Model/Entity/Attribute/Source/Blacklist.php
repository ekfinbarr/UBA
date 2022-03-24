<?php

namespace UBA\DHLExpress\Model\Entity\Attribute\Source;

class Blacklist extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Evening delivery'), 'value' => 'EVE'],
                ['label' => __('Same-day delivery'), 'value' => 'SDD'],
            ];
        }
        return $this->_options;
    }
}
