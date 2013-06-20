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
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Payment method selection checkout step
 *
 */
var Payment = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     *
     * @type String
     */
    code: 'payment',
    /**
     * Before payment init handlers
     *
     * @type Hash
     */
    beforeInitFunc:$H({}),
    /**
     * After payment init handlers
     *
     * @type Hash
     */
    afterInitFunc:$H({}),
    /**
     * Before payment validate handlers
     *
     * @type Hash
     */
    beforeValidateFunc:$H({}),
    /**
     * After payment validate handlers
     *
     * @type Hash
     */
    afterValidateFunc:$H({}),
    /**
     * CSS selector for content block that will be reloaded each time for a step
     *
     * @type String
     */
    contentCssSelector: '.step-content #co-payment-form #checkout-payment-method-load',
    /**
     * Regexp for normalizing received payment methods contents
     *
     * @type RegExp
     */
    contentElementRegExp: new RegExp('^<[\\s\\S]*?class="sp-methods"[\\s\\S]*?id="checkout-payment-method-load"[\\s\\S]*?>([\\s\\S]*)<[\\s\\S]*?>$', 'm'),
    /**
     * Payment checkout step constructor
     *
     * @param Function $super parent constructor
     * @param String from
     * @param String saveUrl
     * @return void
     */
    initialize: function($super, form, saveUrl){
        if (EcomDev.CheckItOut.instance && EcomDev.CheckItOut.instance.getStep(this.code)) {
            EcomDev.Replacer.replace(this, window, 'payment', EcomDev.CheckItOut.instance.getStep(this.code));
            return;
        }

        this.noReviewLoad = false;
        this.form = form;
        this.saveUrl = saveUrl;
        this.parentConstructor = $super;
        this.onOutsideCheckboxClick = this.handleOutsideCheckboxClick.bind(this);
        this.errorEl = new Element('input', {type: 'hidden', value: 1, name: 'cio_payment_method_error', id: 'payment_method_error', 'class' : 'required-entry ajax-error'});
        this.__deferedConstructor.bind(this).delay(0.01); // Call it anyway if init was not invoked before.
    },
    /**
     * Returns elements for submitting data into savePayment controller action
     * Uses container to send all items
     *
     * @return Array
     */
    getElements: function () {
        return this.container.select('select', 'input', 'textarea');
    },
    /**
     * Updates payment step content,
     * carries about saving of already entered data
     *
     * @param Function $super parent method
     * @param String content
     * @return void
     */
    update: function ($super, content) {
        var values = this.getValues();

        this.updateFieldValues = values;
        $super(content);
        this.updateFieldValues = false;
        this.checkOneMethod();
    },
    /**
     * Updates html content
     *
     * @return void
     */
    __updateContent: function ($super, htmlContent) {
        this.initFieldsCalled = false;
        var scripts = htmlContent.extractScripts();
        var content = htmlContent.stripScripts();

        if (this.contentElementRegExp.test(content)) {
            content = content.replace(this.contentElementRegExp, '$1');
        }

        $super(content);

        if (this.updateFieldValues) {
            var fieldNames = Object.keys(this.updateFieldValues);
            for (var i=0, l=fieldNames.length; i < l; i++) {
                var field = this.content.down('*[name="' + fieldNames[i] + '"]');
                if (field && !['checkbox', 'radio'].include(field.type)) {
                    field.value = this.updateFieldValues[fieldNames[i]];
                } else if (field) {
                    var elements = this.content.select('*[name="' + fieldNames[i] + '"]');
                    for (var elementIndex = 0, elementLength = elements.length;
                         elementIndex < elementLength;
                         elementIndex ++) {
                        if (elements[elementIndex].value == this.updateFieldValues[fieldNames[i]]) {
                            elements[elementIndex].checked = true;
                        }
                    }
                }
            }
        }

        // Bringing all scripts together to eval in the same scope
        var scriptText = '';
        for (var i=0, l = scripts.length; i < l; i ++) {
            scriptText += scripts[i] + "\n";
        }
        if (scriptText.length > 0) {
            try {
                ('<script type="text/javascript">' + scriptText + "</script>").evalScripts();
            } catch (e) {
                if (window.console && console.log) {
                    console.log(e);
                }
            }
        }

        if (!this.initFieldsCalled) {
            this.initFields();
        }
    },
    /**
     * Add handler for before init observing
     *
     * @param String code handler code
     * @param Function func
     * @return void
     */
    addBeforeInitFunction : function(code, func) {
        this.beforeInitFunc.set(code, func);
    },

    /**
     * Calls before init handlers
     *
     * @return void
     */
    beforeInit : function() {
        var values = this.beforeInitFunc.values();
        for (var i = 0, l = values.length; i < l; i ++) {
            values[i]();
        }
    },
    /**
     * Initialization of payment method
     *
     * @return void
     */
    init : function () {
        this.beforeInit();
        this.initFields();
        this.afterInit();
    },
    __deferedConstructor: function () {
        if (this.parentConstructor) {
            this.parentConstructor(this.findContainer(this.form), this.saveUrl);
            this.parentConstructor = false;
        }
    },
    initFields: function () {
        this.initFieldsCalled = true;
        var elements = Form.getElements(this.form);
        var method = null;
        for (var i=0; i<elements.length; i++) {
            if (elements[i].name == 'payment[method]') {
                if (elements[i].checked) {
                    method = elements[i].value;
                }
            } else {
                elements[i].disabled = true;
            }
            elements[i].setAttribute('autocomplete','off');
        }
        if (method && window.currentPaymentMethod !== method) {
            this.switchMethod(method);
        } else if (method) {
            this.changeVisible(method, false);
        }
    },
    /**
     * Inits what is CVV tooltips
     *
     * @param Function $super parent method
     * @return void
     */
    bindFields: function ($super) {
        this.initWhatIsCvvListeners();
        this.container.select('input[name="payment[method]"]').each(
            function (element) {
                element.addClassName('no-autosubmit');
            },
            this
        );
        this.container.select('input[type="checkbox"]').each(
            function (element) {
                if (!element.up('.sp-methods')) {
                    element.observe('click', this.onOutsideCheckboxClick);
                }
            },
            this
        );
        $super();
    },
    /**
     * Handles changes on outside of payment methods container
     *
     * @param Event evt
     * @return void
     */
    handleOutsideCheckboxClick: function (evt) {
        if (!this.content.visible()) {
            this.handleChange({});
        }
    },
    /**
     * Add handler for after init observing
     *
     * @param String code handler code
     * @param Function func
     * @return void
     */
    addAfterInitFunction : function(code, func) {
        this.afterInitFunc.set(code, func);
    },

    /**
     * Calls after init handlers
     *
     * @return void
     */
    afterInit : function() {
        var values = this.afterInitFunc.values();
        for (var i = 0, l = values.length; i < l; i ++) {
            values[i]();
        }
    },
    /**
     * Forces payment method selection if there is only one
     *
     * @return void
     */
    initCheckout: function ($super) {
        $super();
        this.content.insert({after: this.errorEl});
        if (this.checkout.preloadedHtml.payment) {
            this.update(this.checkout.preloadedHtml.payment);
        }
    },
    /**
     * Checks that it is a single method
     *
     * @return void
     */
    checkOneMethod: function () {
        var methods = this.container.select('input[name="payment[method]"]');
        if (methods.length == 1) {
            var form = $('payment_form_' +methods.first().value);
            if (!form || form.select('input', 'select', 'textarea').length == 0) {
                this.switchMethod(methods.first().value);
            }
        }
    },
    /**
     * Switches payment method and displays related payment forms
     *
     * @param String method
     * @return void
     */
    switchMethod : function(method) {
        var fireAnEvent = arguments.length == 1 || arguments.length == 2 && arguments[1] == true;

        if (this.currentMethod && $('payment_form_'+this.currentMethod)) {
            this.changeVisible(this.currentMethod, true);
        }


        var form = $('payment_form_' + method);

        if (form){
            this.changeVisible(method, false);
            fireAnEvent || form.fire('payment-method:switched', {method_code : method});
        } else {
            //Event fix for payment methods without form like "Check / Money order"
            fireAnEvent || document.body.fire('payment-method:switched', {method_code : method});
        }

        if ((!form || form.select('select','input', 'textarea').length == 0)) {
            this.handleChange({});
            this.noReviewLoad = true;
        }

        if (this.currentMethod != method && this.checkout) {
            this.checkout.paymentRedirect = false;
        }

        this.currentMethod = method;
        window.currentPaymentMethod = this.currentMethod;
    },
    /**
     * Change payment form visibility
     *
     * @return void
     */
    changeVisible: function(method, mode) {
        var block = 'payment_form_' + method;
        [block + '_before', block, block + '_after'].each(function(el) {
            element = $(el);
            if (element) {
                element.style.display = (mode) ? 'none' : '';
                element.select('input', 'select', 'textarea').each(function(field) {
                    field.disabled = mode;
                });
                if (element.relatedElement) {
                    element.relatedElement.style.display = (mode) ? 'none' : '';
                    element.relatedElement.select('input', 'select', 'textarea').each(function(field) {
                        field.disabled = mode;
                    });
                }
            }
        });
    },
    getCurrentForm: function () {
        var block = 'payment_form_' + this.currentMethod;
        return $(block);
    },
    /**
     * Add handler for before validation observing
     *
     * @param String code handler code
     * @param Function func
     * @return void
     */
    addBeforeValidateFunction : function(code, func) {
        this.beforeValidateFunc.set(code, func);
    },
    /**
     * Calls before validate handlers
     *
     * @return void
     */
    beforeValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        var values = this.beforeValidateFunc.values();
        for (var i = 0, l = values.length; i < l; i ++) {
            hasValidation = true;
            if (values[i]() == false) {
                validateResult = false;
            }
        }

        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },
    /**
     * Validates payment form input and applies external validations
     *
     * @param Function $super parent method
     * @return Boolean
     */
    isValid: function($super) {
        if (this.errorEl) {
            this.errorEl.__advicevalidateAjax = 1;
            Validation.reset(this.errorEl);
        }
        var result = this.beforeValidate();
        if (result) {
            return true;
        }
        var methods = document.getElementsByName('payment[method]');
        if (methods.length==0) {
            var errorText = Translator.translate('Your order cannot be completed at this time as there is no payment methods available for it.');
            if (this.errorEl) {
                Validation.ajaxError(this.errorEl, errorText)
            } else {
                alert(errorText);
            }
            return false;
        }
        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return (arguments.length ? $super(arguments[0]) : $super());
            }
        }
        result = this.afterValidate();
        if (result) {
            return (arguments.length ? $super(arguments[0]) : $super());
        }
        var errorText = Translator.translate('Please specify payment method.');
        if (this.errorEl) {
            Validation.ajaxError(this.errorEl, errorText);
        } else {
            alert(errorText);
        }
        return false;
    },
    /**
     * Alias for isValid method
     *
     * @return Boolean
     */
    validate: function () {
        return this.isValid();
    },
    /**
     * Add handler for after validation observing
     *
     * @param String code handler code
     * @param Function func
     * @return void
     */
    addAfterValidateFunction : function(code, func) {
        this.afterValidateFunc.set(code, func);
    },
    /**
     * Calls after validate handlers
     *
     * @return void
     */
    afterValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        var values = this.afterValidateFunc.values();
        for (var i = 0, l = values.length; i < l; i ++) {
            hasValidation = true;
            if (values[i]() == false) {
                validateResult = false;
            }
        }

        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },
    /**
     * Initializes tooltips about CVV code
     *
     * @return void
     */
    initWhatIsCvvListeners: function(){
        $$('.cvv-what-is-this').each(function(element){
            Event.observe(element, 'click', toggleToolTip);
        });
    },
    /**
     * Handles submission complete of payment methods,
     * Displays errors if any
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

        if (result.redirect) {
            this.checkout.paymentRedirect = result.redirect;
        }

        if (result.error){
            if (result.fields) {
                var fields = result.fields.split(',');
                for (var i=0;i<fields.length;i++) {
                    var field = null;
                    if (field = $(fields[i])) {
                        Validation.ajaxError(field, result.error);
                    }
                }
            } else {
                Validation.ajaxError(this.errorEl, result.error);
                this.isLoading(false);
            }
            return;
        }
        return $super(response);
    }
});
