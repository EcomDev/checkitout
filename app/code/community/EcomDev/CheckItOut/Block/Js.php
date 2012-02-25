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
