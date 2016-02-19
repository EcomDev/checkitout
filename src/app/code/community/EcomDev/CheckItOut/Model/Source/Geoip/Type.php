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

/**
 * Shipping method source model for default shipping method select
 *
 */
class EcomDev_CheckItOut_Model_Source_Geoip_Type
{
    const TYPE_COUNTRY = 'country';
    const TYPE_LOCATION = 'location';

    /**
     * Return array of GeoIp functionality options
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '', 'label' => Mage::helper('ecomdev_checkitout')->__('None')),
            array('value' => self::TYPE_COUNTRY, 'label' => Mage::helper('ecomdev_checkitout')->__('Country by IP Address')),
            array('value' => self::TYPE_LOCATION, 'label' => Mage::helper('ecomdev_checkitout')->__('Location/City by IP Address')),
        );
    }
}
