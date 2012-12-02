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
 * Extension observer model
 *
 *
 */
class EcomDev_CheckItOut_Model_Observer
{
    /**
     * Retrieve module helper instance
     *
     * @return EcomDev_CheckItOut_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('ecomdev_checkitout');
    }

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
        if ($this->_getHelper()->isShoppingCartRedirectEnabled()) {
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

    /**
     * Before save order activities (e.g. saving customer comment, default payment method)
     *
     * @param Varien_Event_Observer $observer
     * @void
     */
    public function preDispatchSaveOrderAction(Varien_Event_Observer $observer)
    {
        if ($this->_getHelper()->isActive()) {
            /* @var $controller Mage_Core_Controller_Front_Action */
            $controller = $observer->getEvent()->getControllerAction();

            $orderData = $controller->getRequest()->getPost('order');

            if ($this->_getHelper()->isCustomerCommentAllowed()
                && isset($orderData['customer_comment'])) {
                Mage::getSingleton('ecomdev_checkitout/type_onepage')
                    ->getQuote()
                    ->setCustomerComment($orderData['customer_comment']);
            }

            if ($this->_getHelper()->isPaymentMethodHidden()) {
                // Issue with not submitted form details if payment method is hidden
                $post = $controller->getRequest()->getPost();
                $post['payment']['method'] = $this->_getHelper()->getDefaultPaymentMethod();
                $controller->getRequest()->setPost($post);
            }

            if ($controller->getRequest()->getPost('newsletter')) {
                Mage::getSingleton('checkout/session')
                    ->setNewsletterSubsribed(true)
                    ->setNewsletterEmail(
                        Mage::getSingleton('ecomdev_checkitout/type_onepage')->getQuote()->getCustomerEmail()
                    );
            }
        }
    }

    /**
     * Order sucess activities (subscription to newsletter)
     *
     * @param Varien_Event_Observer $observer
     * @void
     */
    public function orderSuccessAction(Varien_Event_Observer $observer)
    {
        if ($this->_getHelper()->isActive()) {
            if ($this->_getHelper()->isNewsletterCheckboxDisplay()
                && Mage::getSingleton('checkout/session')->getNewsletterSubsribed(true)) {
                try {
                    Mage::getModel('newsletter/subscriber')->subscribe(
                        Mage::getSingleton('checkout/session')->getNewsletterEmail(true)
                    );
                } catch (Exception $e) {
                    // Subscription shouldn't break checkout, so we just log exception
                    Mage::logException($e);
                }
            }
        }
    }
}
