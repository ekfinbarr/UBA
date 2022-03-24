<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace UBA\DHLExpress\Model\ResourceModel\Carrier\Rate\CSV;

class ColumnResolver
{
    const COLUMN_COUNTRY = 'Country';
    const COLUMN_REGION = 'Region/State';
    const COLUMN_ZIP = 'Zip/Postal Code';
    const COLUMN_PRICE = 'Shipping Price';

    /**
     * @var array
     */
    private $nameToPositionIdMap = [
        self::COLUMN_COUNTRY => 0,
        self::COLUMN_REGION => 1,
        self::COLUMN_ZIP => 2,
        self::COLUMN_PRICE => 4,
    ];

    /**
     * @var array
     */
    private $headers;

    /**
     * ColumnResolver constructor.
     * @param array $headers
     * @param array $columns
     */
    public function __construct(array $headers, array $columns = [])
    {
        $this->nameToPositionIdMap = array_merge($this->nameToPositionIdMap, $columns);
        $this->headers = array_map('trim', $headers);
    }

    /**
     * @param string $column
     * @param array $values
     * @return string|int|float|null
     * @throws ColumnNotFoundException
     */
    public function getColumnValue($column, array $values)
    {
        $column = (string)$column;
        $columnIndex = array_search($column, $this->headers, true);
        if (false === $columnIndex) {
            if (array_key_exists($column, $this->nameToPositionIdMap)) {
                $columnIndex = $this->nameToPositionIdMap[$column];
            } else {
                throw new ColumnNotFoundException(__('Requested column "%1" cannot be resolved (is the condition correct?)', $column));
            }
        }

        if (!array_key_exists($columnIndex, $values)) {
            throw new ColumnNotFoundException(__('Value for column "%1" not found', $column));
        }

        return trim($values[$columnIndex]);
    }
}
