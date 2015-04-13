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
 * Hashing model for checkout steps from quote object
 *
 */
class EcomDev_CheckItOut_Model_Hash
{
    const XML_PATH_HASHABLE_STEPS = 'ecomdev/checkitout/steps';

    /**
     * Returs hash for checkout steps from quote object
     *
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return string
     */
    public function getHash($quote)
    {
        $result = array();

        $steps = Mage::getConfig()->getNode(self::XML_PATH_HASHABLE_STEPS)->children();
        foreach ($steps as $step) {
            if (!isset($step->hash)) {
                continue;
            }

            $result[$step->getName()] = '';
            foreach ($step->hash->children() as $hashModel) {
                $result[$step->getName()] .= Mage::getSingleton((string)$hashModel)
                    ->setQuote($quote)
                    ->getHash();
            }
        }

        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        // Remove cached properties for 1.8x (since data is cached by Magento internally)
        $address->unsetData('cached_items_all');
        $address->unsetData('cached_items_nominal');
        $address->unsetData('cached_items_nonnominal');

        return $result;
    }
}
