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
 * Quote item info model for retrieving of additional information
 *
 */
class EcomDev_CheckItOut_Model_Quote_Item_Info
{
    /**
     * Retrieves information from quote item
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    public function getInfo($item)
    {
        $info = array();
        $info['item_id'] = $item->getId();
        $info['allow_remove'] = !$item->isNominal();
        $info['allow_change_qty'] = !$item->isNominal();
        $info['qty'] = $item->getQty();
        return $info;
    }
}
