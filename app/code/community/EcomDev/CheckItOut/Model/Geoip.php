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
 * GeoIp model for importing of the csv files and searching by ip address
 *
 */
class EcomDev_CheckItOut_Model_Geoip extends Mage_Core_Model_Abstract
{
    const TYPE_COUNTRY = 'country';
    const TYPE_LOCATION = 'location';
    const TYPE_LOCATION_BLOCK = 'location_block';

    const XML_PATH_TYPE = EcomDev_CheckItOut_Helper_Data::XML_PATH_GEOIP_TYPE;
    const XML_PATH_USE_REGION = 'ecomdev_checkitout/geoip/use_region';
    const XML_PATH_USE_POSTCODE = 'ecomdev_checkitout/geoip/use_postcode';
    const XML_PATH_USE_CITY = 'ecomdev_checkitout/geoip/use_city';
    const XML_PATH_ALLOWED_COUNTRY = 'general/country/allow';

    /**
     * Initializes resource model
     *
     */
    protected function _construct()
    {
        $this->_init('ecomdev_checkitout/geoip');
    }

    /**
     * Set logger data
     *
     * @param Zend_Log|null $logger
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    public function setLogger($logger = null)
    {
        $this->_logger = $logger;
        return $this;
    }

    /**
     * Returns logger instance
     *
     * @return null|Zend_Log
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Apply address info to address container by ip address
     *
     * @param string $ipAddress
     * @param Varien_Object $address address data container
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    public function applyLocationByIp($ipAddress, $address)
    {
        if (Mage::getStoreConfig(self::XML_PATH_TYPE) == self::TYPE_LOCATION) {
            return $this->_applyLocationCityByIp($ipAddress, $address);
        }

        return $this->_applyLocationCountryByIp($ipAddress, $address);
    }

    /**
     * Apply address info to address container by ip address from coountry db
     *
     * @param string $ipAddress
     * @param $address
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    protected function _applyLocationCountryByIp($ipAddress, $address)
    {
        if ($countryId = $this->_getResource()->getCountryIdByIp($ipAddress)) {
            $allowCountries = explode(',', (string)Mage::getStoreConfig(self::XML_PATH_ALLOWED_COUNTRY));
            if (!$allowCountries || in_array($countryId, $allowCountries)) {
                $address->setCountryId($countryId);
            }
        }
        return $this;
    }

    /**
     * Apply address info to address container by ip address from city db
     *
     * @param string $ipAddress
     * @param $address
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    protected function _applyLocationCityByIp($ipAddress, $address)
    {
        if ($data = $this->_getResource()->getCityLocationByIp($ipAddress)) {
            $allowCountries = explode(',', (string)Mage::getStoreConfig(self::XML_PATH_ALLOWED_COUNTRY));
            if (!$allowCountries || in_array($data['country_id'], $allowCountries)) {
                $address->setCountryId($data['country_id']);
                if (Mage::getStoreConfigFlag(self::XML_PATH_USE_REGION)) {
                    $address->setRegionId($data['region_id']);
                }
                if (Mage::getStoreConfigFlag(self::XML_PATH_USE_CITY)) {
                    $address->setCity($data['city']);
                }
                if (Mage::getStoreConfigFlag(self::XML_PATH_USE_POSTCODE)) {
                    $address->setPostcode($data['postcode']);
                }
            }
        }
        return $this;
    }

    /**
     * Imports country based GEOIP information
     *
     * @param $file
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    public function importCountryFile($file)
    {
        $io = $this->_getIo($file);
        $this->_log('Start importing country GEOIP file: ' . $file, Zend_Log::INFO);
        $this->_getResource()->startImport(self::TYPE_COUNTRY);
        try {
            $this->_walkCsv(
                $io, 6,
                array('ip_string_from', 'ip_string_to',
                      'ip_num_from', 'ip_num_to', 'country_id'),
                array('ip_num_from' => 'ip_from',
                      'ip_num_to' => 'ip_to',
                      'country_id' => 'country_id')
            );
            $this->_getResource()->endImport();
            $this->_log('End importing country GEOIP file', Zend_Log::INFO);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_log('Aborted importing process, see exception log', Zend_Log::INFO);
            $this->_getResource()->cancelImport();
        }
        $io->streamClose();
        return $this;
    }

    /**
     * Imports city based GEOIP information with location info
     *
     * @param $file
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    public function importLocationFile($file)
    {
        $io = $this->_getIo($file);
        $this->_log('Start importing city-location GEOIP file: ' . $file, Zend_Log::INFO);
        $this->_getResource()->startImport(self::TYPE_LOCATION);
        try {
            $this->_walkCsv($io, 9, false, array(
                'locId' => 'location_id',
                'country' => 'country_id',
                'region' => 'region_code',
                'city' => 'city',
                'postalCode' => 'postcode'
            ));
            $this->_getResource()->endImport();
            $this->_log('End importing city-location GEOIP file', Zend_Log::INFO);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_log('Aborted importing process, see exception log', Zend_Log::INFO);
            $this->_getResource()->cancelImport();
        }

        $io->streamClose();
        return $this;
    }

    /**
     * Imports city based GEOIP information with location ip blocks
     *
     * @param $file
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    public function importLocationBlockFile($file)
    {
        $io = $this->_getIo($file);
        $this->_log('Start importing city-blocks GEOIP file: ' . $file, Zend_Log::INFO);
        $this->_getResource()->startImport(self::TYPE_LOCATION_BLOCK);
        try {
            $this->_walkCsv($io, 3, false, array(
                'startIpNum' => 'ip_from',
                'endIpNum' => 'ip_to',
                'locId' => 'location_id'
            ));
            $this->_getResource()->endImport();
            $this->_log('End importing city-blocks GEOIP file', Zend_Log::INFO);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_log('Aborted importing process, see exception log', Zend_Log::INFO);
            $this->_getResource()->cancelImport();
        }
        $io->streamClose();
        return $this;
    }




    /**
     *
     * @param Varien_Io_File $io
     * @param int            $length
     * @param array|bool     $headers
     * @param array          $map
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    protected function _walkCsv(Varien_Io_File $io, $length, $headers, $map)
    {
        $rowCount = 0;
        while (false !== ($csvLine = $io->streamReadCsv())) {
            $rowCount++;
            if (empty($csvLine) || count($csvLine) < $length) {
                $this->_log(sprintf('Skipping row #%d because of not enough column count', $row));
                continue;
            }

            if ($headers === false) {
                $headers = $csvLine;
                $this->_log(sprintf('Found a header line: %s', implode(',', $headers)));
                continue;
            }

            $row = array();
            foreach ($headers as $index => $header) {
                if (!isset($map[$header])) {
                    continue;
                }
                $row[$map[$header]] = $csvLine[$index];
            }

            $this->_getResource()->addRow($row);
            if ($rowCount % 10000 == 0) {
                $this->_log(sprintf('%d rows walked...', $rowCount));
            }
        }

        $this->_log(sprintf('%d rows walked...', $rowCount));
        return $this;
    }

    /**
     * Return prepared IO file object
     *
     * @param $file
     * @return Varien_Io_File
     */
    protected function _getIo($file)
    {
        $io     = new Varien_Io_File();
        $info   = pathinfo($file);
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');
        return $io;
    }

    /**
     * Logs a message to a logger or internal system log
     *
     * @param     $message
     * @param int $priority
     *
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    protected function _log($message, $priority = Zend_Log::DEBUG)
    {
        if ($this->getLogger()) {
            $this->getLogger()->log($message, $priority);
        } else {
            Mage::log($message, $priority, 'checkitout.log');
        }

        return $this;
    }
}
