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

        return $result;
    }
}
