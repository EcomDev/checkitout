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
 * Model for hashing of shipping rates
 *
 */
class EcomDev_CheckItOut_Model_Hash_Quote_Address_Rate extends EcomDev_CheckItOut_Model_Hash_Quote_Abstract
{
    /**
     * Returns array of shipping rates info: code;carrier;method;price
     * (non-PHPdoc)
     * @see EcomDev_CheckItOut_Model_Hash_Quote_Abstract::getDataForHash()
     */
    public function getDataForHash()
    {
        $data = array();

        if ($this->getQuote()->isVirtual()) {
            return $data;
        }

        foreach ($this->getQuote()->getShippingAddress()
                    ->getShippingRatesCollection() as $shippingRate) {
            $data[] = sprintf(
                '%s;%s;%s;%.2f', $shippingRate->getCode(), $shippingRate->getCarrierTitle(),
                $shippingRate->getMethodTitle(), $shippingRate->getPrice()
            );
        }
        
        $shippingDescription = $this->getQuote()->getShippingAddress()->getShippingDescription();
        
        $data[] = $shippingDescription ? $shippingDescription : 'Empty';
        return $data;
    }
}
