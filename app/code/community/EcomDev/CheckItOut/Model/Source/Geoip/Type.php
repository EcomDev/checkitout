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
