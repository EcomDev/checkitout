<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2011 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */


/**
 * Shipping method source model for default shipping method select
 *
 */
class EcomDev_CheckItOut_Model_Source_Shipping_Methods extends Mage_Adminhtml_Model_System_Config_Source_Shipping_Allmethods
{
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
