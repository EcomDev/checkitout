<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2016 EcomDev BV (http://www.ecomdev.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

// Old fashion of creating tables, since backward compatibilities for 1.4-1.5
$this->run("
CREATE TABLE {$this->getTable('ecomdev_checkitout/geoip_country')} (
    ip_to BIGINT UNSIGNED NOT NULL,
    ip_from BIGINT UNSIGNED NOT NULL,
    country_id CHAR(2) NOT NULL DEFAULT '',
    PRIMARY KEY (ip_to, ip_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('ecomdev_checkitout/geoip_location')} (
    location_id BIGINT UNSIGNED NOT NULL,
    country_id CHAR(2) NOT NULL DEFAULT '',
    region_code CHAR(2) NOT NULL DEFAULT '',
    city VARCHAR(255) NOT NULL DEFAULT '',
    postcode VARCHAR(64) NOT NULL DEFAULT '',
    PRIMARY KEY(location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('ecomdev_checkitout/geoip_location_block')} (
    ip_to BIGINT UNSIGNED NOT NULL,
    ip_from BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (ip_to, ip_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");



$this->endSetup();
