<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace UBA\DHLExpress\Model\ResourceModel\Carrier\Rate;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Directory/country table name
     *
     * @var string
     */
    protected $countryTable;
    /**
     * Directory/country_region table name
     *
     * @var string
     */
    protected $regionTable;

    protected function _construct()
    {
        $this->_init(
            \UBA\DHLExpress\Model\Carrier::class,
            \UBA\DHLExpress\Model\ResourceModel\Carrier\RateManager::class
        );
        $this->countryTable = $this->getTable('directory_country');
        $this->regionTable = $this->getTable('directory_country_region');
    }

    /**
     * Initialize select, add country iso3 code and region name
     *
     * @return void
     */
    public function _initSelect()
    {
        parent::_initSelect();

        $this->_select->joinLeft(
            ['country_table' => $this->countryTable],
            'country_table.country_id = main_table.dest_country_id',
            ['dest_country' => 'iso3_code']
        )->joinLeft(
            ['region_table' => $this->regionTable],
            'region_table.region_id = main_table.dest_region_id',
            ['dest_region' => 'code']
        );

        $this->addOrder('dest_country', self::SORT_ORDER_ASC);
        $this->addOrder('dest_region', self::SORT_ORDER_ASC);
        $this->addOrder('dest_zip', self::SORT_ORDER_ASC);
        $this->addOrder('condition_value', self::SORT_ORDER_ASC);
    }

    /**
     * @param $websiteId
     * @return Collection
     */
    public function setWebsiteFilter($websiteId)
    {
        return $this->addFieldToFilter('website_id', $websiteId);
    }

    /**
     * @param $storeId
     * @return Collection
     */
    public function setStoreFilter($storeId)
    {
        return $this->addFieldToFilter('store_id', $storeId);
    }

    /**
     * @param $conditionName
     * @return Collection
     */
    public function setConditionFilter($conditionName)
    {
        return $this->addFieldToFilter('condition_name', $conditionName);
    }

    /**
     * @param $countryId
     * @return Collection
     */
    public function setCountryFilter($countryId)
    {
        return $this->addFieldToFilter('dest_country_id', $countryId);
    }

    /**
     * @param $method
     * @return Collection
     */
    public function setMethodFilter($method)
    {
        return $this->addFieldToFilter('method_name', $method);
    }
}
