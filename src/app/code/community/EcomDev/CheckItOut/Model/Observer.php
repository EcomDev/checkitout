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
     * Returns a checkout object instance
     *
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('ecomdev_checkitout/type_onepage');
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

        // Replace library only if it is included and only if Magento version is lower than 1.7
        if (isset($headItems['js/prototype/prototype.js'])
            && !$this->_getHelper()
                    ->getCompatibilityMode(EcomDev_CheckItOut_Helper_Data::COMPATIBILITY_TYPE_JS)) {
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
                        '', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, '1'
                );
                $controller->getRequest()->setDispatched(true);
                $controller->getResponse()->setRedirect(
                        Mage::getUrl('checkout/onepage/')
                );
            }
        }
    }

    /**
     * Changes flag in the session for modifying of the controller behavior
     *
     * @param Varien_Event_Observer $observer
     * @void
     */
    public function preDispatchIndexAction(Varien_Event_Observer $observer)
    {
        if ($this->_getHelper()->isActive() && $this->_getHelper()->isCustomRouter()) {
            /* @var $controller Mage_Checkout_OnepageController */
            $controller = $observer->getEvent()->getControllerAction();
            $controller->getOnepage()
                ->getCheckout()
                ->setIsActiveCheckItOut(
                    (bool)$controller->getRequest()->getParam('checkitout')
                );
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
        if ($this->_getHelper()->isActiveForSession()) {
            /* @var $controller Mage_Core_Controller_Front_Action */
            $controller = $observer->getEvent()->getControllerAction();

            $orderData = $controller->getRequest()->getPost('order');

            if ($this->_getHelper()->isCustomerCommentAllowed()
                    && isset($orderData['customer_comment'])) {
                $this->_getCheckout()
                        ->getQuote()
                        ->setCustomerComment($orderData['customer_comment']);
            }


            $post = $controller->getRequest()->getPost();

            if ($this->_getHelper()->isPaymentMethodHidden()) {
                // Issue with not submitted form details if payment method is hidden
                $post['payment']['method'] = $this->_getHelper()->getDefaultPaymentMethod();
                $controller->getRequest()->setPost($post);
            }

            if (isset($post['billing']) && !$this->_getCheckout()->getQuote()->getCustomerId()
                && $this->_getCheckout()->getQuote()->getCustomerEmail() !== $post['billing']['email']) {
                $result = $this->_getCheckout()->validateCustomerData($post['billing']);
                if ($result !== true) {
                    $result['error'] = true;
                    $result['success'] = false;
                    $result['error_messages'] = $result['message'];
                    // If customer data is not valid, throw an error
                    $controller->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);
                    $controller->getResponse()->setBody(
                        Mage::helper('core')->jsonEncode($result)
                    );
                    $controller->getRequest()->setDispatched(true);
                    return;
                }
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
        if ($this->_getHelper()->isActiveForSession()) {
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

    /**
     * Reset checkout session when product added to card
     *
     */
    public function cardAddComplete()
    {
        if ($this->_getHelper()->isActive()) {
            Mage::getSingleton('checkout/cart')->init();
        }
    }

    /**
     * Adds missing blocks to checkout, when block is enabled
     *
     * @param Varien_Event_Observer $observer
     */
    public function addMissingBlocks(Varien_Event_Observer $observer)
    {
        $compatibilityMode = $this->_getHelper()->getCompatibilityMode(
            EcomDev_CheckItOut_Helper_Data::COMPATIBILITY_TYPE_CODE
        );

        if ($compatibilityMode === EcomDev_CheckItOut_Helper_Data::COMPATIBILITY_V18) {
            $response = $observer->getResponse();
            $block = $observer->getBlock();

            $response->setShippingMethod(
                $block->helper('ecomdev_checkitout/render')->renderStep('shipping_method')
            );

            $response->setPayment(
                $block->helper('ecomdev_checkitout/render')->renderStep('payment')
            );
        }
    }
}
