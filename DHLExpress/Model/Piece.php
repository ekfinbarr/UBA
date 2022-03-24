<?php

namespace UBA\DHLExpress\Model;

use Magento\Framework\Model\AbstractModel;

class Piece extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('UBA\DHLExpress\Model\ResourceModel\Piece');
    }
}
