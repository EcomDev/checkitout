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
 * @copyright  Copyright (c) 2015 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Design Options Dropdown
 *
 */
abstract class EcomDev_CheckItOut_Model_Source_Design_Abstract
{
    const DEFAULT_HELPER = 'ecomdev_checkitout';

    /**
     * Configuration path for retrieving of the design options
     *
     * @var string
     */
    protected $_nodePath = null;

    /**
     * List of options retrieved from config
     *
     * @var array
     */
    protected $_options = null;

    /**
     * Return list of options from configuration
     *
     * @return array
     */
    public function getOptions()
    {
        $this->_initOptions();
        return $this->_options;
    }

    /**
     * Initializes options from configuration
     *
     * @return EcomDev_CheckItOut_Model_Source_Design_Abstract
     */
    protected function _initOptions()
    {
        if ($this->_options === null && $this->_nodePath !== null) {
            $this->_options = array();

            $configNode = Mage::getConfig()->getNode($this->_nodePath);
            if ($configNode) {
                /* @var $node Varien_Simplexml_Element */
                foreach ($configNode->children() as $node) {
                    $option = $node->asArray();

                    $module = self::DEFAULT_HELPER;
                    if (isset($option['@']['module'])) {
                        $module = $option['@']['module'];
                    }

                    unset($option['@']);

                    $option['label'] = Mage::helper($module)->__($option['label']);
                    $this->_options[$node->getName()] = new Varien_Object($option);
                }
            }
        }
        return $this;
    }

    /**
     * Return list of options for configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = array();
        foreach ($this->getOptions() as $optionCode => $optionObject) {
            $result[] = array('value' => $optionCode, 'label' => $optionObject->getLabel());
        }
        return $result;
    }

    /**
     * Returns option by its code
     *
     * @param string $optionCode
     * @return bool
     */
    public function getOptionByCode($optionCode)
    {
        $this->_initOptions();

        if (isset($this->_options[$optionCode])) {
            return $this->_options[$optionCode];
        }

        return false;
    }
}
