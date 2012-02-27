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
 * Js common methods
 *
 */
class EcomDev_CheckItOut_Block_Js extends Mage_Checkout_Block_Onepage_Abstract
{
    /**
     * Retrieve checkout block
     *
     * @return Mage_Checkout_Block_Onepage
     */
    public function getCheckoutBlock()
    {
        return $this->getLayout()->getBlock(EcomDev_CheckItOut_Block_Layout_Step_Abstract::BLOCK_NAME_CHECKOUT);
    }

    /**
     * Returns JSON of the region list
     * @return mixed
     */
    public function getRegionJson()
    {
        return $this->helper('directory')->getRegionJson();
    }

    /**
     * Returns shipping method if applicable
     *
     * @return string|boolean
     */
    public function getShippingMethodJson()
    {
        if ($this->getCheckoutBlock()->getQuote()->isVirtual()) {
            return false;
        }

        $shippingMethod = $this->getCheckoutBlock()->getQuote()
            ->getShippingAddress()->getShippingMethod();

        $result = array('shipping_method' => $shippingMethod);

        return $this->helper('core')->jsonEncode($result);
    }

    /**
     * Returns hash json for checkout steps
     *
     * @return string
     */
    public function getHashJson()
    {
        $hash = Mage::getSingleton('ecomdev_checkitout/hash')->getHash(
            $this->getCheckoutBlock()->getQuote()
        );

        return $this->helper('core')->jsonEncode($hash);
    }

    /**
     * Checks availability of guest checkout
     *
     * @return boolean
     */
    public function isAllowedGuestCheckout()
    {
        return $this->helper('ecomdev_checkitout')->isAllowedGuestCheckout();
    }

}
