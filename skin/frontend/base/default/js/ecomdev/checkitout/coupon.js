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

window.CouponCode = Class.create(
    EcomDev.CheckItOut.Step, {
        /**
         * Checkout step constructor
         *
         * @param Function $super parent constructor method
         * @param String form container element id
         * @param String saveUrl url for submitting of the coupon codes
         * @return void
         */
        initialize: function ($super, form, saveUrl) {
            var container = this.findContainer(form);
            $super(container, saveUrl);
        }
    }
);