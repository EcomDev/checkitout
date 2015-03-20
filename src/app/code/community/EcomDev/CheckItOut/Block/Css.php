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
            $designFiles = $this->helper('ecomdev_checkitout')->getDesignFiles();
            foreach ($designFiles['css'] as $file) {
                $this->getLayout()->getBlock('head')->addCss($file);
            }
            foreach ($designFiles['js'] as $file) {
                $this->getLayout()->getBlock('head')->addSkinJs($file);
            }
        }

        return $this;
    }

}
