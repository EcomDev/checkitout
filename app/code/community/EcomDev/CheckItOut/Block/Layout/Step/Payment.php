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
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Payment method selection step wrapper
 *
 */
class EcomDev_CheckItOut_Block_Layout_Step_Payment extends EcomDev_CheckItOut_Block_Layout_Step_Abstract
{
    /**
     * Custom logic for block visibility, depending on preselected values
     *
     * @return boolean
     */
    public function isVisible()
    {
        return !Mage::helper('ecomdev_checkitout')->isPaymentMethodHidden();
    }

}
