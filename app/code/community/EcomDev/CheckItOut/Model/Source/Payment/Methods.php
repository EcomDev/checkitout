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