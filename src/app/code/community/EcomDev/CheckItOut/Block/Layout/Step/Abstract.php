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
 * Checkout Layout Step Block Abstract Implementation
 *
 * @method setStepCode(string $code) sets code for getting checkout step as a base
 * @method string getStepCode() returns step code
 * @method setStepName(string $name) sets name of the checkout block
 * @method unsStepName() removes step name from the block data
 * @method setStepNumber(int $number) sets number of the step to make possible counting them
 * @method setIsVisibleForVirtual(boolean $flag)
 * $method boolean getIsVisibleForVirtual() returns flag for block is visible depending on type of shopping cart (is virtual or not)
 *
 */
abstract class EcomDev_CheckItOut_Block_Layout_Step_Abstract
    extends Mage_Checkout_Block_Onepage_Abstract
    implements EcomDev_CheckItOut_Block_Layout_Step_Interface
{
    const BLOCK_NAME_CHECKOUT = 'checkout.onepage';

    /**
     * Checkout step number incrementor
     *
     * @var int
     */
    protected static $_stepNumber = 0;

    /**
     * Checkout block instance
     *
     * @var Mage_Checkout_Block_Onepage
     */
    protected $_checkoutBlock = null;

    /**
     * Class names array for the checkout step block
     *
     * @var array
     */
    protected $_classNames = null;

    /**
     * Css prefixes for step
     *
     * @var string
     */
    protected $_initCssPrefix = 'checkout-step';

    /**
     * Indicator that it has loading overlay
     *
     * @var bool
     */
    protected $_hasLoadingOverlay = true;

    /**
     * Returns value of the loading overlay
     *
     * @return bool
     */
    public function hasLoadingOverlay()
    {
        return (bool)$this->_hasLoadingOverlay;
    }

    /**
     * Returns block of the standard Magento checkout
     *
     * @return Mage_Checkout_Block_Onepage
     */
    public function getCheckoutBlock()
    {
        if ($this->_checkoutBlock === null) {
            $this->_checkoutBlock = $this->getLayout()->getBlock(self::BLOCK_NAME_CHECKOUT);
        }

        return $this->_checkoutBlock;
    }



    /**
     * Returns step block instance from the original checkout
     *
     * @return Mage_Checkout_Block_Onepage_Abstract
     */
    public function getStepBlock()
    {
        if (!$this->getStepCode()) {
            Mage::throwException('Step code is not specified.');
        }

        return $this->getCheckoutBlock()->getChild($this->getStepCode());
    }

    /**
     * Returns block step number
     *
     * @return int
     */
    public function getStepNumber()
    {
        if (!$this->hasData('step_number')) {
            $this->setData('step_number', ++self::$_stepNumber);
        }

        return $this->_getData('step_number');
    }

    /**
     * Should return step name from the checkout object
     *
     * @return string
     */
    public function getStepName()
    {
        if (!$this->hasData('step_name') && $this->getStepCode()) {
            $this->setStepName($this->getCheckout()->getStepData($this->getStepCode(), 'label'));
        }

        return $this->_getData('step_name');
    }

    /**
     * Returns rendered content of the step block
     *
     * @return string
     */
    public function getStepContent()
    {
        if (!$this->hasData('step_content')) {
            $stepContent = $this->getStepBlock()->toHtml();
            $this->setData('step_content', $stepContent);
        }

        return $this->_getData('step_content');
    }

    /**
     * Initializes step class names
     *
     * @return EcomDev_CheckItOut_Block_Layout_Step_Abstract
     */
    protected function _initClassNames()
    {
        if ($this->_classNames === null) {
            $this->_classNames = array();
            if ($this->_initCssPrefix) {
                $this->addClassName($this->_initCssPrefix);
                if ($this->getStepCode()) {
                    $this->addClassName(
                        sprintf('%s-%s', $this->_initCssPrefix, $this->getStepCode())
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Adds class name to the list of css
     *
     * @param string $className
     * @return EcomDev_CheckItOut_Block_Layout_Step_Abstract
     */
    public function addClassName($className)
    {
        $this->_initClassNames();
        $this->_classNames[] = $className;
    }

    /**
     * Remove class name from the list of css
     *
     * @param string $className
     *
     * @return EcomDev_CheckItOut_Block_Layout_Step_Abstract
     */
    public function removeClassName($className)
    {
        $this->_initClassNames();

        if (($index = array_search($className, $this->_classNames)) !== false) {
            array_splice($this->_classNames, $index, 1);
        }

        return $this;
    }

    /**
     * Returns class names of the block
     *
     * @return string
     */
    public function getClassName()
    {
        $this->_initClassNames();
        return implode(' ', $this->_classNames);
    }

    /**
     * Check if this block is visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        if ($this->getIsVisibleForVirtual() === 0 && $this->getCheckout()->getQuote()->isVirtual()) {
            return false;
        }

        if ($this->getStepCode() && $this->getStepBlock() && !$this->getStepBlock()->isShow()) {
            return false;
        }

        return true;
    }
}
