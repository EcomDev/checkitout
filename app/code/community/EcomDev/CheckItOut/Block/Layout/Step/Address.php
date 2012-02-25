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
 * Address form step wrapper
 *
 */
class EcomDev_CheckItOut_Block_Layout_Step_Address extends EcomDev_CheckItOut_Block_Layout_Step_Abstract
{
    /**
     * Initializes address values
     *
     * @return EcomDev_CheckItOut_Block_Layout_Step_Address
     */
    public function initAddress()
    {
        if (Mage::helper('ecomdev_checkitout')->isApplyStoredAddresses()) {
            if ($this->getStepBlock()) {
                $expectedAddress = $this->getCheckout()->getQuote()->{'get' . ucfirst($this->getStepCode()) . 'Address'}();
                if ($this->getStepBlock()->getAddress() !== $expectedAddress) {
                    $this->getStepBlock()->getAddress()->setData($expectedAddress->getData());
                }
            }
        }

        return $this;
    }
}