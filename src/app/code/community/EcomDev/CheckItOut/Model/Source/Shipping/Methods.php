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
 * Shipping method source model for default shipping method select
 *
 */
class EcomDev_CheckItOut_Model_Source_Shipping_Methods extends Mage_Adminhtml_Model_System_Config_Source_Shipping_Allmethods
{
    const AUTO_METHOD = '__auto__';

    /**
     * Return array of carriers.

     * @param bool $isActiveOnlyFlag (ignored)
     * @return array
     */
    public function toOptionArray($isActiveOnlyFlag=false)
    {
        $isActiveOnlyFlag = true;
        return parent::toOptionArray($isActiveOnlyFlag);
    }
}
