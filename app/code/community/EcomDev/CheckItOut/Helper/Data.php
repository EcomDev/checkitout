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

    const CONFIRM_TYPE_CHECKBOX = EcomDev_CheckItOut_Model_Config_Source_Confirm_Type::TYPE_CHECKBOX;
    const CONFIRM_TYPE_POPUP = EcomDev_CheckItOut_Model_Config_Source_Confirm_Type::TYPE_POPUP;
    const CONFIRM_TYPE_NONE = EcomDev_CheckItOut_Model_Config_Source_Confirm_Type::TYPE_NONE;

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
        return Mage::getStoreConfigFlag(self::XML_PATH_NEWSLETTER_CHECKBOX);
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
}
