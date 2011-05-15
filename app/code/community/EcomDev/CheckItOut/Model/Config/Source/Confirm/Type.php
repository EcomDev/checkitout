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
 * Confirmation type source model
 *
 */
class EcomDev_CheckItOut_Model_Config_Source_Confirm_Type
{
    const TYPE_NONE = 'none';
    const TYPE_POPUP = 'popup';
    const TYPE_CHECKBOX = 'checkbox';

    /**
     * Returns option list of confirmation types for configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            self::TYPE_NONE => Mage::helper('ecomdev_checkitout')->__('None'),
            self::TYPE_CHECKBOX => Mage::helper('ecomdev_checkitout')->__('Checkbox'),
            self::TYPE_POPUP => Mage::helper('ecomdev_checkitout')->__('PopUp')
        );
    }
}
