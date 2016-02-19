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
 * Interface for a checkout step hasher, that is based on quote data
 *
 */
interface EcomDev_CheckItOut_Model_Hash_Quote_Interface
{
    /**
     * Set quote that will be used for retrieving hashed data
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return EcomDev_CheckItOut_Model_Hash_Quote_Interface
     */
    public function setQuote($quote);

    /**
     * Returns quote instance that will be used for retrieving of hashed data
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote();

    /**
     * Calculates hash from data that was defined in getDataForHash method
     *
     * @return string
     */
    public function getHash();
}
