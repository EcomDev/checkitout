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
 * Payment method source model for default shipping method select
 *
 */
class EcomDev_CheckItOut_Model_Source_Payment_Methods
{
    public function toOptionArray()
    {
        $methods = Mage::getSingleton('payment/config')->getActiveMethods();

        $result = array(array('value' => '', 'label' => ''));

        foreach ($methods as $code => $method) {
            if (!$method || !$method->canUseCheckout()) {
                continue;
            }
            $result[] = array('value' => $code, 'label' => $method->getTitle());
        }

        return $result;
    }
}
