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
 * Js Loader Block
 *
 * Realizes conditional logic of JS files inclusion, based on helper callbacks
 *
 */
class EcomDev_CheckItOut_Block_Js_Loader extends Mage_Core_Block_Abstract
{
    /**
     * Adds js file only if
     *
     * @param string $file
     * @param bool $condition condition for inclusion of js file
     * @param bool $negative flag for treating condition as negative one
     * @return EcomDev_CheckItOut_Block_Js_Loader
     */
    public function add($file, $condition = true, $negative = false)
    {
        if ($negative) {
            $condition = !$condition;
        }

        if ($condition && $this->getLayout()->getBlock('head')) {
            $this->getLayout()->getBlock('head')->addItem('skin_js', $file);
        }

        return $this;
    }
}