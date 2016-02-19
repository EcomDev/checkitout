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
 * Model for hashing of quote total
 *
 */
class EcomDev_CheckItOut_Model_Hash_Quote_Total extends EcomDev_CheckItOut_Model_Hash_Quote_Abstract
{

    /**
     * Original values for cart totals
     *
     * @var array[]
     */
    protected $_originalTotalValues = array();

    /**
     * Reflection of the address class
     *
     * @var ReflectionClass
     */
    protected $_addressReflection;

    /**
     * Retrieves reflection of address class
     *
     * @return ReflectionClass
     */
    protected function _getAddressReflection()
    {
        if ($this->_addressReflection === null) {
            $this->_addressReflection = new ReflectionClass(
                get_class(
                    $this->getQuote()->getAddressesCollection()->getFirstItem()
                )
            );
        }

        return $this->_addressReflection;
    }

    /**
     * Returns reflection of the total property of address class
     *
     * @return ReflectionProperty
     */
    protected function _getTotalPropertyReflection()
    {
        return $this->_getAddressReflection()->getProperty('_totals');
    }

    /**
     * Walks over all quote addresses and saves
     * value of _totals property in address to restore it back later
     *
     * @return $this
     */
    protected function _saveTotalValues()
    {
        // Save original _totals property of quote addresses 
        $addresses = $this->getQuote()->getAddressesCollection();
        $this->_originalTotalValues = array();

        $property = $this->_getTotalPropertyReflection();

        $property->setAccessible(true);

        foreach ($addresses as $address) {
            $addressId = spl_object_hash($address);
            $this->_originalTotalValues[$addressId] = $property
                ->getValue($address);
        }

        $property->setAccessible(false);

        return $this;
    }

    /**
     * Restores total values saved by _saveTotalValues method
     *
     * @return $this
     */
    protected function _restoreTotalValues()
    {
        // Save original _totals property of quote addresses 
        $addresses = $this->getQuote()->getAddressesCollection();
        $property = $this->_getTotalPropertyReflection();

        $property->setAccessible(true);

        foreach ($addresses as $address) {
            $addressId = spl_object_hash($address);

            if (!isset($this->_originalTotalValues[$addressId])) {
                continue;
            }

            $property->setValue(
                $address,
                $this->_originalTotalValues[$addressId]
            );
        }

        $property->setAccessible(false);
        $this->_originalTotalValues = array();

        return $this;
    }

    /**
     * Returns array of quote totals info: code;title;value
     * (non-PHPdoc)
     * @see EcomDev_CheckItOut_Model_Hash_Quote_Abstract::getDataForHash()
     */
    public function getDataForHash()
    {
        $data = array();

        $this->_saveTotalValues();

        foreach ($this->getQuote()->getTotals() as $total) {
            $data[] = sprintf('%s;%s;%.2f', $total->getCode(), $total->getTitle(), $total->getValue());
        }

        $this->_restoreTotalValues();

        return $data;
    }
}
