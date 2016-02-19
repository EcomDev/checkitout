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

// Authorize Net Direct Post Support
if (window.directPost) {
    Payment = Class.create(Payment, {
        init: function ($super) {
            $super();
            if (window.directPostModel && this.currentMethod == directPostModel.code) {
                directPostModel.moveForm();
            }
        }
    });
    directPost = Class.create(directPost, {
        moveFormToPayment: false,
        /**
         * Returns CheckItOut Instance
         *
         * @return EcomDev.CheckItOut
         */
        getCheckItOut: function () {
            return EcomDev.CheckItOut.instance;
        },

        validate: function ($super) {
            if (this.getCheckItOut()) {
                return $super() && this.getCheckItOut().isValid();
            }

            return $super();
        },
        removeForm: function () {
            if (!this.moveFormToPayment) {
                return;
            }

            if (window.currentDirectPostForm) {
                if (window.currentDirectPostForm.placeholder.parentNode) {
                    window.currentDirectPostForm.placeholder.remove();
                }


                window.currentDirectPostForm.remove();

                if ($(this.iframeId) && !$(this.iframeId).up().down('form')) {
                    $(this.iframeId).up().insert({
                        bottom: window.currentDirectPostForm
                    });
                }

                window.currentDirectPostForm = null;
            }
        },
        preparePayment: function ($super) {
            $super();
            this.moveForm();
        },
        moveForm: function () {
            if (!this.moveFormToPayment) {
                return;
            }
            this.removeForm();
            if ($(this.iframeId) && this.getCheckItOut()) {
                var checkout = this.getCheckItOut();
                var container = checkout.getStep('review').container;
                var form = $(this.iframeId).up().down('form');
                var payment = checkout.getStep('payment');
                var currentPaymentForm = payment.getCurrentForm();
                if (currentPaymentForm) {
                    currentPaymentForm.relatedElement = form;

                    var dimensions = form.down().getDimensions();

                    form.remove();
                    $$('body').first().insert({
                        bottom: form
                    });
                    form.absolutize();
                    var placeholder = new Element('li');

                    currentPaymentForm.insert({
                        bottom: placeholder
                    });

                    placeholder.setStyle({
                        height: dimensions.height + 'px',
                        display: 'block'
                    });

                    var offset = placeholder.cumulativeOffset();

                    form.setStyle({
                        width: placeholder.getWidth() + 'px',
                        height: dimensions.height + 'px',
                        top: offset.top + 'px',
                        left: offset.left + 'px'
                    });

                    form.placeholder = placeholder;
                    window.currentDirectPostForm = form;
                }
            }
        }
    });
}
