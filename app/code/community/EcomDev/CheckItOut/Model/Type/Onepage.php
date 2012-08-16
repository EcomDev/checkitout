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
 * Simple dependency injection for checkout object
 *
 * @extends Mage_Checkout_Model_Type_Onepage
 * @method Mage_Sales_Model_Quote getQuote()
 */
class EcomDev_CheckItOut_Model_Type_Onepage
{

    protected $_dependency = null;

    /**
     * Checks if address location info is empty,
     * e.g. no data were set
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return bool
     */
    public function isLocationInfoEmpty($address)
    {
        return !array_filter($address->toArray(
            'postcode', 'street', 'city', 'country_id', 'region_id', 'region'
        ));
    }

    /**
     * Initializes checkout object
     *
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    public function initCheckout()
    {
        Mage::helper('ecomdev_checkitout')->resetDefaultAddress();
        $this->_getDependency()->initCheckout();

        $recalculateTotals = false;
        if (!$this->isLocationInfoEmpty($this->getQuote()->getBillingAddress())) {
            $this->getQuote()->getBillingAddress()->addData(
                Mage::helper('ecomdev_checkitout')->getDefaultAddress()->getData()
            );
            $recalculateTotals = true;
        }

        if (!$this->getQuote()->isVirtual()
            && !$this->isLocationInfoEmpty($this->getQuote()->getShippingAddress())
            && Mage::helper('ecomdev_checkitout')->isShipmentSameByDefault()) {
            $this->getQuote()->getShippingAddress()->addData(
                Mage::helper('ecomdev_checkitout')->getDefaultAddress()->getData() + array('same_as_billing' => 1)
            );
            $recalculateTotals = true;
        }

        if ($recalculateTotals) {
            $this->recalculateTotals();
        }

        if (!$this->getQuote()->isVirtual()
            && !$this->getQuote()->getShippingAddress()->getShippingMethod()
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
     * Applies the coupon code to the shopping cart
     *
     * @param string $couponCode
     * @return array|boolean
     */
    public function saveCouponCode($couponCode)
    {
        $oldCouponeCode = $this->getQuote()->getCouponCode();
        if ($oldCouponeCode === $couponCode) {
            return array(
                'error' => true,
                'message' => Mage::helper('ecomdev_checkitout')->__('The coupon code is already applied.'),
                'field' => 'coupon'
            );
        }
        $this->getQuote()->setCouponCode($couponCode);
        $this->recalculateTotals();
        if ($couponCode !== null
            && $this->getQuote()->getCouponCode() !== $couponCode) {
            return array(
                'error' => true,
                'message' => Mage::helper('ecomdev_checkitout')->__('The coupon code is invalid.'),
                'field' => 'coupon'
            );
        }

        return array('success' => true, 'coupon' => $this->getQuote()->getCouponCode());
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

        if (!$this->getQuote()->isVirtual() && !empty($data['use_for_shipping'])
            && !$this->getQuote()->getShippingAddress()->getShippingMethod()) {
            $this->getQuote()->getShippingAddress()->setShippingMethod(
                Mage::helper('ecomdev_checkitout')->getDefaultShippingMethod()
            );
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

        if (!$this->getQuote()->getShippingAddress()->getShippingMethod()) {
            $this->getQuote()->getShippingAddress()->setShippingMethod(
                Mage::helper('ecomdev_checkitout')->getDefaultShippingMethod()
            );
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