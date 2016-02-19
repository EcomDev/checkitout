<?php
/**
 * CheckItOut extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_CheckItOut
 * @copyright  Copyright (c) 2016 EcomDev BV (http://www.ecomdev.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Simple dependency injection for checkout object
 *
 * @extends Mage_Checkout_Model_Type_Onepage
 * @method Mage_Sales_Model_Quote getQuote()
 * @method Mage_Checkout_Model_Session getCheckout()
 */
class EcomDev_CheckItOut_Model_Type_Onepage
{
    protected $_dependency = null;

    /**
     * Dispatches event for checkout method
     *
     * @param string $method
     * @param string $suffix
     * @param array  $eventArgs
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    protected function dispatchEvent($method, $suffix, $eventArgs = array())
    {
        $eventArgs['onepage'] = $this;
        $eventArgs['quote'] = $this->getQuote();
        if (strtolower($method) !== $method) {
            // Uncamelize method name
            $method = strtolower(preg_replace('/[A-Z]/', '_\\0', $method));
        }
        Mage::dispatchEvent('ecomdev_checkitout_checkout_' . $method . '_' . $suffix, $eventArgs);
        return $this;
    }

    /**
     * Checks if address location info is empty,
     * e.g. no data were set
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return bool
     */
    public function isLocationInfoEmpty($address)
    {
        $response = new Varien_Object(
            array('is_empty' => true)
        );

        $this->dispatchEvent(__FUNCTION__, 'before', array(
            'address' => $address,
            'response' => $response
        ));

        foreach (array('postcode', 'street', 'city',
                     'country_id', 'region_id') as $attribute) {
            if ($address->getData($attribute)) {
                $response->setIsEmpty(false);
                break;
            }
        }

        $this->dispatchEvent(__FUNCTION__, 'after', array(
            'address' => $address,
            'response' => $response
        ));

        return $response->getIsEmpty();
    }

    /**
     * Returns helper instance
     *
     * @return EcomDev_CheckItOut_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('ecomdev_checkitout');
    }

    /**
     * Initializes checkout object
     *
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    public function initCheckout()
    {
        $this->dispatchEvent(__FUNCTION__, 'before');
        $this->_getHelper()->resetDefaultAddress();
        $this->_getDependency()->initCheckout();

        $recalculateTotals = false;
        if ($this->isLocationInfoEmpty($this->getQuote()->getBillingAddress())) {
            $this->getQuote()->getBillingAddress()->addData(
                $this->_getHelper()->getDefaultAddress()->getData()
            );
            $recalculateTotals = true;
        }

        if (!$this->getQuote()->isVirtual()
            && $this->isLocationInfoEmpty($this->getQuote()->getShippingAddress())
            && $this->_getHelper()->isShipmentSameByDefault()) {
            $this->getQuote()->getShippingAddress()->addData(
                $this->_getHelper()->getDefaultAddress()->getData() + array('same_as_billing' => 1)
            );
            $recalculateTotals = true;
        }

        if (!$this->getQuote()->isVirtual()
            && !$this->getQuote()->getShippingAddress()->getShippingMethod()
            && $this->_getHelper()->getDefaultShippingMethod($this->getQuote())) {
            $this->getQuote()->getShippingAddress()->setShippingMethod(
                $this->_getHelper()->getDefaultShippingMethod($this->getQuote())
            );
            $recalculateTotals = true;
        }

        if ($recalculateTotals) {
            $this->recalculateTotals();
        }

        if ($this->_getHelper()->isPaymentMethodHidden()) {
            $this->savePayment(array(
                'method' => $this->_getHelper()->getDefaultPaymentMethod($this->getQuote())
            ));
            $this->recalculateTotals(true);
        } elseif ($this->_getHelper()->getDefaultPaymentMethod($this->getQuote())
            && !$this->getQuote()->getPayment()->getMethod()) {
            $this->getQuote()->getPayment()->setMethod($this->_getHelper()->getDefaultPaymentMethod($this->getQuote()));
        }

        $this->dispatchEvent(__FUNCTION__, 'after');

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
        $response = new Varien_Object();
        $this->dispatchEvent(__FUNCTION__, 'before', array(
            'response' => $response,
            'coupon_code' => $couponCode
        ));

        $oldCouponeCode = $this->getQuote()->getCouponCode();
        if ($oldCouponeCode === $couponCode) {
            $response->addData(array(
                'error' => true,
                'message' => $this->_getHelper()->__('The coupon code is already applied.'),
                'field' => 'coupon'
            ));
        }

        if (!$response->getError()) {
            $this->getQuote()->setCouponCode($couponCode);
            $this->recalculateTotals();
            if ($couponCode !== null
                && $this->getQuote()->getCouponCode() !== $couponCode) {
                $response->addData(array(
                    'error' => true,
                    'message' => $this->_getHelper()->__('The coupon code is invalid.'),
                    'field' => 'coupon'
                ));
            } else {
                $response->addData(
                    array(
                        'success' => true,
                        'coupon' => $this->getQuote()->getCouponCode()
                    )
                );
            }
        }

        $this->dispatchEvent(__FUNCTION__, 'after', array('response' => $response));
        return $response->getData();
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
        $response = new Varien_Object();

        $this->dispatchEvent(__FUNCTION__, 'before', array(
            'response' => $response,
            'address_data' => $data,
            'address' => $this->getQuote()->getBillingAddress(),
            'customer_address_id' => $customerAddressId
        ));

        $quoteReflection = new ReflectionObject($this->getQuote());

        // Added because of strange core behaviour on standard save operation
        if ($quoteReflection->hasProperty('_preventSaving')) {
            $property = $quoteReflection->getProperty('_preventSaving');
            $property->setAccessible(true);
            $property->setValue($this->getQuote(), true);
        }

        $result = $this->_getDependency()->saveBilling($data, $customerAddressId);

        if (isset($property)) {
            $property->setValue($this->getQuote(), false);
            $property->setAccessible(false);
        }

        if (!$this->getQuote()->getCustomerId()
            && $this->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->loadByEmail($data['email']);
            $loginAction = Mage::registry('login_action_text');
            if ($customer->getId()) {
                $result = array(
                    'error' => 1,
                    'message' => $this->_getHelper()
                            ->__('You are already registered with this email address, please %s.', $loginAction),
                    'field' => 'email',
                    'value' => $customer->getEmail()
                );
            } elseif (isset($result['error'])) {
                $this->getQuote()->getBillingAddress()
                    ->setEmail($data['email']);
            }
        }

        if (!empty($data)
            && isset($result['error'])  && $result['error'] == -1
            && !is_array($result['message'])) {
            $result['field'] = 'email'; // Usually -1 indicates customer account email errors
            $result['error'] = 1; // Make it true for js
            $result['value'] = isset($data['email']) ? $data['email'] : '';
        }

        $recalculateTotals = true;
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
                $shipping->addData($this->_filterAddressData($billing->getData()))
                    ->setSameAsBilling(1)
                    ->setSaveInAddressBook(0)
                    ->setShippingMethod($shippingMethod);
                $this->dispatchEvent(__FUNCTION__, 'copy_address', array(
                    'shipping_address' => $shipping,
                    'billing_address' => $billing
                ));
            }
        }

        if (!$this->getQuote()->isVirtual() && !empty($data['use_for_shipping'])
            && !$this->getQuote()->getShippingAddress()->getShippingMethod()) {
            $this->getQuote()->getShippingAddress()->setShippingMethod(
                $this->_getHelper()->getDefaultShippingMethod($this->getQuote())
            );
        }

        if ($recalculateTotals) {
            $this->recalculateTotals();
        }

        $response->addData($result);

        $this->dispatchEvent(__FUNCTION__, 'after', array(
            'response' => $response,
            'address' => $this->getQuote()->getBillingAddress()
        ));

        return $response->getData();
    }

    /**
     * Save Shipping Proxy Call
     *
     * @param array $data
     * @param $customerAddressId
     * @return array
     */
    public function saveShipping($data, $customerAddressId)
    {
        $response = new Varien_Object();
        $this->dispatchEvent(__FUNCTION__, 'before', array(
            'response' => $response,
            'address_data' => $data,
            'address' => $this->getQuote()->getShippingAddress(),
            'customer_address_id' => $customerAddressId
        ));

        $result = $this->_getDependency()->saveShipping($data, $customerAddressId);

        $recalculateTotals = false;
        if (isset($result['error'])) {
            $this->getQuote()
                ->getShippingAddress()
                ->addData($this->_filterAddressData($data))
                ->implodeStreetAddress();
            $recalculateTotals = true;
        }

        if (!$this->getQuote()->getShippingAddress()->getShippingMethod()) {
            $this->getQuote()->getShippingAddress()->setShippingMethod(
                $this->_getHelper()->getDefaultShippingMethod($this->getQuote())
            );
            $recalculateTotals = true;
        }

        if ($recalculateTotals) {
            $this->recalculateTotals();
        }

        $response->addData($result);

        $this->dispatchEvent(__FUNCTION__, 'after', array(
            'response' => $response,
            'address' => $this->getQuote()->getShippingAddress()
        ));

        return $response->getData();
    }

    /**
     * Invokes customer data validation in core functionality
     *
     * Works only on php version higher then 5.3
     *
     * @param array $data
     * @return bool
     */
    public function validateCustomerData($data)
    {
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            $reflection = new ReflectionObject($this->_getDependency());
            $method = $reflection->getMethod('_validateCustomerData');
            $method->setAccessible(true);
            return $method->invokeArgs($this->_getDependency(), array($data));
        }

        return true;
    }

    /**
     * Recalculates totals for checkout object
     *
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    public function recalculateTotals()
    {
        $this->dispatchEvent(__FUNCTION__, 'before');
        if (!$this->getQuote()->isVirtual()) {
            $this->getQuote()
                ->getShippingAddress()->setCollectShippingRates(true);
        }

        $addressToResetData = $this->getQuote()->getBillingAddress();

        if (!$this->getQuote()->isVirtual()) {
            $addressToResetData = $this->getQuote()->getShippingAddress();
        }

        $addressToResetData->unsCachedItemsAll();
        $addressToResetData->unsCachedItemsNominal();
        $addressToResetData->unsCachedItemsNonNominal();

        $this->getQuote()->setTotalsCollectedFlag(false);
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();

        $this->dispatchEvent(__FUNCTION__, 'after');
        return $this;
    }


    /**
     * Stubs payment method some of the checkout steps,
     * that require it to be set
     *
     * @return EcomDev_CheckItOut_Model_Type_Onepage
     */
    public function stubPaymentMethod()
    {
        $this->dispatchEvent(__FUNCTION__, 'before');

        if (!$this->getQuote()->getPayment()->getMethod()) {
            $methods = Mage::helper('payment')->getStoreMethods($this->getQuote()->getStore(), $this->getQuote());
            foreach ($methods as $method) {
                $this->getQuote()->getPayment()->setMethod($method->getCode());
                if (method_exists($this->getQuote(), 'preventSaving')) {
                    $this->getQuote()->preventSaving();
                }
                break;
            }
        }

        $this->dispatchEvent(__FUNCTION__, 'after');
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
