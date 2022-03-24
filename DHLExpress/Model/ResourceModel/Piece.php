<?php

namespace UBA\DHLExpress\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Piece extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('uba_dhlexpress_pieces', 'entity_id');
    }
}
