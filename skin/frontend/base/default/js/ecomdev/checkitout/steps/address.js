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
 * Abstract class for address related checkout steps
 *
 *
 */
EcomDev.CheckItOut.Step.Address = Class.create(EcomDev.CheckItOut.Step, {
    /*
     * Automatically submit data even if it is invalid
     *
     */
    autoSubmitInvalid: true,
    /**
     * Checkout step constructor
     *
     * @param Function $super parent constructor
     * @param String form
     * @param String addressUrl
     * @param String saveUrl
     * @return void
     */
    initialize: function ($super, form, addressUrl, saveUrl) {
        /**
         * Url for loading of addresses
         *
         * @type String
         */
        this.addressUrl = addressUrl;
        /**
         * Address load callback
         *
         * @type Function
         */
        this.onAddressLoad = this.fillForm.bind(this);
        var container = this.findContainer(form);
        this.submitError = false;
        $super(container, saveUrl);
    },
    /**
     * Clears address form on load
     *
     * @param $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        if (!this.isAddressSelected()) {
            this.newAddress(true);
        }
        $super();
    },
    /**
     * Sets address from dropdown,
     * old method in one page,
     * left for backward capability
     *
     * @param String addressId
     * @return void
     */
    setAddress: function(addressId){
        if (addressId) {
            request = new Ajax.Request(
                this.addressUrl+addressId,
                {method:'get', onSuccess: this.onAddressLoad, onFailure: checkout.ajaxFailure.bind(checkout)}
            );
        }
        else {
            this.fillForm(false);
        }
    },
    /**
     * Diplays or hides address form
     *
     * @param Boolean isNew
     * @return void
     */
    newAddress: function(isNew){
        if (isNew) {
            this.resetSelectedAddress();
            Element.show(this.code + '-new-address-form');
        } else {
            Element.hide(this.code + '-new-address-form');
        }
    },
    /**
     * Resets address select to 'new address' value
     *
     * @return void
     */
    resetSelectedAddress: function(){
        if (this.getSelectElement()) {
            this.getSelectElement().value='';
        }
    },
    /**
     * Checks that address was already selected
     *
     * @return Boolean
     */
    isAddressSelected: function () {
        return this.getSelectElement() && this.getSelectElement().value;
    },
    /**
     * Returns Select Element
     *
     * @return Element|Boolean
     */
    getSelectElement: function() {
        return $(this.code + '-address-select');
    },
    /**
     * Fills address form with received address values
     *
     * @param Ajax.Response transport
     * @return void
     */
    fillForm: function(transport){
        var elementValues = {};
        if (transport && transport.responseText){
            if (transport.responseText.isJSON()) {
                elementValues = transport.responseText.evalJSON();
            }
        }
        else{
            this.resetSelectedAddress();
        }

        var elements = this.content.select('input', 'textarea', 'select');

        for (var i = 0, l = elements.length; i < l; i++) {
            if (elements[i].id) {
                var fieldName = elements[i].identify().replace(new RegExp('^' + this.code + ':'), '');
                elements[i].value = elementValues[fieldName] ? elementValues[fieldName] : '';
                if (fieldName == 'country_id' && billingForm){
                    billingForm.elementChildLoad(elements[i]);
                }
            }
        }
    },
    /**
     * Handles submission complete and displays related errors
     * if there were any
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
            /*
             * This one was left from core OPC,
             * but it is not needed in without validation address save
             *
             if (window[this.code + 'RegionUpdater']) {
             window[this.code + 'RegionUpdater'].update();
             }
             */
            this.submitError = result;
            if (this.hasBackendError()) {
                this._showAjaxError(result);
            }
        } else {
            this.submitError = false;
        }
        return $super(response);
    },
    hasBackendError: function () {
        if (this.submitError !== false
            &&
            (this.submitError.error == '-1'
                || (this.submitError.value
                    && this.submitError.field
                    && this.submitError.value != $(this.code + ':' + this.submitError.field).value
                    )
            )) {
            // Clear error if it is outdated.
            this.submitError = false;
        }

        return this.submitError !== false;
    },
    isValid: function ($super) {
        var hasError = this.hasBackendError();

        var result = true;

        if (hasError) {
            result = !this._showAjaxError(this.submitError);

            if (arguments.length === 1 || arguments[1] === true) {
                result = false;
            }
        }

        if (result && arguments.length > 1) {
            result = $super(arguments[1]);
        } else if (result) {
            result = $super();
        }

        return result;
    },
    _showAjaxError: function (result) {
        if (result.field
            && $(this.code + ':' + result.field)
            && $(this.code + ':' + result.field).wasChanged) {
            var elm = $(this.code + ':' + result.field);
            var classNames = $w(elm.className);
            var isValid = classNames.all(function(className) {
                var validatorFunction = Validation.get(className);
                return !Validation.isVisible(elm) || validatorFunction.test(elm.value, elm);
            });

            if (isValid) {
                // Show message only if original validation passed
                Validation.ajaxError($(this.code + ':' + result.field), result.message);
                return true;
            }
        }

        return false;
    }
});

/**
 * Billing checkout step, used to manage billing address
 *
 *
 */
var Billing = Class.create(EcomDev.CheckItOut.Step.Address, {
    /**
     * Checkout step unique code
     *
     * @type String
     */
    code: 'billing',

    /**
     * List of fields 
     * that trigger auto-submit invalid event
     * 
     * @type Array
     */
    isAutosubmitInvalidFields: [
        'billing:country_id',
        'billing:region_id',
        'billing:city',
        'billing:region',
        'billing:email',
        'billing:postcode'
    ],
    /**
     * Clears address form on load
     *
     * @param $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        this.insertUseForShippingCheckbox();
        this.insertRegistrationFields();
        $super();
    },
    /**
     * Inserts use for shipping checkbox bellow address form,
     * Removes old elements
     *
     * @return void
     */
    insertUseForShippingCheckbox: function () {
        var insertAbove = false;
        var isChecked = true;
        if ($('billing:use_for_shipping_yes')) {
            isChecked = $('billing:use_for_shipping_yes').checked;
            insertAbove = $('billing:use_for_shipping_yes').up('li').previous();
            $('billing:use_for_shipping_yes').up('li').remove();
        }

        if ($('billing:use_for_shipping_no')) {

            insertAbove = $('billing:use_for_shipping_no').up('li').previous();
            $('billing:use_for_shipping_no').up('li').remove();
        }

        if (insertAbove) {
            var element = new Element('li', {
                'class': 'control'
            });

            insertAbove.insert({after:element});

            var inputConfig = {
                type:'checkbox',
                id: 'billing:use_for_shipping',
                value:'1',
                'class': 'checkbox',
                name: 'billing[use_for_shipping]',
                title: this.checkout.config.useForShippingLabel
            };

            if (isChecked) {
                inputConfig.checked = 'checked';
            }

            element.insert(new Element('input', inputConfig));

            element.insert(
                new Element('label', {'for': 'billing:use_for_shipping'})
                    .update(this.checkout.config.useForShippingLabel));

        }
    },
    /**
     * Moves registration fields below billing address for not logged in customers
     *
     * @return void
     */
    insertRegistrationFields: function () {
        if (this.checkout.config.isAllowedGuestCheckout) {
            if ($('register-customer-password')) {
                var registerElement = new Element('li', {
                    'class': 'control'
                });
                $('register-customer-password').insert({'before':registerElement});
                registerElement.insert(new Element(
                    'input',
                    {type:'checkbox',
                        id: 'billing:create_an_account',
                        value:'1',
                        'class': 'checkbox  no-autosubmit',
                        title: this.checkout.config.useForShippingLabel}));
                $('billing:create_an_account').observe('click', this.accountCheckbox.bind(this));
                $('billing:create_an_account').observe('change', this.accountCheckbox.bind(this));
                registerElement.insert(
                    new Element('label', {'for': 'billing:create_an_account'})
                        .update(this.checkout.config.createAccountLabel));
                this.accountCheckbox($('billing:create_an_account'));
            }
        }
    },
    /**
     * Observes "Create An Account" checkbox click event, and sets appropriate checkout method
     *
     * @param Event evt
     * @return void
     */
    accountCheckbox: function (evt) {
        var element = (Object.isFunction(evt.identify) ? evt : Event.element(evt));
        if ($('register-customer-password').visible() == element.checked) {
            return;
        }
        if (element.checked) {
            this.checkout.getStep('login').setCheckoutMethod('register');
            $('register-customer-password').show();
        } else {
            this.checkout.getStep('login').setCheckoutMethod('guest');
            $('register-customer-password').hide();
        }
        if (!Object.isFunction(evt.identify)) {
            Event.extend(evt).stopPropagation();
        }
    },
    /**
     * Handle fields change, syncronized shipping
     * and billing address fields if the option is selected
     *
     * @param Function $parent
     * @param Event evt
     * @return void
     */
    handleChange: function ($super, evt) {
        $super(evt);
        if ($('billing:use_for_shipping') && $('billing:use_for_shipping').checked) {
            this.checkout.getStep('shipping').syncWithBilling();
        }
    },
    /**
     * Set the same as billing checkbox for billing address
     *
     * @param Boolean flag
     * @return void
     */
    setUseForShipping: function(flag) {
        $('billing:use_for_shipping').checked = flag;
    }
});

/**
 * Shipping address selection checkout step
 *
 *
 */
var Shipping = Class.create(EcomDev.CheckItOut.Step.Address, {
    /**
     * Checkout step unique code
     *
     * @type String
     */
    code: 'shipping',

    /**
     * List of fields
     * that trigger auto-submit invalid event
     *
     * @type Array
     */
    isAutosubmitInvalidFields: [
        'shipping:country_id',
        'shipping:region_id',
        'shipping:city',
        'shipping:region',
        'shipping:postcode'
    ],
    
    /**
     * Clears address form on load
     *
     * @param $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        $super();
        $('shipping:same_as_billing').up('li').remove();

        this.container.down('form').insert(new Element('input', {
            type: 'hidden',
            id: 'shipping:same_as_billing',
            name:'shipping[same_as_billing]'
        }));

        $('billing:use_for_shipping').observe('click', function (evt) {
            var element = Event.element(evt);
            this.setSameAsBilling(element.checked);
        }.bind(this));

        if ($('billing:use_for_shipping').checked) {
            this.setSameAsBilling(true);
        }

        if (this.checkout && this.checkout.useClassForHide) {
            this.container.insert(new Element('div', {'class': 'same-as-billing-overlay'}));
            this.sameAsBillingOverlay =  this.container.down('div.same-as-billing-overlay');
            this.updateSameAsBillingOverlay();
        }

        if (this.isAddressSelected()) {
            this.submit();
        }
    },
    /**
     * Updates size of the overlay depending on the size of
     */
    updateSameAsBillingOverlay: function () {
        if (this.sameAsBillingOverlay) {
            var dimensions = this.container.getDimensions();
            this.sameAsBillingOverlay.setStyle({
                opacity: this.checkout.config.overlayOpacity,
                width: dimensions.width + 'px',
                height: dimensions.height + 'px'
            });
        }
    },
    /**
     * Sets address from dropdown,
     * old method in one page,
     * left for backward capability
     *
     * @param String addressId
     * @return void
     */
    setAddress: function(addressId){
        if (addressId) {
            request = new Ajax.Request(
                this.addressUrl+addressId,
                {method:'get', onSuccess: this.onAddressLoad, onFailure: checkout.ajaxFailure.bind(checkout)}
            );
            this.updateSameAsBillingOverlay();
        }
        else {
            this.fillForm(false);
            this.updateSameAsBillingOverlay();
        }
    },
    /**
     * Diplays or hides address form,
     * unchecks sync chebox if addresses are not the same
     *
     * @param Boolean isNew
     * @return void
     */
    newAddress: function($super, isNew){
        if (!this.checkout) {
            return;
        }
        if ($('billing:use_for_shipping').checked &&
            this.checkout.getStep('billing').getSelectElement() &&
            this.checkout.getStep('billing').getSelectElement().value != this.getSelectElement().value) {
            //this.setSameAsBilling(false);
        }
        $super(isNew);
        this.updateSameAsBillingOverlay();
    },
    /**
     * Set the same as billing flag for shipping address
     *
     * @param Boolean flag
     * @return void
     */
    setSameAsBilling: function(flag) {
        $('billing:use_for_shipping').checked = flag;
        $('shipping:same_as_billing').value = (flag ? 1 : 0);
        if (flag) {
            if (!this.checkout || !this.checkout.useClassForHide) {
                this.container.hide();
            } else {
                this.container.addClassName('same-as-billing');
            }
            this.submitError = false; // Remove validation from it
            this.syncWithBilling();
        } else {
            if (!this.checkout || !this.checkout.useClassForHide) {
                this.container.show();
            } else {
                this.container.removeClassName('same-as-billing');
            }
        }
    },
    /**
     * Synchronizes billing and shipping addresses
     *
     * @return void
     */
    syncWithBilling: function () {
        $('billing-address-select') && this.newAddress(!$('billing-address-select').value);
        if (!$('billing-address-select') || !$('billing-address-select').value) {
            arrElements = this.getElements();
            for (var elemIndex in arrElements) {
                if (arrElements[elemIndex].id) {
                    var sourceField = $(arrElements[elemIndex].id.replace(/^shipping:/, 'billing:'));
                    if (sourceField){
                        arrElements[elemIndex].value = sourceField.value;
                    }
                }
            }
            if (window.shippingRegionUpdater) {
                shippingRegionUpdater.update();
            }
            if ($('shipping:region_id') && $('billing:region_id')) {
                $('shipping:region_id').value = $('billing:region_id').value;
                $('shipping:region').value = $('billing:region').value;
            }
        } else {
            $('shipping-address-select').value = $('billing-address-select').value;
        }
    },

    /**
     * Set region value into form field from billing ones
     *
     * @return void
     */
    setRegionValue: function(){
        if ($('shipping:region') && $('billing:region')) {
            $('shipping:region').value = $('billing:region').value;
        }
    }
});
