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
 * @copyright  Copyright (c) 2015 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Command line API for importing GeoIp data into checkitout
 *
 */
// Supposed to provide full path, since file can be included from unittest
$prefix = '';
if (class_exists('Mage', false)) {
    $prefix = Mage::getBaseDir() . DS . 'shell' . DS;
}

require_once $prefix . 'abstract.php';

class EcomDev_CheckItOut_Shell extends Mage_Shell_Abstract
{
    const DOWNLOAD_BASE_URL = 'http://geolite.maxmind.com/download/geoip/database/';
    const DOWNLOAD_CITY = 'GeoLiteCity_CSV/';
    const DOWNLOAD_COUNTRY = 'GeoIPCountryCSV.zip';

    const TYPE_CITY = 'city';
    const TYPE_COUNTRY = 'country';

    /**
     * Map of arguments for shell script,
     * for making possible using shortcuts
     *
     * @var array
     */
    protected $_actionArgsMap = array(
        'download' => array(
            'type' => 't',
            'url' => 'u',
        ),
        'import' => array(
            'type' => 't',
            'file' => 'f',
            'zip' => 'z'
        ),
        'test'   => array(
            'type' => 't',
            'ip' => 'i'
        )
    );

    /**
     * Current shell script action
     *
     * @var string|null
     */
    protected $_action = null;

    /**
     * Logger instance
     *
     * @var Zend_Log
     */
    protected $_logger = null;

    /**
     * Returns GeoIP model
     *
     * @return EcomDev_CheckItOut_Model_Geoip
     */
    protected function _getModel()
    {
        return Mage::getSingleton('ecomdev_checkitout/geoip');
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f geoip.php -- [options]

Actions:
  download       Downloads latest database from Maxmind website and imports data. Supports such parameters:
    -t --type    Type of the database to download (country, city)
    -u --url     Url for downloading of the database archive (ZIP) by default points to MaxMind website. [OPTIONAL]
  import         Imports GeoIP database from existent file in the system.
    -t --type    Type of the database to import (country, city)
    -f --file    File of the database (zip or csv)
    -z --zip     Alias of file property
  test           Tests lookup from the database
    -t --type    Type of the database to test (country, city)
    -i --ip      Ip address to check
USAGE;
    }

    /**
     * Logger for outputting data into command line
     *
     * @return Zend_Log
     */
    protected function _getLogger()
    {
        if ($this->_logger === null) {
            $this->_logger = new Zend_Log();
            $writer = new Zend_Log_Writer_Stream(STDOUT, 'a');
            $formatter = new Zend_Log_Formatter_Simple('%priorityName%: %message%' . PHP_EOL);
            $writer->setFormatter($formatter);
            if (!$this->getArg('debug')) {
                $writer->addFilter(Zend_Log::INFO);
            } else {
                $writer->addFilter(Zend_Log::DEBUG);
            }
            $this->_logger->addWriter(
                $writer
            );
        }

        return $this->_logger;
    }


    protected function _parseArgs()
    {
        foreach ($_SERVER['argv'] as $index => $argument) {
            if (isset($this->_actionArgsMap[$argument])) {
                $this->_action = $argument;
                unset($_SERVER['argv'][$index]);
                break;
            }
            unset($_SERVER['argv'][$index]);
        }

        parent::_parseArgs();
    }

    /**
     * Retrieves arguments (with map)
     *
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed|bool
     */
    public function getArg($name, $defaultValue = false)
    {
        if (parent::getArg($name) !== false) {
            return parent::getArg($name);
        }

        if ($this->_action && isset($this->_actionArgsMap[$this->_action][$name])) {
            $value = parent::getArg($this->_actionArgsMap[$this->_action][$name]);
            if ($value === false) {
                return $defaultValue;
            }
            return $value;
        }

        return $defaultValue;
    }

    public function run()
    {
        if (!$this->_action) {
            echo $this->usageHelp();
            return;
        }

        $this->{$this->_action}();
    }

    public function download()
    {
        $type = $this->getArg('type');
        $url = $this->getArg('url');
        if ($url === false) {
            $this->_getLogger()->log('Url is not specified, trying downloading data from MaxMind website', Zend_Log::DEBUG);
            if ($type === self::TYPE_CITY) {
                $content = @file_get_contents(self::DOWNLOAD_BASE_URL . self::DOWNLOAD_CITY);
                if (!preg_match_all('/href="([^\"]+\.zip)"/', $content, $matches)) {
                    echo $content;
                    $this->_getLogger()->log('Unable to load list of GeoIP files from MaxMind website', Zend_Log::WARN);
                    return;
                }
                sort($matches[1]);
                $lastRelease = end($matches[1]);
                $this->_getLogger()->log('Found latest release of the GeoIP city database: ' . $lastRelease, Zend_Log::INFO);
                $url = self::DOWNLOAD_BASE_URL . self::DOWNLOAD_CITY . $lastRelease;
            } else {
                $url = self::DOWNLOAD_BASE_URL . self::DOWNLOAD_COUNTRY;
            }
        }



    }

    /**
     * Returns required files for running import process
     *
     * @param string $urlOrPath file path (can be url)
     * @param string $type type of path
     * @return array|bool
     */
    protected function _getCsvFiles($urlOrPath, $type)
    {
        $tmpDir = Mage::getBaseDir('var') . DS . uniqid('geoip-file');
        mkdir($tmpDir);
        if (is_dir($urlOrPath)) {
            $files = @grep($urlOrPath . DS . '*.csv');
            foreach ($files as $file) {
                copy($file, $tmpDir . DS . basename($file));
            }
        } else {
            $baseName = basename($urlOrPath);

            if (is_file($urlOrPath)) {
                copy($urlOrPath, $tmpDir . DS . $baseName);
            } else {
                $content = @file_get_contents($urlOrPath);
                if (!$content) {
                    return false;
                }
                file_put_contents($tmpDir . DS . $baseName, $content);
                unset($content);
            }

            if (substr($baseName, -4) === '.zip') {
                shell_exec('unzip -d ')
            }

        }


    }
}

if (!class_exists('Mage', false)) {
    // Only instantiate shell when we are not in Magento app,
    // unit tests, etc
    $shell = new EcomDev_CheckItOut_Shell();
    $shell->run();
}
