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
 * Model for hashing of quote items
 *
 */
class EcomDev_CheckItOut_Model_Hash_Quote_Item extends EcomDev_CheckItOut_Model_Hash_Quote_Abstract
{
    /**
     * Returns array of items info: sku;qty;base_price;base_row_total
     * (non-PHPdoc)
     * @see EcomDev_CheckItOut_Model_Hash_Quote_Abstract::getDataForHash()
     */
    public function getDataForHash()
    {
        $data = array();

        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            $data[] = sprintf('%s;%s;%.2f;%.2f', $item->getSku(), $item->getQty(), $item->getBasePrice(), $item->getBaseRowTotal());
        }

        return $data;
    }
}
