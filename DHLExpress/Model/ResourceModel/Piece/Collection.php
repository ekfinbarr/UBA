<?php

namespace UBA\DHLExpress\Model\ResourceModel\Piece;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'UBA\DHLExpress\Model\Piece',
            'UBA\DHLExpress\Model\ResourceModel\Piece'
        );
    }
}
