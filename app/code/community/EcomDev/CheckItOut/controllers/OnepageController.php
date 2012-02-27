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

require_once 'Mage/Checkout/controllers/OnepageController.php';

/**
 * CheckItOut override of onepage checkout controller
 *
 */
class EcomDev_CheckItOut_OnepageController extends Mage_Checkout_OnepageController
{
    const DEFAULT_ACTION_NAME = 'index';

    const LAYOUT_HANDLE_BASE = 'ecomdev_checkitout_skeleton';
    const LAYOUT_HANDLE_DEPRACATED = 'ecomdev_checkitout_layout';
    const LAYOUT_HANDLE_NO_PAYMENT = 'ecomdev_checkitout_no_payment';

    /**
     * List of action names that require adding of base handle
     *
     * @var array
     */
    protected $_addBaseHandleToActions = array('index', 'layout', 'steps');

    /**
     * List of special handles for checkout steps
     *
     * @var array
     */
    protected $_specialHandlesForSteps = array(
        'shipping_method' => 'checkout_onepage_shippingmethod',
        'payment' => 'checkout_onepage_paymentmethod'
    );

    /**
     * Checks that CheckItOut extension is active for current website
     *
     * @return boolean
     */
    protected function _isActive()
    {
        return $this->_getHelper()->isActive();
    }

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
     * Adds checkitout layout handles if it is enabled
     * (non-PHPdoc)
     *
     * @see Mage_Core_Controller_Varien_Action::addActionLayoutHandles()
     * @return Mage_Core_Controller_Varien_Action
     */
    public function addActionLayoutHandles()
    {
        parent::addActionLayoutHandles();
        if ($this->_isActive()
            && in_array($this->getRequest()->getActionName(), $this->_addBaseHandleToActions)) {
            $this->getLayout()->getUpdate()->addHandle(self::LAYOUT_HANDLE_BASE);
            $designHandle = $this->_getHelper()->getDesignLayoutHandle();
            if ($designHandle === false) { // Old layout system
                $designHandle = self::LAYOUT_HANDLE_DEPRACATED;
            }

            $this->getLayout()->getUpdate()->addHandle($designHandle);
        }

        if ($this->_isActive()
            && !$this->getOnepage()->getQuote()->getPayment()->getMethod()) {
             $this->getLayout()->getUpdate()->addHandle(self::LAYOUT_HANDLE_NO_PAYMENT);
        }

        if ($this->_isActive() && $this->_getHelper()->getCompatibilityMode('template') !== false) {
            $this->getLayout()->getUpdate()->addHandle(
                $this->getFullActionName() . '_' . $this->_getHelper()->getCompatibilityMode('template')
            );
        }

        return $this;
    }

    /**
     * Returns dependency injected onepage model
     *
     * @return Mage_Checkout_Model_Type_Onepage|EcomDev_CheckItOut_Model_Type_Onepage
     */
    public function getOnepage()
    {
        if ($this->_isActive()) {
            return Mage::getSingleton('ecomdev_checkitout/type_onepage');
        }

        return parent::getOnepage();
    }

    /**
     * Retrieve steps blocks from checkout
     * and sets response as JSON
     *
     */
    public function stepsAction()
    {
        if (!$this->_isActive()) {
            $this->norouteAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }

        $steps = $this->getRequest()->getParam('steps');

        if (!is_array($steps)) {
            $steps = array();
        }

        $this->loadLayout();
        $result = array();
        foreach ($steps as $step) {
            if (isset($this->_specialHandlesForSteps[$step])) {
                continue;
            }
            $result[$step] = $this->getLayout()->getBlock('checkout.layout')->getStepBlockHtml($step);
        }

        foreach ($steps as $step) {
            if (isset($this->_specialHandlesForSteps[$step])) {
                $layout = Mage::getModel('core/layout');
                $update = $layout->getUpdate();
                $update->load($this->_specialHandlesForSteps[$step]);
                $layout->generateXml();
                $layout->generateBlocks();
                $result[$step] =  $layout->getOutput();
            }
        }

        if (empty($result)) {
            $resultJSON = '{}';
        } else {
            $resultJSON = Mage::helper('core')->jsonEncode($result);
        }

        $this->getResponse()->setBody($resultJSON);
    }

    /**
     * Displays confirmation popup content
     *
     *
     */
    public function confirmAction()
    {
        if (!$this->_isActive()) {
            $this->norouteAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }

        if ($orderData = $this->getRequest()->getPost('order')) {
            $customerComment = (isset($orderData['customer_comment']) ? $orderData['customer_comment'] : '');
            $this->getOnepage()->getQuote()->setCustomerComment($customerComment);
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Retrieves all layout of checkout page and return html
     *
     * @depracated after 1.3.0
     */
    public function layoutAction()
    {
        if (!$this->_isActive()) {
            $this->norouteAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }

        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->getBlock('checkout.layout')->toHtml()
        );
    }



    /**
     * Overrides default behavior for saving billing address
     *
     * @return void
     */
    public function saveBillingAction()
    {
        if (!$this->_isActive()) {
            parent::saveBillingAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost('billing', array());
            if ($this->_getHelper()
                    ->getCompatibilityMode('code') === EcomDev_CheckItOut_Helper_Data::COMPATIBILITY_V14) {
                $data = $this->_filterPostData($postData);
            } else {
                $data = $postData;
            }

            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }

            Mage::register(
                'login_action_text',
                Mage::helper('ecomdev_checkitout')->__('<a hreaf="#" onclick="%s">log in</a>', 'loginStep.showPopUp(); return false;')
            );

            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);
            $this->_addHashInfo($result);

            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        }
    }


    /**
     * Overrides default behavior for saving shipping address
     *
     * @return void
     */
    public function saveShippingAction()
    {
        if (!$this->_isActive()) {
            parent::saveShippingAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

            $this->_addHashInfo($result);
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        }
    }

    /**
     * Adds hash information to result info object
     *
     *
     * @param array $result
     * @return EcomDev_CheckItOut_OnepageController
     */
    protected function _addHashInfo(&$result)
    {
        $result['stepHash'] = Mage::getSingleton('ecomdev_checkitout/hash')->getHash(
            $this->getOnepage()->getQuote()
        );

        return $this;
    }

    /**
     * Overrides default behavior for saving checkout method
     * for adding hash info
     *
     * @return void
     */
    public function saveMethodAction()
    {
        parent::saveMethodAction();
        if ($this->_isActive() && $this->getRequest()->isPost()) {
            $result = Mage::helper('core')->jsonDecode(
                $this->getResponse()->getBody()
            );

            $this->_addHashInfo($result);
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        }
    }

    /**
     * Overrides default behavior for saving shipping method
     * for adding hash info
     *
     * @return void
     */
    public function saveShippingMethodAction()
    {
        parent::saveShippingMethodAction();
        if ($this->_isActive()) {
            $result = Mage::helper('core')->jsonDecode(
                $this->getResponse()->getBody()
            );

            $this->_addHashInfo($result);
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        }
    }

    /**
     * Overrides default behavior for saving payment data
     * for adding hash info
     *
     * @return void
     */
    public function savePaymentAction()
    {
        parent::savePaymentAction();
        if ($this->_isActive()) {
            $result = Mage::helper('core')->jsonDecode(
                $this->getResponse()->getBody()
            );

            $this->_addHashInfo($result);
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        }
    }


    /**
     * Action for changing quantity in already added product
     *
     *
     */
    public function changeQtyAction()
    {
        if (!$this->_isActive()
            || !$this->_getHelper()->isChangeItemQtyAllowed()) {
            $this->norouteAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }

        $result = array();

        $quoteItem = $this->getOnepage()->getQuote()->getItemById(
            $this->getRequest()->getPost('item_id')
        );

        if ($quoteItem) {
            $quoteItem->setQty(
                $this->getRequest()->getPost('qty')
            );

            try {
                if ($quoteItem->getHasError() === true) {
                    $result['error'] = $quoteItem->getMessage(true);
                } else {
                    $this->_recalculateTotals();
                    $result['success'] = true;
                }
            } catch (Mage_Core_Exception $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                Mage::logException($e);
                $result['error'] = Mage::helper('ecomdev_checkitout')->__('There was an error during changing product qty');
            }
        } else {
            $result['error'] = Mage::helper('ecomdev_checkitout')->__('Product was not found');
        }

        $this->_addHashInfo($result);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Action for removing product from cart
     *
     */
    public function removeAction()
    {
        if (!$this->_isActive()
            || !$this->_getHelper()->isRemoveItemAllowed()) {
            $this->norouteAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }

        $result = array();

        $quoteItem = $this->getOnepage()->getQuote()->getItemById(
            $this->getRequest()->getPost('item_id')
        );

        if ($quoteItem) {
            try {
                $this->getOnepage()->getQuote()
                    ->removeItem($quoteItem->getId());
                $this->_recalculateTotals();
                $result['success'] = true;
            } catch (Mage_Core_Exception $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                Mage::logException($e);
                $result['error'] = Mage::helper('ecomdev_checkitout')->__('There was an error during removing product from order');
            }
        } else {
            $result['error'] = Mage::helper('ecomdev_checkitout')->__('Product was not found');
        }

        $this->_addHashInfo($result);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Recalculates totals for quote
     *
     * @return EcomDev_CheckItOut_OnepageController
     */
    protected function _recalculateTotals()
    {
        $this->getOnepage()->recalculateTotals();
        return $this;
    }

    /**
     * Overrides default behavior for saving order with shipping and billing
     *
     * @return void
     */
    public function saveOrderAction()
    {
        if (!$this->_isActive()) {
            parent::saveOrderAction();
            return;
        }

        if ($this->_expireAjax()) {
            return;
        }
        $orderData = $this->getRequest()->getPost('order');

        if ($this->_getHelper()->isCustomerCommentAllowed()
            && isset($orderData['customer_comment'])) {
            $this->getOnepage()->getQuote()->setCustomerComment($orderData['customer_comment']);
        }

        if ($this->_getHelper()->isPaymentMethodHidden()) {
            // Issue with not submitted form details if payment method is hidden
            $post = $this->getRequest()->getPost();
            $post['payment']['method'] = $this->_getHelper()->getDefaultPaymentMethod();
            $this->getRequest()->setPost($post);
        }

        parent::saveOrderAction();

        // If order is created and there is enabled subscription
        if ($this->getOnepage()->getCheckout()->getLastOrderId()
            && $this->_getHelper()->isNewsletterCheckboxDisplay()
            && $this->getRequest()->getPost('newsletter')) {
            try {
                Mage::getModel('newsletter/subscriber')->subscribe(
                    $this->getOnepage()->getQuote()->getCustomerEmail()
                );
            } catch (Exception $e) {
                // Subscription shouldn't break checkout, so we just log exception
                Mage::logException($e);
            }
        }

        return $this;
    }

    /**
     * Applies coupon code the quote
     *
     *
     */
    public function applyCouponAction()
    {
        if (!$this->_isActive()) {
            $this->norouteAction();
            return;
        }

        $isRemove = (bool)$this->getRequest()->getParam('remove', false);
        $coupon = $this->getRequest()->getParam('coupon');
        if ($isRemove) {
            $coupon = null;
        }

        $result = $this->getOnepage()->saveCouponCode($coupon);

        $this->_addHashInfo($result);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
