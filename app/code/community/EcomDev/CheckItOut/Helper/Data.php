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
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Module helper, used for translations
 *
 * @author Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */
class EcomDev_CheckItOut_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ACTIVE = 'ecomdev_checkitout/settings/active';
    const XML_PATH_CUSTOMER_COMMENT = 'ecomdev_checkitout/settings/customer_comment';
    const XML_PATH_CHANGE_ITEM_QTY = 'ecomdev_checkitout/settings/change_item_qty';
    const XML_PATH_REMOVE_ITEM = 'ecomdev_checkitout/settings/remove_item';
    const XML_PATH_CONFIRM_TYPE = 'ecomdev_checkitout/settings/confirm_type';
    const XML_PATH_CONFIRM_TEXT = 'ecomdev_checkitout/settings/confirm_text';
    const XML_PATH_NEWSLETTER_CHECKBOX = 'ecomdev_checkitout/settings/newsletter_checkbox';
    const XML_PATH_NEWSLETTER_CHECKBOX_CHECKED = 'ecomdev_checkitout/settings/newsletter_checkbox_checked';
    const XML_PATH_STORED_ADDRESSES = 'ecomdev_checkitout/settings/stored_addresses';
    const XML_PATH_SHOPPING_CART_REDIRECT = 'ecomdev_checkitout/settings/shopping_cart_redirect';


    // New layout feature configurations
    const XML_PATH_DESIGN_ACTIVE = 'ecomdev_checkitout/design/active';
    const XML_PATH_DESIGN_LAYOUT = 'ecomdev_checkitout/design/layout';
    const XML_PATH_DESIGN_CSS = 'ecomdev_checkitout/design/css';
    const XML_PATH_DESIGN_CUSTOM_CSS = 'ecomdev_checkitout/design/%s_css';

    // Default payment, shipping methods
    const XML_PATH_DEFAULT_SHIPPING_METHOD = 'ecomdev_checkitout/default/shipping_method';
    const XML_PATH_DEFAULT_PAYMENT_METHOD = 'ecomdev_checkitout/default/payment_method';
    const XML_PATH_DEFAULT_SAME_AS_BILLING = 'ecomdev_checkitout/default/same_as_billing';
    const XML_PATH_DEFAULT_REGION = 'ecomdev_checkitout/default/region_id';
    const XML_PATH_DEFAULT_POSTCODE = 'ecomdev_checkitout/default/postcode';
    const XML_PATH_DEFAULT_CITY = 'ecomdev_checkitout/default/city';

    // GeoIp configurations
    const XML_PATH_GEOIP_TYPE = 'ecomdev_checkitout/geoip/type';

    // Custom router
    const XML_PATH_CUSTOM_ROUTER_ACTIVE = 'ecomdev_checkitout/router/active';
    const XML_PATH_CUSTOM_ROUTER_PATH = 'ecomdev_checkitout/router/path';

    // Hide options for payment, shipping methods
    const XML_PATH_HIDE_SHIPPING_METHOD = 'ecomdev_checkitout/hidden/shipping_method';
    const XML_PATH_HIDE_PAYMENT_METHOD = 'ecomdev_checkitout/hidden/payment_method';
    const XML_PATH_HIDE_COUPON_CODE = 'ecomdev_checkitout/hidden/coupon_code';

    const XML_PATH_COMPATIBILITY = 'ecomdev/checkitout/compatibility/%s';
    const XML_PATH_STEPS = 'ecomdev/checkitout/steps';
    const XML_PATH_DEFAULT_COUNTRY = 'general/country/default';

    const COMPATIBILITY_TYPE_TEMPLATE = 'template';
    const COMPATIBILITY_TYPE_CODE = 'code';
    const COMPATIBILITY_TYPE_JS = 'js';
    const COMPATIBILITY_V14 = 'v14';
    const COMPATIBILITY_V15 = 'v15';
    const COMPATIBILITY_V18 = 'v18';

    const CONFIRM_TYPE_CHECKBOX = EcomDev_CheckItOut_Model_Source_Confirm_Type::TYPE_CHECKBOX;
    const CONFIRM_TYPE_POPUP = EcomDev_CheckItOut_Model_Source_Confirm_Type::TYPE_POPUP;
    const CONFIRM_TYPE_NONE = EcomDev_CheckItOut_Model_Source_Confirm_Type::TYPE_NONE;

    /**
     * Default address object
     *
     * @var Varien_Object
     */
    protected $_defaultAddress = null;

    /**
     * Checks that checkitout extension is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE);
    }

    /**
     * Check that checkitout has a custom router set
     *
     * @return bool
     */
    public function isCustomRouter()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CUSTOM_ROUTER_ACTIVE)
                   && $this->getCustomRoute();
    }

    /**
     * Check that checkitout has a custom router set
     *
     * @return bool
     */
    public function getCustomRoute()
    {
        return Mage::getStoreConfig(self::XML_PATH_CUSTOM_ROUTER_PATH);
    }

    /**
     * Return activity state of the checkitout by current session
     *
     * @return bool
     */
    public function isActiveForSession()
    {
        if (!$this->isActive()) {
            return false;
        }

        if (!$this->isCustomRouter()) {
            return true;
        }

        $session = Mage::getSingleton('checkout/session');
        $isActive = $session->getIsActiveCheckItOut();

        return $isActive;
    }

    /**
     * Checks that customer is allowed to enter custom message
     *
     * @return boolean
     */
    public function isCustomerCommentAllowed()
    {
        return $this->isActive()
               && Mage::getStoreConfigFlag(self::XML_PATH_CUSTOMER_COMMENT);
    }

    /**
     * Check that it is currently a guest checkout
     *
     * @return boolean
     */
    public function isGuestCheckout()
    {
        $checkoutModel = Mage::getSingleton('checkout/type_onepage');
        return $checkoutModel->getCheckoutMethod() === Mage_Checkout_Model_Type_Onepage::METHOD_GUEST;
    }

    /**
     * Check if confirmation type is checkbox
     *
     * @return boolean
     */
    public function isConfirmCheckbox()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIRM_TYPE) == self::CONFIRM_TYPE_CHECKBOX;
    }

    /**
     * Check if confirmation type is popup
     *
     * @return boolean
     */
    public function isConfirmPopUp()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIRM_TYPE) == self::CONFIRM_TYPE_POPUP;
    }

    /**
     * Retrieves confirm text
     *
     * @return string
     */
    public function getConfirmText()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIRM_TEXT);
    }

    /**
     * Check if item qty change is allowed
     *
     * @return boolean
     */
    public function isChangeItemQtyAllowed()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CHANGE_ITEM_QTY);
    }

	/**
     * Check if item removal is allowed
     *
     * @return boolean
     */
    public function isRemoveItemAllowed()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_REMOVE_ITEM);
    }

    /**
     * Check if it is required to display newsletter sign up checkbox
     *
     * @return boolean
     */
    public function isNewsletterCheckboxDisplay()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_NEWSLETTER_CHECKBOX) && !$this->isCustomerSubscribedToNewsletter();
    }

    /**
     * Check if newsletter signup checkbox is checked by default
     *
     * @return boolean
     */
    public function isNewsletterCheckboxChecked()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_NEWSLETTER_CHECKBOX_CHECKED);
    }

    /**
     * Checks that customer already subscribed to the newsletter
     *
     * @return boolean
     */
    public function isCustomerSubscribedToNewsletter()
    {
        // Non logged in cannot be checked
        if (!Mage::helper('customer')->isLoggedIn()) {
            return false;
        }

        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::helper('customer')->getCustomer();

        /* @var $subscriber Mage_Newsletter_Model_Subscriber */
        // Cache subscriber into customer instance
        if (!$customer->hasData('subscriber_model')) {
            $subscriber = Mage::getModel('newsletter/subscriber');
            $customer->setData('subscriber_model', $subscriber);
            // Prevent auto-subscription
            $subscriber->setCustomerId($customer->getId());
            $subscriber->loadByCustomer($customer);
        } else {
            $subscriber = $customer->getData('subscriber_model');
        }


        return $subscriber->isSubscribed();
    }

    /**
     * Checks that guest checkout is allowed
     *
     * @return boolean
     */
    public function isAllowedGuestCheckout()
    {
        $quote = Mage::helper('checkout')->getQuote();

        return Mage::helper('checkout')->isAllowedGuestCheckout($quote);
    }

    /**
     * Returns items JSON info
     *
     * @param array $items
     * @return string
     */
    public function getItemsInfoJson($items)
    {
        $infoModel = Mage::getSingleton('ecomdev_checkitout/quote_item_info');

        $result = array();
        foreach ($items as $item) {
            $result[] = $infoModel->getInfo($item);
        }

        return Mage::helper('core')->jsonEncode($result);
    }

    /**
     * Get compatibility mode for extension code or view part
     *
     *
     * @param string $type type of compatibility (template, code)
     * @param string|null $currentVersion Magento version (null for using version from Mage::getVersion())
     * @return string|boolean
     */
    public function getCompatibilityMode($type, $currentVersion = null)
    {
        if ($currentVersion === null) {
            $currentVersion = Mage::getVersion();
        }

        $modes = Mage::getConfig()->getNode(sprintf(self::XML_PATH_COMPATIBILITY, $type))->children();

        foreach ($modes as $mode) {
            foreach ($mode->children() as $condition) {
                if (isset($condition->enterprise)
                    && Mage::getConfig()->getNode('modules/Enterprise_Enterprise') === false) {
                    // If version check is related to Enterprise/Professional editions
                    continue;
                }

                if (isset($condition->minVersion)
                    && !version_compare($currentVersion, (string)$condition->minVersion, '>=')) {
                    // Minimal version is not matched for this mode
                    continue;
                }

                if (isset($condition->maxVersion)
                    && !version_compare($currentVersion, (string)$condition->maxVersion, '<=')) {
                    // Maximum version is not matched for this mode
                    continue;
                }

                if (isset($condition->configFlag) && !Mage::getStoreConfigFlag((string)$condition->configFlag)) {
                    // This mode requires additional flag in configuration
                    continue;
                }

                return $mode->getName();
            }
        }

        return false;
    }

    /**
     * Returns one of passed values that are valid for compatibility mode
     * in current Magento version
     *
     *
     * @param string $type type of compatibility (template, code)
     * @param array $values
     * @param string|null $currentVersion Magento version (null for using version from Mage::getVersion())
     * @return string|boolean
     */
    public function getCompatibleValue($type, array $values, $currentVersion = null)
    {
        $mode = $this->getCompatibilityMode($type, $currentVersion);
        if (isset($values[$mode])) {
            return $values[$mode];
        }

        return false;
    }

    /**
     * Retrieves default country code from configuration
     *
     * @return string
     */
    public function getDefaultCountry()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_COUNTRY);
    }

    /**
     * Retrieves default region from configuration
     *
     * @return string
     */
    public function getDefaultRegion()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_REGION);
    }

    /**
     * Retrieves default region from configuration
     *
     * @return string
     */
    public function getDefaultPostcode()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_POSTCODE);
    }

    /**
     * Returns default city
     *
     * @return string
     */
    public function getDefaultCity()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_CITY);
    }

    /**
     * Returns default address data
     *
     * @return Varien_Object
     */
    public function getDefaultAddress()
    {
        if ($this->_defaultAddress === null) {
            $object = new Varien_Object();
            if ($this->isGeoIpEnabled()) {
                if ($this->_getRequest()->getParam('ip')) {
                    $ipAddress = $this->_getRequest()->getParam('ip');
                } else {
                    $ipAddress = $this->_getRequest()->getClientIp();
                }
                Mage::getSingleton('ecomdev_checkitout/geoip')->applyLocationByIp($ipAddress, $object);
            }

            $defaultData = array(
                'country_id' => $this->getDefaultCountry(),
                'region_id' => $this->getDefaultRegion(),
                'city' => $this->getDefaultCity(),
                'postcode' => $this->getDefaultPostcode()
            );

            if (!$object->getCountryId()) {
                $object->addData($defaultData);
            } elseif (!$object->getRegionId()
                      && $object->getCountryId() === $this->getDefaultCountry()
                      && !$object->getCity()
                      && !$object->getPostcode()) {
                unset($defaultData['country_id']);
                $object->addData($defaultData);
            } elseif ((!$object->getCity() || !$object->getPostcode())
                      && $object->getRegionId() == $this->getDefaultRegion()
                      && $object->getCountryId() === $this->getDefaultCountry()) {
                unset($defaultData['region_id']);
                unset($defaultData['country_id']);

                if ($object->getCity()) {
                    unset($defaultData['city']);
                }

                if ($object->getPostcode()) {
                    unset($defaultData['postcode']);
                }

                $object->addData($defaultData);
            }
            $this->_defaultAddress = $object;
        }

        return $this->_defaultAddress;
    }

    /**
     * Resets default address property
     *
     * @return EcomDev_CheckItOut_Helper_Data
     */
    public function resetDefaultAddress()
    {
        $this->_defaultAddress = null;
        return $this;
    }

    /**
     * Flag check for same as billing default value
     *
     * @return bool
     */
    public function isShipmentSameByDefault()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEFAULT_SAME_AS_BILLING);
    }

    /**
     * Returns default shipping method from configuration.
     * Also allows to set up this value via observer
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return string
     */
    public function getDefaultShippingMethod($quote = null)
    {
        $proxy = new Varien_Object();
        $proxy->setValue(Mage::getStoreConfig(self::XML_PATH_DEFAULT_SHIPPING_METHOD));

        Mage::dispatchEvent('ecomdev_checkitout_get_default_shipping_method', array(
            'proxy' => $proxy,
            'quote' => $quote
        ));

        return $proxy->getValue();
    }

    /**
     * Returns default payment method from configuration.
     * Also allows to set up this values via observer
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return string
     */
    public function getDefaultPaymentMethod($quote = null)
    {
        $proxy = new Varien_Object();
        $proxy->setValue(Mage::getStoreConfig(self::XML_PATH_DEFAULT_PAYMENT_METHOD));

        Mage::dispatchEvent('ecomdev_checkitout_get_default_payment_method', array(
            'proxy' => $proxy,
            'quote' => $quote
        ));

        return $proxy->getValue();
    }

    /**
     * Check that payment method is hidden
     *
     * @return boolean
     */
    public function isShippingMethodHidden()
    {
        return $this->getDefaultShippingMethod() && Mage::getStoreConfigFlag(self::XML_PATH_HIDE_SHIPPING_METHOD);
    }

    /**
     * Check that payment method is hidden
     *
     * @return boolean
     */
    public function isPaymentMethodHidden()
    {
        return $this->getDefaultPaymentMethod() && Mage::getStoreConfigFlag(self::XML_PATH_HIDE_PAYMENT_METHOD);
    }

    /**
     * Check if custom layout is enabled and returning appropriate layout handle,
     * otherwise returns false
     *
     * @return array|boolean
     */
    public function getDesignLayoutHandle()
    {
        if (Mage::getStoreConfigFlag(self::XML_PATH_DESIGN_ACTIVE)) {
            $layoutCode = Mage::getStoreConfig(self::XML_PATH_DESIGN_LAYOUT);
            $layoutOptions = Mage::getSingleton('ecomdev_checkitout/source_design_layout');

            if (!$layoutCode || !$layoutOptions->getOptionByCode($layoutCode)) {
                $layoutOption = current($layoutOptions->getOptions());
            } else {
                $layoutOption = $layoutOptions->getOptionByCode($layoutCode);
            }

            $handles = $layoutOption->getHandles();

            if (!is_array($handles)) {
                $handles = array();
            }

            $handles = array_values($handles);

            if (!$handles && $layoutOption->getHandle()) {
                $handles = array($layoutOption->getHandle());
            }

            if ($handles && $this->isEnterprise()) {
                $handlesToAdd = array();
                foreach ($handles as $handleName) {
                    $handlesToAdd[] = $handleName . '_enterprise';
                }

                $handles = array_merge($handles, $handlesToAdd);
            };

            return $handles;
        }

        return false;
    }

    /**
     * Retrieve list of design files
     *
     * @return array
     */
    public function getDesignFiles()
    {
        $designFiles = array(
            'js' => array(),
            'css' => array()
        );

        if (Mage::getStoreConfigFlag(self::XML_PATH_DESIGN_ACTIVE)) {
            $cssCode = Mage::getStoreConfig(self::XML_PATH_DESIGN_CSS);
            $cssOptions = Mage::getSingleton('ecomdev_checkitout/source_design_css');

            if (!$cssCode || !$cssOptions->getOptionByCode($cssCode)) {
                $cssOption = current($cssOptions->getOptions());
            } else {
                $cssOption = $cssOptions->getOptionByCode($cssCode);
            }

            if (is_array($cssOption->getCss())) {
                foreach ($cssOption->getCss() as $file) {
                    $designFiles['css'][] = $file;
                }
            }

            if (is_array($cssOption->getJs())) {
                foreach ($cssOption->getJs() as $file) {
                    $designFiles['js'][] = $file;
                }
            }

            if ($customCssFile = Mage::getStoreConfig(sprintf(self::XML_PATH_DESIGN_CUSTOM_CSS, $cssCode))) {
                $designFiles['css'][] = $customCssFile;
            }
        }

        return $designFiles;
    }

    /**
     * Returns list of css files that should be included into checkout page
     *
     * @return array
     * @deprecated after 1.4.0
     */
    public function getCssFiles()
    {
        $designFiles = $this->getDesignFiles();
        return $designFiles['css'];
    }

    /**
     * Check that stored addresses should be applied
     *
     * @return bool
     */
    public function isApplyStoredAddresses()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_STORED_ADDRESSES);
    }

    /**
     * Returns configuration flag for availability of coupon code on checkout
     *
     * @return bool
     */
    public function isCouponEnabled()
    {
        return !Mage::getStoreConfigFlag(self::XML_PATH_HIDE_COUPON_CODE);
    }

    /**
     * Checks if current Magento version is an enterprise version
     *
     * @return bool
     */
    public function isEnterprise()
    {
        return Mage::getConfig()->getNode('modules/Enterprise_Enterprise') !== false;
    }

    /**
     * Check if shopping cart redirect feature is enabled
     *
     * @return bool
     */
    public function isShoppingCartRedirectEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SHOPPING_CART_REDIRECT);
    }

    /**
     * Check if geo ip feature enabled
     *
     * @return boolean
     */
    public function isGeoIpEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_GEOIP_TYPE);
    }
}
