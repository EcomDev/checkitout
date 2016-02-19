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
 * Rendering helper
 *
 * allows rendering of particular steps by using only layout model
 */
class EcomDev_CheckItOut_Helper_Render extends Mage_Core_Helper_Abstract
{
    /**
     * Paths to the steps configuration
     * for which handles should retrieved
     *
     * @var string
     */
    const XML_PATH_STEPS = 'ecomdev/checkitout/steps';

    /**
     * List of pre appended handles
     *
     * @var array
     */
    protected $_handles = array();

    /**
     * Mapping of handles per step
     *
     * @var array
     */
    protected $_stepHandles = array();

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
                    $handle . '_checkitout',
                    $handle . '_' . Mage::helper('ecomdev_checkitout')->getCompatibilityMode('template')
                );
            } else {
                $handle = array(
                    $handle,
                    $handle . '_checkitout'
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


    /**
     * Initializes step handles based on configuration
     *
     * @return EcomDev_CheckItOut_Helper_Render
     */
    protected function _initStepHandles()
    {
        if (!$this->_stepHandles) {
            $steps = Mage::getConfig()->getNode(self::XML_PATH_STEPS)->children();
            foreach ($steps as $step) {
                if (isset($step->handle)) {
                    $this->_stepHandles[$step->getName()] = (string)$step->handle;
                }
            }
        }
        return $this;
    }

    /**
     * Returns array of step to handle association
     *
     * @return string[]
     */
    public function getStepHandles()
    {
        $this->_initStepHandles();
        return $this->_stepHandles;
    }

    /**
     * Returns handle that should be used for rendering of the step
     *
     * @param string $stepCode
     * @return string|bool
     */
    public function getStepHandle($stepCode)
    {
        if (!$this->hasStepHandle($stepCode)) {
            return false;
        }

        return $this->_stepHandles[$stepCode];
    }


    /**
     * Check if step has predefined in configuration handle
     *
     * @param string $stepCode
     * @return bool
     */
    public function hasStepHandle($stepCode)
    {
        $this->_initStepHandles();
        return isset($this->_stepHandles[$stepCode]);
    }

    /**
     * Renders step handle, based on mapping in XML
     *
     * @param string $stepCode
     * @return string|bool
     */
    public function renderStep($stepCode)
    {
        if (!$this->hasStepHandle($stepCode)) {
            return false;
        }

        return $this->renderHandle(
            $this->getStepHandle($stepCode)
        );
    }
}
