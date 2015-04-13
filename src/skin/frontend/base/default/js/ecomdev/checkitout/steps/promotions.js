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
 * Promotion code abstract step class
 *
 * @type EcomDev.CheckItOut.Step
 */
EcomDev.CheckItOut.Step.PromotionCode = Class.create(
    EcomDev.CheckItOut.Step, {
        /**
         * Ignores validation results on coupon code fields
         *
         * @var Boolean
         */
        ignoreValidationResult: true,
        /**
         * Checkout step constructor
         *
         * @param Function $super parent constructor method
         * @param String form container element id
         * @param String saveUrl url for submitting of the coupon codes
         * @return void
         */
        initialize: function ($super, container, config) {
            this.autoSubmit = false;
            this.confirmText = config.confirmText;
            $super($(container), config.saveUrl);
        },
        /**
         * Initializes promotion code forms on checkout
         *
         * @param $super
         */
        initCheckout: function ($super) {
            $super();

            this.applyBtn = this.container.down('button.apply');

            this.applyBtn.observe(
                'click',
                this.submit.bind(this)
            );

            this.container.down('form').observe('submit', function (evt) {
                Event.stop(evt);
            });
        },

        /**
         * Makes after init preparations, like adding dependencies
         *
         * @param Function $super
         * @void
         */
        initCheckoutAfter: function ($super) {
            $super();
            if (this.checkout.getStep('shipping_method')) {
                this.checkout.getStep('shipping_method').addRelation(this.code);
            }

            if (this.checkout.getStep('payment')) {
                this.checkout.getStep('payment').addRelation(this.code);
            }

            if (this.checkout.getStep('review')) {
                this.checkout.getStep('review').addRelation(this.code);
            }
        },

        /**
         * Handles submission complete and displays related errors
         * if there were any
         *
         * @param Function $super parent method
         * @param Ajax.Response response
         * @void
         */
        submitComplete: function ($super, response) {
            if (response && response.responseText){
                try{
                    var result = response.responseText.evalJSON();
                }
                catch (e) {
                    var result = {};
                }
            }
            if (result.error){
                if (result.field && this.container.down('*[name="'+ result.field + '"]')) {
                    Validation.ajaxError(this.container.down('*[name="'+ result.field + '"]'), result.message);
                } else {
                    alert(result.message);
                }
            }

            if (typeof this.submitCompleteCallback !== 'undefined') {
                this.submitCompleteCallback(result, response);
            }

            return $super(response);
        }
    }
);

/**
 * Coupon code step class
 *
 * @type EcomDev.CheckItOut.Step.PromotionCode
 */
window.CouponCode = Class.create(EcomDev.CheckItOut.Step.PromotionCode, {
    code: 'coupon_code',

    initialize: function ($super, container, config) {
        this.coupon = config.coupon;
        this.remove = false;
        $super(container, config);
    },
    /**
     * If current operation is related to remove of coupon code,
     * then it returns hash for such operation
     *
     * @param $super
     * @return Object
     */
    getValues: function ($super) {
        if (this.remove) {
            return {remove: 1};
        }

        return $super();
    },
    /**
     * Handles removal of the object
     *
     * @param Event evt
     * @void
     */
    handleRemove: function (evt) {
        if (!confirm(this.confirmText)) {
            return;
        }
        this.remove = true;
        this.autoValidate = false;
        this.submit();
        this.autoValidate = true;
        this.remove = false;
    },
    /**
     * Shows or hides remove button if necessary
     *
     * @void
     */
    changeRemoveBtnVisibility: function () {
        if (this.coupon) {
            this.removeBtn.show();
        } else {
            this.removeBtn.hide();
        }
    },
    /**
     * Initializes copon code forms on checkout
     *
     * @param $super
     */
    initCheckout: function ($super) {
        $super();

        this.couponField = this.container.down('*[name="coupon"]');
        this.removeBtn = this.container.down('button.remove');
        this.changeRemoveBtnVisibility();

        this.removeBtn.observe(
            'click',
            this.handleRemove.bind(this)
        );

        this.couponField.value = this.coupon || '';
    },
    /**
     * Resets validation of coupon field
     *
     * @param result
     * @param response
     */
    submitCompleteCallback: function(result, response) {
        if (!result.error) {
            Validation.reset(this.couponField);
        }

        if (typeof result.coupon !== 'undefined') {
            this.coupon = result.coupon;
        }

        this.changeRemoveBtnVisibility();
    }
});
