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
 * Extension observer model
 *
 *
 */
class EcomDev_CheckItOut_Model_Observer
{
    /**
     * Replaces prototype library with 1.7 one
     *
     */
    public function replacePrototypeLibrary()
    {
        // IE9 compatible prototype library, otherwise checkitout will not work
        $head = Mage::app()->getLayout()->getBlock('head');
        $headItems = $head->getData('items');

        if (isset($headItems['js/prototype/prototype.js'])) {
            $headItems['js/prototype/prototype.js']['name'] = 'ecomdev/prototype.js';
        }

        $head->setData('items', $headItems);
    }
}
