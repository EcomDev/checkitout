<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement for EcomDev Premium Extensions.
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.ecomdev.org/license-agreement
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2012 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Geo IP resource model
 *
 */
class EcomDev_CheckItOut_Model_Mysql4_Geoip extends Mage_Core_Model_Mysql4_Abstract
{
    const TYPE_COUNTRY = EcomDev_CheckItOut_Model_Geoip::TYPE_COUNTRY;
    const TYPE_LOCATION = EcomDev_CheckItOut_Model_Geoip::TYPE_LOCATION;
    const TYPE_LOCATION_BLOCK = EcomDev_CheckItOut_Model_Geoip::TYPE_LOCATION_BLOCK;

    /**
     * Rows to be inserted into database
     *
     * @var array
     */
    protected $_rows = array();

    /**
     * Current import table type
     *
     * @var string
     */
    protected $_currentType = null;

    /**
     * Sets up resource prefix for resource model
     *
     */
    protected function _construct()
    {
        $this->_setResource('ecomdev_checkitout');
        $this->_tables[self::TYPE_COUNTRY] = $this->getTable('ecomdev_checkitout/geoip_country');
        $this->_tables[self::TYPE_LOCATION] = $this->getTable('ecomdev_checkitout/geoip_location');
        $this->_tables[self::TYPE_LOCATION_BLOCK] = $this->getTable('ecomdev_checkitout/geoip_location_block');
    }

    /**
     * Returns country location by ip address
     *
     * @param string|int $ipAddress
     * @return false|string
     */
    public function getCountryIdByIp($ipAddress)
    {
        return $this->_lookupIpRange(self::TYPE_COUNTRY, 'country_id', $ipAddress);
    }

    /**
     * Returns city location by ip address
     *
     * @param string|int $ipAddress
     * @return false|array
     */
    public function getCityLocationByIp($ipAddress)
    {
        if ($locationId = $this->_lookupIpRange(self::TYPE_LOCATION_BLOCK, 'location_id', $ipAddress)) {
            $select = $this->_getReadAdapter()->select();
            $select->from(array('location' => $this->getTable(self::TYPE_LOCATION)))
                ->joinLeft(array('region' => $this->getTable('directory/country_region')),
                    'region.country_id = location.country_id AND region.code = location.region_code',
                    array('region_id')
                )
                ->where('location.location_id = ?', $locationId)
                ->limit(1);

            return $this->_getReadAdapter()->fetchRow($select, array(), Zend_Db::FETCH_ASSOC);
        }

        return false;
    }

    /**
     * Lookups IP range for retrieve table data
     *
     * @param string $table
     * @param string $field
     * @param string|int $ipAddress
     * @return string|false
     */
    protected function _lookupIpRange($table, $field, $ipAddress)
    {
        if (!is_int($ipAddress)) {
            $ipAddress = ip2long($ipAddress);
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable($table), array(
                $this->_getReadAdapter()->quoteInto('IF(ip_from <= ?, ' . $field . ', NULL)', $ipAddress)
            ))
            ->where('ip_to >= ?', $ipAddress)
            ->order('ip_to ASC')
            ->limit(1);

        return $this->_getReadAdapter()->fetchOne($select);
    }


    /**
     * Starts import for importing table records of various geoip data tables
     *
     * @param string $type
     * @return EcomDev_CheckItOut_Model_Mysql4_Geoip
     * @throws RuntimeException
     */
    public function startImport($type)
    {
        if (!isset($this->_tables[$type])) {
            throw new RuntimeException('Unknown record type for import');
        }

        $this->_currentType = $type;
        $this->_rows = array();
        $this->beginTransaction();
        $this->_getWriteAdapter()->delete($this->getTable($this->_currentType));
        return $this;
    }

    /**
     * Adds row for importing data into database
     *
     * @param array $row
     * @return EcomDev_CheckItOut_Model_Mysql4_Geoip
     */
    public function addRow($row)
    {
        $this->_rows[] = $row;
        if (count($this->_rows) > 1000) {
            $this->_getWriteAdapter()->insertMultiple(
                $this->getTable($this->_currentType),
                $this->_rows
            );
            $this->_rows = array();
        }
        return $this;
    }

    /**
     * Ends importing of the rows into database
     *
     * @return EcomDev_CheckItOut_Model_Mysql4_Geoip
     */
    public function endImport()
    {
        if ($this->_rows) {
            $this->_getWriteAdapter()->insertMultiple(
                $this->getTable($this->_currentType),
                $this->_rows
            );
            $this->_rows = array();
        }
        $this->commit();
        return $this;
    }

    /**
     * Cancels import of the data into db
     *
     * @return EcomDev_CheckItOut_Model_Mysql4_Geoip
     */
    public function cancelImport()
    {
        $this->_rows = array();
        $this->rollBack();
        return $this;
    }
}
