<?php

require_once 'Mage/Checkout/controllers/OnepageController.php';

/**
 * CheckItOut override of onepage checkout controller
 *
 */
class EcomDev_CheckItOut_OnepageController extends Mage_Checkout_OnepageController
{
    /**
     * Retrieve steps blocks from checkout
     * and sets response as JSON
     *
     */
    public function stepsAction()
    {
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
            $result[$step] = $this->getLayout()->getBlock('checkout')->getStepBlockHtml($step);
        }

        if (empty($result)) {
            $resultJSON = '{}';
        } else {
            $resultJSON = Mage::helper('core')->jsonEncode($result);
        }

        $this->getResponse()->setBody($resultJSON);
    }

    /**
     * Retrieves all layout of checkout page and return html
     *
     */
    public function layoutAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->getBlock('checkout')->toHtml()
        );
    }

    /**
     * Overrides default behavior for saving billing address
     *
     * @return void
     */
    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost('billing', array());
            $data = $this->_filterPostData($postData);
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);
            if (isset($result['error'])) {
                $this->getOnepage()->getQuote()->getBillingAddress()
                    ->implodeStreetAddress();
                $this->getOnepage()->getQuote()->collectTotals();
                $this->getOnepage()->getQuote()->save();
            }

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
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

            if (isset($result['error'])) {
                $this->getOnepage()->getQuote()->getBillingAddress()
                    ->implodeStreetAddress()
                    ->setCollectShippingRates(true);
                $this->getOnepage()->getQuote()->collectTotals();
                $this->getOnepage()->getQuote()->save();
            }

            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        }
    }

    /**
     * Overrides default behavior for saving order with shipping and billing
     *
     * @return void
     */
    public function saveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $orderData = $this->getRequest()->getPost('order');

        if (isset($orderData['customer_comment'])) {
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
        return $this;
    }
}
