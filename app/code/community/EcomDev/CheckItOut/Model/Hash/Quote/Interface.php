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
