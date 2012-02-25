<?php
/**
 * ${PROJECT}
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   ${CATEGORY}
 * @package    ${PACKAGE}
 * @copyright  Copyright (c) 2012 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

class EcomDev_CheckItOut_Block_Layout_Step_Login extends EcomDev_CheckItOut_Block_Layout_Step_Abstract
{
    /**
     * Check if this block is visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return parent::isVisible() && !$this->isCustomerLoggedIn();
    }
}
