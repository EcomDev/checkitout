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
