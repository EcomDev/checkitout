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

require_once 'Mage/Checkout/controllers/OnepageController.php';

/**
 * CheckItOut override of onepage checkout controller
 *
 */
class EcomDev_CheckItOut_OnepageController extends Mage_Checkout_OnepageController
{
    const LAYOUT_HANDLE_BASE = 'ecomdev_checkitout_layout';
    const LAYOUT_HANDLE_NO_PAYMENT = 'ecomdev_checkitout_no_payment';

    /**
     * List of action names that require adding of base handle
     *
     * @var array
     */
    protected $_addBaseHandleToActions = array('index', 'layout', 'steps');

    /**
     * Checks that CheckItOut extension is active for current website
     *
     * @return boolean
     */
    protected function _isActive()
    {
        return Mage::helper('ecomdev_checkitout')->isActive();
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
     * @see Mage_Core_Controller_Varien_Action::addActionLayoutHandles()
     */
    public function addActionLayoutHandles()
    {
        parent::addActionLayoutHandles();
        if ($this->_isActive()
            && in_array($this->getRequest()->getActionName(), $this->_addBaseHandleToActions)) {
            $this->getLayout()->getUpdate()->addHandle(self::LAYOUT_HANDLE_BASE);
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

        if ($this->_isActive() && !$this->getOnepage()->getQuote()->isVirtual()
            && !$this->getOnepage()->getQuote()->getShippingAddress()->getCountryId()) {
            $this->getOnepage()->getQuote()->getShippingAddress()->setCountryId(
                Mage::getStoreConfig('general/country/default')
            );
            $this->_recalculateTotals();
        }
        return $this;
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
            $result[$step] = $this->getLayout()->getBlock('checkout.layout')->getStepBlockHtml($step);
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

            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!$this->getOnepage()->getQuote()->getCustomerId()
                && $this->getOnepage()->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
                $customer = Mage::getModel('customer/customer');
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                $customer->loadByEmail($data['email']);
                if ($customer->getId()) {
                    $result = array(
                        'error' => 1,
                        'message' => Mage::helper('ecomdev_checkitout')->__('You are already registered with this email address, please <a href="#" onclick="%s">log in</a>.', 'loginStep.showPopUp(); return false;'),
                        'field' => 'email'
                    );
                }
            }

            if (isset($result['error'])) {
                $this->getOnepage()->getQuote()->getBillingAddress()
                    ->addData($data)
                    ->implodeStreetAddress();

                if (!$this->getOnepage()->getQuote()->isVirtual()
                    && !empty($data['use_for_shipping'])) {
                    $billing = clone $this->getOnepage()->getQuote()->getBillingAddress();
                    $billing->unsAddressId()->unsAddressType();
                    $shipping = $this->getOnepage()->getQuote()->getShippingAddress();
                    $shippingMethod = $shipping->getShippingMethod();
                    $shipping->addData($billing->getData())
                        ->setSameAsBilling(1)
                        ->setSaveInAddressBook(0)
                        ->setShippingMethod($shippingMethod);
                }

                $this->_recalculateTotals();
            }

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

            if (isset($result['error'])) {
                $this->getOnepage()->getQuote()->getShippingAddress()
                    ->addData($data)
                    ->implodeStreetAddress();
                $this->_recalculateTotals();
            }

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
            || !Mage::helper('ecomdev_checkitout')->isChangeItemQtyAllowed()) {
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
            || !Mage::helper('ecomdev_checkitout')->isRemoveItemAllowed()) {
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
        if (!$this->getOnepage()->getQuote()->isVirtual()) {
            $this->getOnepage()->getQuote()
                ->getShippingAddress()->setCollectShippingRates(true);
        }

        $this->getOnepage()->getQuote()->collectTotals();
        $this->getOnepage()->getQuote()->save();
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

        if (Mage::helper('ecomdev_checkitout')->isCustomerCommentAllowed()
            && isset($orderData['customer_comment'])) {
            $this->getOnepage()->getQuote()->setCustomerComment($orderData['customer_comment']);
        }

        $postData = $this->getRequest()->getPost('billing', array());
        $data = $this->_filterPostData($postData);
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        if (isset($data['email'])) {
            $data['email'] = trim($data['email']);
        }

        $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

        if (isset($result['error'])) {
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
            return;
        }

        if (!$this->getOnepage()->getQuote()->isVirtual()) {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

            if (isset($result['error'])) {
                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode($result)
                );
                return;
            }
        }

        parent::saveOrderAction();

        // If order is created and there is enabled subscription
        if ($this->getOnepage()->getCheckout()->getLastOrderId()
            && Mage::helper('ecomdev_checkitout')->isNewsletterCheckboxDisplay()
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
}
