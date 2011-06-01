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
 * Checkout Layout Block
 *
 */
class EcomDev_CheckItOut_Block_Checkout_Layout extends Mage_Core_Block_Template
{
    /**
     * Checkout blocks layouted by internal system
     *
     * @var array
     */
    protected $_checkoutLayout = array();

    /**
     * Checkout steps map
     *
     * @var array
     */
    protected $_steps = array();

    /**
     * Retrieve checkout block
     *
     * @return Mage_Checkout_Block_Onepage
     */
    public function getCheckoutBlock()
    {
        return $this->getLayout()->getBlock('checkout.onepage');
    }

    /**
     * Initialize steps before actions on it
     *
     * @return EcomDev_CheckItOut_Block_Checkout_Layout
     */
    protected function _initializeSteps()
    {
        if (empty($this->_steps)) {
            $this->_steps = $this->getCheckoutBlock()->getSteps();
        }

        return $this;
    }

    /**
     * Retrieve steps lists
     */
    public function getSteps()
    {
        $this->_initializeSteps();
        return $this->_steps;
    }

    /**
     * Retrieve checkout step by its id
     *
     * @param string $stepId
     * @return array|boolean
     */
    public function getStep($stepId)
    {
        $this->_initializeSteps();
        if (isset($this->_steps[$stepId])) {
            return $this->_steps[$stepId];
        }

        return false;
    }

    /**
     * Retrieve step block html
     *
     * @param string $stepId
     * @return string
     */
    public function getStepBlockHtml($stepId)
    {
        $step = $this->getStep($stepId);

        if (!$step) {
            return '';
        }

        return $this->getCheckoutBlock()->getChildHtml($stepId);
    }

    /**
     * Checks whether display step or not
     *
     * @param string $stepId
     * @return boolean
     */
    public function displayStep($stepId)
    {
        $step = $this->getStep($stepId);

        if (!$step) {
            return false;
        }

        if (!$this->getCheckoutBlock()->getChild($stepId) ||
            !$this->getCheckoutBlock()->getChild($stepId)->isShow()) {
            return false;
        }

        return true;
    }

    /**
     * Add checkout block from 'checkout.onepage' block to layout
     *
     * @param string $position
     * @param string $stepId
     * @param boolean|null $isVirtual performs isVirtual check for quote
     * @return EcomDev_CheckItOut_Block_Checkout_Layout
     */
    public function addCheckoutStepToLayout($position, $stepId, $isVirtual = null)
    {
        if ($isVirtual !== null) {
            if (!$isVirtual && $this->getCheckoutBlock()->getQuote()->isVirtual()) {
                return $this;
            } elseif ($isVirtual && !$this->getCheckoutBlock()->getQuote()->isVirtual()) {
                return $this;
            }
        }
        $step = $this->getStep($stepId);
        if (!$step) {
            return $this;
        }
        $this->_checkoutLayout[$position][$stepId] = $step;
        return $this;
    }

    /**
     * Retrieve checkout block for specified positions in layout
     *
     * @param strning $position
     * @return array
     */
    public function getCheckoutBlocksByLayout($position)
    {
        if (!isset($this->_checkoutLayout[$position])) {
            return array();
        }

        return $this->_checkoutLayout[$position];
    }

    /**
     * Checks whether display layout or not
     *
     * @param string $position
     * @return boolean
     */
    public function displayLayout($position)
    {
        if (empty($this->_checkoutLayout[$position])) {
            return false;
        }

        return true;
    }

    /**
     * Check is displayable containter or not
     *
     * @return boolean
     */
    public function displayContainer()
    {
        return !$this->getRequest()->getParam('isAjax');
    }

    /**
     * Returns shipping method if applicable
     *
     * @return string|boolean
     */
    public function getShippingMethod()
    {
        if ($this->getCheckoutBlock()->getQuote()->isVirtual()) {
            return false;
        }

        return $this->getCheckoutBlock()->getQuote()
                   ->getShippingAddress()->getShippingMethod();
    }
}
