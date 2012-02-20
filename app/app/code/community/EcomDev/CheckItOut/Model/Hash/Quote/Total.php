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
 * Model for hashing of quote total
 *
 */
class EcomDev_CheckItOut_Model_Hash_Quote_Total extends EcomDev_CheckItOut_Model_Hash_Quote_Abstract
{
    /**
     * Returns array of quote totals info: code;title;value
     * (non-PHPdoc)
     * @see EcomDev_CheckItOut_Model_Hash_Quote_Abstract::getDataForHash()
     */
    public function getDataForHash()
    {
        $data = array();

        foreach ($this->getQuote()->getTotals() as $total) {
            $data[] = sprintf('%s;%s;%.2f', $total->getCode(), $total->getTitle(), $total->getValue());
        }

        return $data;
    }
}
