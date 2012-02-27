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
 * Step container block
 *
 */
class EcomDev_CheckItOut_Block_Layout_Step_Container extends EcomDev_CheckItOut_Block_Layout_Step_Abstract
{
    /**
     * Css prefixes for step
     *
     * @var string
     */
    protected $_initCssPrefix = 'container';

    protected $_addClassNameForCount = array();


    /**
     * Adds class name if visible children count is equal to passed parameter
     *
     * @param string $className
     * @param int $stepCount
     * @return EcomDev_CheckItOut_Block_Layout_Step_Container
     */
    public function addClassNameForStepCount($className, $stepCount)
    {
        $this->_addClassNameForCount[(int)$stepCount][$className] = $className;
        return $this;
    }


    /**
     * Removes class name from list for visible children add logic
     *
     * @param string $className
     * @param int $stepCount
     * @return EcomDev_CheckItOut_Block_Layout_Step_Container
     */
    public function removeClassNameForStepCount($className, $stepCount)
    {
        if (isset($this->_addClassNameForCount[(int)$stepCount][$className])) {
            unset($this->_addClassNameForCount[(int)$stepCount][$className]);
        }

        return $this;
    }

    /**
     * Adds logic for container count on before to HTML
     *
     * @return EcomDev_CheckItOut_Block_Layout_Step_Container
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        $this->_initClassNamesForStepCount();
        return $this;
    }

    /**
     * Adds class names for current block step count
     *
     * @return EcomDev_CheckItOut_Block_Layout_Step_Container
     */
    protected function _initClassNamesForStepCount()
    {
        if (isset($this->_addClassNameForCount[$this->getStepCount()])) {
            foreach ($this->_addClassNameForCount[$this->getStepCount()] as $className) {
                $this->addClassName($className);
            }
        }

        return $this;
    }

    /**
     * Iterates over all child blocks and
     * if they are visible adds increment
     *
     * @return int
     */
    public function getStepCount()
    {
        $result = 0;
        foreach ($this->getSortedChildBlocks() as $childBlock) {
            if ($childBlock instanceof EcomDev_CheckItOut_Block_Layout_Step_Interface && $childBlock->isVisible()) {
                $result++;
            }
        }
        return $result;
    }

    /**
     * Returns content of child blocks
     *
     * @return string
     */
    public function getStepContent()
    {
        $content = '';
        $index = 0;
        foreach ($this->getSortedChildBlocks() as $childBlock) {
            if ($childBlock instanceof EcomDev_CheckItOut_Block_Layout_Step_Interface && $childBlock->isVisible()) {
                if ($this->getUsePositionCode()) {
                    $index++;
                    $childBlock->setPositionCode($index%2 == 0? 'second' : 'first');
                }
                $content .= $childBlock->toHtml();
            }
        }
        return $content;
    }

    /**
     * Check if this block is visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->getStepCount() > 0;
    }

}
