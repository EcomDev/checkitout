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
 * Shipping method selection checkout step
 *
 */
var ShippingMethod = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     *
     * @type String
     */
    code: 'shipping_method',
    // No interval between selection of shipping method
    changeInterval: 0,
    /**
     * CSS selector for content block that will be reloaded each time for a step
     *
     * @type String
     */
    contentCssSelector: '.step-content #checkout-shipping-method-load',
    /**
     * Checkout step constructor
     *
     * @param Function $super parent constructor method
     * @return void
     */
    initialize: function ($super, form, saveUrl) {
        if (EcomDev.CheckItOut.instance && EcomDev.CheckItOut.instance.getStep(this.code)) {
            EcomDev.Replacer.replace(this, window, 'shippingMethod', EcomDev.CheckItOut.instance.getStep(this.code));
            return;
        }
        var container = this.findContainer(form);
        this.onAdditionalLoad = this.handleAdditionalLoad.bind(this);
        this.onChangeAdditional = this.handleChangeAdditional.bind(this);
        $super(container, saveUrl);
        this.errorEl = new Element('input', {type: 'hidden', value: 1, name: 'cio_shipping_method_error', id: 'shipping_method_error', 'class' : 'required-entry ajax-error'});
        this.addRelation('shipping');
        this.addRelation('billing');
        new ShippingAdditional(this);
    },
    /**
     * Performs checking of fullfillment of shipping method selection
     *
     * @param Function $super parent method
     * @return Boolean
     */
    isValid: function ($super) {
        this.errorEl.__advicevalidateAjax = 1;
        Validation.reset(this.errorEl);
        var methods = document.getElementsByName('shipping_method');
        if (methods.length==0) {
            Validation.ajaxError(this.errorEl, Translator.translate('Your order cannot be completed at this time as there is no shipping methods available for it. Please make neccessary changes in your shipping address.'));
            return false;
        }

        if(!$super()) {
            return false;
        }

        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return true;
            }
        }
        Validation.ajaxError(this.errorEl, Translator.translate('Please specify shipping method.'));
        return false;
    },
    /**
     * Handles init checkout hook to show radio button
     * if only one shipping method is available
     *
     * @parent Function $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        if (!this.checkout.preloadedHtml.shipping_method) {
            this.checkOneMethod();
        }
        $super();
        this.content.insert({after: this.errorEl});
        if (this.checkout.preloadedHtml.shipping_method) {
            this.update(this.checkout.preloadedHtml.shipping_method);
        }

        if (this.checkout.preloadedHtml.shipping_additional) {
            this.updateAdditional(this.checkout.preloadedHtml.shipping_additional);
        } else {
            this.additionalLoad();
        }
    },
    /**
     * Loads additional shipping method data
     *
     * @return void
     */
    additionalLoad: function () {
        $(this.checkout.config.additionalContainer).hide();
        new Ajax.Updater(this.checkout.config.additionalContainer, this.checkout.config.additionalUrl, {evalScripts:true, onComplete: this.onAdditionalLoad});
    },

    /**
     * Updates additional block html content
     *
     * @param html
     */
    updateAdditional: function (html) {
        $(this.checkout.config.additionalContainer).update(html);
        this.handleAdditionalLoad();
    },

    /**
     * Handles additional load action completed (i.e. adding field observers)
     *
     * @return void
     */
    handleAdditionalLoad: function () {
        if (arguments.length) {
            this.onAdditionalLoad.defer();
            return this;
        }

        $(this.checkout.config.additionalContainer).show();

        var items = $(this.checkout.config.additionalContainer).select('input', 'select', 'textarea');
        for (var i=0,l=items.length; i < l; i++) {
            items[i].observe('change', this.onChangeAdditional);
            if (items[i].tagName.toLowerCase() == 'input' && (items[i].type == 'checkbox' || items[i].type == 'radio')) {
                items[i].observe('click', this.onChangeAdditional);
            }
        }
    },
    /**
     * Handles change on additional elements and performs sending data to webserver
     *
     * @return void
     */
    handleChangeAdditional: function (evt) {
        this.onChange.defer(evt);
        var element = Event.element(evt)
        if (element.tagName.toLowerCase() == 'select' ||  (element.tagName.toLowerCase() == 'input' && element.type == 'checkbox')) {
            this.checkout.getStep('review').loadedHash = false;
        }
    },
    /**
     * Retrieve form elements
     *
     * @return Array
     */
    getElements: function () {
        return this.container.select('input', 'select', 'textarea');
    },
    /**
     * Send shipping method to backend, Sets internal data
     * for restoring of selected shipping method
     *
     * @param Function $super
     * @return void
     */
    submit: function ($super) {
        $super();
        this.lastSubmitted = this.getValues();
    },

    /**
     * Updates content of checkout step and show radio button
     * if only one shipping method is available
     *
     * @param Function $super parent method
     * @param String content
     * @return void
     */
    update: function ($super, content) {
        $super(content);
        this.checkOneMethod();
    },
    /**
     * Show radio button if only one shipping method is available
     *
     * @return void
     */
    checkOneMethod: function () {
        this.container.select('input[type=radio]').each(function (item) {
            item.setAttribute('autocomplete', 'off');
        });
        var oneMethod = this.container.down('.no-display input[type=radio]');
        if (oneMethod) {
            this.onChange.delay(0.5, {});
        }
    },
    /**
     * Handles submission result of saving shipping method,
     * display errors if any
     *
     * @param Function $super parent method
     * @param Ajax.Response response
     * @return void
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
            this.isLoading(false);
            alert(result.message);
            return;
        }
        return $super(response);
    }
});

/**
 * Shipping method selection checkout step
 *
 */
var ShippingAdditional = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     *
     * @type String
     */
    code: 'shipping_additional',

    /**
     * Check auto submit feature
     */
    autoSubmit: false,

    /**
     * Sets shipping method instance
     *
     * @param shippingMethod
     */
    initialize: function ($super, shippingMethod) {
        this.shippingMethod = shippingMethod;
        $super(this.shippingMethod.container, false);
    },
    /**
     * Updates additional block content
     *
     * @param content
     */
    update: function (content) {
        this.shippingMethod.updateAdditional(content);
    }
});
