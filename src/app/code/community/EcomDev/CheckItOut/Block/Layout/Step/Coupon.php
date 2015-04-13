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

class EcomDev_CheckItOut_Block_Layout_Step_Coupon extends EcomDev_CheckItOut_Block_Layout_Step_Abstract
{
    /**
     * Check if this block is visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->helper('ecomdev_checkitout')->isCouponEnabled();
    }

    /**
     * Returns JSON configuration for coupon codes
     *
     * @return string
     */
    public function getCouponJson()
    {
        $config = array(
            'coupon' => $this->getQuote()->getCouponCode(),
            'saveUrl' => $this->getUrl('*/*/applyCoupon'),
            'confirmText' => $this->__('Are you sure that you want to remove the applied coupon code?')
        );

        return $this->helper('core')->jsonEncode($config);
    }
}
