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
 * Rendering helper
 *
 * allows rendering of particular steps by using only layout model
 */
class EcomDev_CheckItOut_Helper_Render extends Mage_Core_Helper_Abstract
{
    protected $_handles = array();

    /**
     * Adds handle to render process
     *
     * @param string $handle
     * @return EcomDev_CheckItOut_Helper_Render
     */
    public function addHandle($handle)
    {
        $this->_handles[] = $handle;
        return $this;
    }

    /**
     * Renders a handle by using of layout model
     *
     * Can be used even within existing page,
     * since doesn't affect layout singleton
     *
     * @param string|string[] $handle
     *
     * @return string
     */
    public function renderHandle($handle)
    {
        if (is_string($handle)) {
            if (Mage::helper('ecomdev_checkitout')->getCompatibilityMode('template') !== false) {
                $handle = array(
                    $handle,
                    $handle . '_' . Mage::helper('ecomdev_checkitout')->getCompatibilityMode('template')
                );
            } else {
                $handle = array(
                    $handle
                );
            }

            $handle = array_merge(
                $handle,
                $this->_handles
            );
        }

        $layout = Mage::getModel('core/layout');
        $layout->setArea(Mage::app()->getLayout()->getArea());
        // Removing layout singleton, since it brakes the functionality
        $previousValue = Mage::registry('_singleton/core/layout');
        Mage::unregister('_singleton/core/layout');
        Mage::register('_singleton/core/layout', $layout);
        $update = $layout->getUpdate();
        $update->load($handle);
        $layout->generateXml();
        $layout->generateBlocks();
        // Restoring singleton back
        Mage::unregister('_singleton/core/layout');
        Mage::register('_singleton/core/layout', $previousValue);
        return $layout->getOutput();
    }
}
