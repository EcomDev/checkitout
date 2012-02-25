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
 * Css addition into head
 *
 */
class EcomDev_CheckItOut_Block_Css extends Mage_Core_Block_Abstract
{
    /**
     * Adds CSS styles to head block if it exists
     *
     * @return EcomDev_CheckItOut_Block_Css
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->getLayout()->getBlock('head')) {
            foreach ($this->helper('ecomdev_checkitout')->getCssFiles() as $file) {
                $this->getLayout()->getBlock('head')->addCss($file);
            }
        }

        return $this;
    }

}
