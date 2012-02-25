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
 * Extension observer model
 *
 *
 */
class EcomDev_CheckItOut_Model_Observer
{
    /**
     * Replaces prototype library with 1.7 one
     *
     */
    public function replacePrototypeLibrary()
    {
        // IE9 compatible prototype library, otherwise checkitout will not work
        $head = Mage::app()->getLayout()->getBlock('head');
        $headItems = $head->getData('items');

        if (isset($headItems['js/prototype/prototype.js'])) {
            $headItems['js/prototype/prototype.js']['name'] = 'ecomdev/prototype.js';
        }

        $head->setData('items', $headItems);
    }

    /**
     * Redirect customer from shopping cart to checkout if needed.
     *
     * @param Varien_Event_Observer $observer
     * @void
     */
    public function redirectShoppingCartToCheckout(Varien_Event_Observer $observer)
    {
        if (Mage::helper('ecomdev_checkitout')->isShoppingCartRedirectEnabled()) {
            $cart = Mage::getSingleton('checkout/cart');
            if ($cart->getQuote()->getItemsCount()) {
                $cart->init();
                $cart->save();

                if (!$cart->getQuote()->validateMinimumAmount()) {
                    $warning = Mage::getStoreConfig('sales/minimum_order/description');
                    Mage::getSingleton('checkout/session')->addNotice($warning);
                }

                /* @var $controller Mage_Core_Controller_Front_Action */
                $controller = $observer->getEvent()->getControllerAction();
                $controller->setFlag(
                    '',
                    Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH,
                    '1'
                );
                $controller->getRequest()->setDispatched(true);
                $controller->getResponse()->setRedirect(
                    Mage::getUrl('checkout/onepage/')
                );
            }
        }
    }
}
