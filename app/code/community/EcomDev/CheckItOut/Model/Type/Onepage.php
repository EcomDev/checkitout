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
 * Simple dependency injection for checkout object
 *
 * @extends Mage_Checkout_Model_Type_Onepage
 * @method Mage_Sales_Model_Quote getQuote()
 */
class EcomDev_CheckItOut_Model_Type_Onepage
{

    protected $_dependency = null;


    /**
     * Initializes checkout object
     *
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    public function initCheckout()
    {
        $this->_getDependency()->initCheckout();

        $recalculateTotals = false;
        if (!$this->getQuote()->getBillingAddress()->getCountryId()) {
            $this->getQuote()->getBillingAddress()->setCountryId(
                Mage::helper('ecomdev_checkitout')->getDefaultCountry()
            );
            $recalculateTotals = true;
        }

        if (!$this->getQuote()->isVirtual()
            && !$this->getQuote()->getShippingAddress()->getCountryId()) {
            $this->getQuote()->getShippingAddress()->setCountryId(
                Mage::helper('ecomdev_checkitout')->getDefaultCountry()
            );
            $recalculateTotals = true;
        }

        if ($recalculateTotals) {
            $this->recalculateTotals();
        }

        if (!$this->getQuote()->isVirtual()
            && Mage::helper('ecomdev_checkitout')->getDefaultShippingMethod()) {
            $this->saveShippingMethod(
                Mage::helper('ecomdev_checkitout')->getDefaultShippingMethod()
            );
        }

        if (Mage::helper('ecomdev_checkitout')->isPaymentMethodHidden()) {
            $this->savePayment(array(
                'method' => Mage::helper('ecomdev_checkitout')->getDefaultPaymentMethod()
            ));
            $this->recalculateTotals();
        } elseif (Mage::helper('ecomdev_checkitout')->getDefaultPaymentMethod()) {
            if ($this->getQuote()->isVirtual()) {
                $address = $this->getQuote()->getBillingAddress();
            } else {
                $address = $this->getQuote()->getShippingAddress();
            }
            $address->setPaymentMethod(
                Mage::helper('ecomdev_checkitout')->getDefaultPaymentMethod()
            );

        }



        return $this;
    }

    /**
     * Dependency injection implementation of saveBilling
     *
     * @param array $data
     * @param $customerAddressId
     * @return array
     */
    public function saveBilling($data, $customerAddressId)
    {
        $result = $this->_getDependency()->saveBilling($data, $customerAddressId);

        if (!$this->getQuote()->getCustomerId()
            && $this->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->loadByEmail($data['email']);
            $loginAction = Mage::registry('login_action_text');
            if ($customer->getId()) {
                $result = array(
                    'error' => 1,
                    'message' => Mage::helper('ecomdev_checkitout')->__('You are already registered with this email address, please %s.', $loginAction),
                    'field' => 'email'
                );
            }
        }

        if (isset($result['error'])) {
            $this->getQuote()->getBillingAddress()
                ->addData($this->_filterAddressData($data))
                ->implodeStreetAddress();

            if (!$this->getQuote()->isVirtual()
                && !empty($data['use_for_shipping'])) {
                $billing = clone $this->getQuote()->getBillingAddress();
                $billing->unsAddressId()->unsAddressType();
                $shipping = $this->getQuote()->getShippingAddress();
                $shippingMethod = $shipping->getShippingMethod();
                $shipping->addData($billing->getData())
                    ->setSameAsBilling(1)
                    ->setSaveInAddressBook(0)
                    ->setShippingMethod($shippingMethod);
            }

            $this->recalculateTotals();
        }

        return $result;
    }

    /**
     * Dependency injection implementation of saveShipping
     *
     * @param array $data
     * @param $customerAddressId
     * @return array
     */
    public function saveShipping($data, $customerAddressId)
    {
        $result = $this->_getDependency()->saveShipping($data, $customerAddressId);

        if (isset($result['error'])) {
            $this->getQuote()
                ->getShippingAddress()
                ->addData($this->_filterAddressData($data))
                ->implodeStreetAddress();

            $this->recalculateTotals();
        }

        return $result;
    }

    /**
     * Recalculates totals for checkout object
     * s
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    public function recalculateTotals()
    {
        if (!$this->getQuote()->isVirtual()) {
            $this->getQuote()
                ->getShippingAddress()->setCollectShippingRates(true);
        }

        $this->getQuote()->setTotalsCollectedFlag(false);
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();
        return $this;
    }

    /**
     * Filters post address data into available address attributes
     *
     * @param array $data
     * @return array
     */
    protected function _filterAddressData($data)
    {
        $customerAddressAttributes = Mage::getSingleton('eav/config')
            ->getEntityAttributeCodes('customer_address');

        $dataToApply = array();
        foreach ($customerAddressAttributes as $attributeCode) {
            if (isset($data[$attributeCode])) {
                $dataToApply[$attributeCode] = $data[$attributeCode];
            }
        }

        return $dataToApply;
    }

    /**
     * Onepage checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _getDependency()
    {
        if ($this->_dependency === null) {
            $this->_dependency = Mage::getSingleton('checkout/type_onepage');
        }

        return $this->_dependency;
    }

    /**
     * If method is not redefined call parent
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $result = call_user_func_array(array($this->_getDependency(), $method), $args);
        if ($result === $this->_getDependency()) {
            return $this; // Return self calls
        }

        return $result;
    }
}