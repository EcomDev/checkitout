if (typeof window.EcomDev == 'undefined') {
    // Initiliaze namespace if it isn't defined yet
    window.EcomDev = {}; 
}

/**
 * Main Checkout class
 * 
 */
EcomDev.CheckItOut = Class.create({
    /**
     * Hash of checkout steps, 
     * filled during initialization
     * 
     * @type Hash
     */
    steps: $H({}),
    /**
     * Class constructor,
     * Initializes container object and the other stuff
     * 
     * @param Object config
     * @return void
     */
    initialize: function (config) {
        /**
         * Configuration initialization
         * 
         * @type Object
         */
        this.config = config;
        /**
         * Container html element
         * 
         * @type Element
         */
        this.container = $(config.container);
        /**
         * Content html element
         * 
         * @type Element
         */        
        this.content = this.container.down('.content');
        /**
         * Mask html element
         * 
         * @type Element
         */        
        this.mask = this.container.down('.friendly-checkout-loading');
        /**
         * On reload event handler that binded to checkout object scope
         * 
         * @type Function
         */
        this.onReload = this.handleReload.bind(this);
        /**
         * To reload object sequance
         * 
         * @type Array
         */
        this.toReload = [];
        // Initialize instace for static class
        EcomDev.CheckItOut.setInstance(this);
    },
    /**
     * Adds step to checkout steps hash object
     * 
     * @param EcomDev.CheckItOut.Step stepObject
     * @return void
     */
    addStep: function (stepObject) {
        if (this.steps.get(stepObject.code)) {
            var result = this.steps.unset(stepObject.code);
            delete result; // Remove old object from memory
        }

        this.steps.set(stepObject.code, stepObject);
        stepObject.addCallback(this.onReload);
        stepObject.setCheckout(this);
    },
    /**
     * Retrieve checkout step from hash object by id
     * 
     * @return EcomDev.CheckItOut.Step
     */
    getStep: function (stepId) {
        return this.steps.get(stepId);
    },
    /**
     * Ajax failure callback, 
     * used for ajax requests to redirect user to failure url
     * 
     * @return void
     */
    ajaxFailure: function(){
        location.href = this.config.failureUrl;
    },
    /**
     * Handles reload of particular checkout step object
     * Dispatches changes to the other steps that are in the relation
     * 
     * @return void
     */
    handleReload: function (stepObject) {
        var steps = this.steps.values();
        var reloadCallbacks = [];
        for (var i = 0, l = steps.length; i < l; i ++) {
            if (!steps[i].isRelated(stepObject)) {
                continue;
            }
            if (stepObject.isLoading()) {
                steps[i].showMask();
            } else if (Object.isFunction(steps[i].load)) {
                reloadCallbacks.push(steps[i]);
           } else {
                this.addToReload(steps[i]);
            }
        }
        this.reload();
        reloadCallbacks.invoke('load');
    },
    /**
     * Displays mask overlay for whole checkout page
     * 
     * @return void
     */
    showMask: function () {
        this.mask.show();
        var dimensions = this.content.getDimensions();
        this.mask.setStyle({
            width: dimensions.width + 'px', 
            height: this.getMaskHeight() + 'px',
            top: this.content.offsetTop + 'px',
            left: this.content.offsetLeft + 'px',
            opacity: 0.5
        });
    },
    getMaskHeight: function () {
        var elements = this.content.childElements();
        var height = 0;
        for (var i=0, l=elements.length; i < l; i++) {
            var item = elements[i];
            if (item.hasClassName('max-height')) {
                height += item.childElements().collect(function (el) {return el.getDimensions().height; }).max();
            } else {
                height += item.getDimensions().height + (parseInt(item.getStyle('margin-bottom'))||0) + (parseInt(item.getStyle('margin-bottom-width'))||0);
            }
        }
        return height;
    },
    /**
     * Hides displayed mask
     * 
     * @return void
     */
    hideMask: function () {
        this.mask.hide();
    },
    /**
     * Add steps to reload sequance object
     * 
     * @param EcomDev.CheckItOut.Step stepObject
     * @retun void
     */
    addToReload: function (stepObject) {
        this.toReload.push(stepObject);
    },
    /**
     * Reloads checkout steps that were added to sequance
     * 
     * @return void
     */
    reload: function () {
        var steps = [];
        var parameters = {
            'steps[]': []
        };
        
        while (this.toReload.length > 0) {
            var step = this.toReload.shift();
            steps.push(step);
            parameters['steps[]'].push(step.code);
            Object.extend(parameters, step.getValues());
        }
        
        if (steps.length > 0) {
            new Ajax.Request(this.config.reload, {
                parameters: parameters,
                method: 'POST',
                onComplete: function (result) {
                    var blocks = result.responseText.evalJSON();
                    for (var i=0, l = steps.length; i < l; i ++) {
                        if (blocks[steps[i].code]) {
                            steps[i].update(blocks[steps[i].code]);
                        }
                        steps[i].hideMask();
                   }
                }
            })
        }
    },
    /**
     * Submits the placing of the order,
     * Performs validation before sending data to backend
     * 
     * @return void
     */
    submit: function () {
        var steps = this.steps.values();
        var parameters = {};
        for (var i = 0, l = steps.length; i < l; i ++) {
            if (steps[i].isLoading() || !steps[i].isValid()) {
                return;
            }
            Object.extend(parameters, steps[i].getValues());
        }
        this.showMask();
        new Ajax.Request(this.config.save, {
            parameters: parameters,
            method: 'POST',
            onComplete: this.submitComplete.bind(this) 
        });
    },
    /**
     * Handler for completion of checkout step
     * 
     * @param Ajax.Response response
     * @return void
     */
    submitComplete: function (response) {
        this.hideMask();
        try{
            var result = response.responseText.evalJSON();
        }
        catch (e) {
            var result = {};
        }
        if (result.redirect) {
            location.href = response.redirect;
            return;
        }
        if (result.success) {
            window.location=this.config.success;
        }
        else{
            var msg = response.error_messages;
            if (typeof(msg)=='object') {
                msg = msg.join("\n");
            }
            alert(msg);
        }
     }
});

/**
 * Checkout step abstract class
 * 
 */
EcomDev.CheckItOut.Step = Class.create({
    /**
     * Step unique code
     * 
     * @type String
     */
    code: '',
    /**
     * Relations list
     * 
     * @type Array
     */
    relations: undefined,
    /**
     * Checkout object
     * 
     * @type EcomDev.CheckItOut
     */
    checkout: undefined, 
    /**
     * Change time out 
     * 
     * @type Number
     */
    changeInterval: 1200,
    /**
     * Complete callbacks
     * 
     * @type Array
     */
    callbacks: undefined,
    /**
     * Automaticaly submit element values?
     * 
     * @type Boolean
     */
    autoSubmit: true,
    /**
     * Automaticaly validate element values?
     * 
     * @type Boolean
     */
    autoValidate: true,
    /**
     * Step constructor, 
     * 
     * @param String container container element id
     * @param String saveUrl url for submitting of element values
     * @return void
     */
    initialize: function (container, saveUrl) {
        this.container = $(container);
        this.saveUrl = saveUrl;
        this.callbacks = [];
        this.relations = [];
        this.updater = this.submit.bind(this);
        this.onChange = this.handleChange.bind(this);
        this.onAjaxComplete = this.submitComplete.bind(this); 
        EcomDev.CheckItOut.addStep(this);
    },
    /**
     * Set checkout object to step 
     * 
     * @param Object checkout
     * @return void
     */
    setCheckout: function (checkout) {
        this.checkout = checkout;
        this.initCheckout();
    },
    /**
     * Init checkout place holder,
     * Used to initialize form, eg. removing of values
     * 
     * @return void
     */
    initCheckout: function () {
        this.mask = this.container.down('.step-loading');
        this.content = this.container.down('.step-content');
        if (this.content.down('from')) {
            this.content.down('from').observe('submit', function (evt) {
                    Event.stop(evt)
            })
        }
        this.bindFields();
    },
    /**
     * Check s step in loading or it's already loaded 
     * Set flag if the paramter is given
     * 
     * @param Boolean flag 
     * @return Boolean
     */
    isLoading: function (flag) {
        if (typeof flag == 'boolean') {
            this._isLoading = flag;
        }
        return this._isLoading;
    },
    /**
     * Retrieve from element values
     * 
     * @return Object
     */
    getValues: function () {
        return Form.serializeElements(
            this.getElements(),
            true
        );
    },
    /**
     * Retrieve form elements
     * 
     * @return Array 
     */
    getElements: function () {
        return this.content.select('input', 'select', 'textarea');
    },
    /**
     * Add related step codes to this step,
     * Used for updating of content if related step was changed
     * 
     * @param Array|String steps
     * @return void
     */
    addRelation: function (steps) {
        this.relations.push(steps);
        if (Object.isArray(steps)) {
            this.relations = this.relations.flatten();
        }
    },
    /**
     * Check is this step related to the specified one
     * 
     * @param stepObject related step object
     * @return Boolean
     */
    isRelated: function (stepObject) {
        return this.relations.include(stepObject.code); 
    },
    /**
     * Validates internal content of the checkout step
     * 
     * @return Boolean
     */
    isValid: function () {
        return this.content.select('input', 'select', 'textarea')
                   .map(Validation.validate).all();
    },
    /**
     * Bind field after checkout initialization or after content update
     * 
     * @return void
     */
    bindFields: function () {
        if (!this.autoSubmit) {
            return;
        }
        var fields = this.content.select('input', 'select', 'textarea');
        for (var i = 0, l = fields.length; i < l; i ++) {
            if (fields[i].hasClassName('no-autosubmit')) {
                continue;
            }
            fields[i].observe('change', this.onChange);
            if (['input', 'textarea'].include(fields[i].tagName.toLowerCase())) {
  
                fields[i].observe('keyup', this.onChange);
                if (fields[i].type && ['radio', 'checkbox', 'button'].include(fields[i].type)) {
                    fields[i].observe('click', this.onChange);
                }
            }
        }
    },
    /**
     * Handler for changing of the elements value
     * 
     * @param Event evt
     * @return void
     */
    handleChange: function (evt) {
        if (this.timeout) {
            clearInterval(this.timeout);
        }
        this.timeout = setTimeout(this.updater, this.changeInterval);
    },
    /**
     * Submits checkout step form values
     * 
     * @return void
     */
    submit: function () {
        if (this.isLoading()) {
            return;
        }
        
        if (this.timeout) {
            clearInterval(this.timeout);
            this.timeout = undefined;
        }
        
        if (!this.autoValidate || this.isValid()) {
            this.isLoading(true);
            this.respondCallbacks();
            new Ajax.Request(this.saveUrl, {
                parameters: this.getValues(),
                method: 'POST',
                onComplete: this.onAjaxComplete,
                onFailure: this.checkout.onFailure
            });
        }
    },
    /**
     * Handles completing of ajax request for form submit
     * 
     * @param Ajax.Response response [optional]
     * @return void
     */
    submitComplete: function (response) {
        this.isLoading(false);
        this.respondCallbacks();
    },
    /**
     * Finds container element from its child
     * 
     * @param Element|String element id or element object
     * @return Element
     */
    findContainer: function (element) {
        return $(element).up('.checkout-step');
    },
    /**
     * Adds callback to checkout step submitting
     * 
     * @param Function callback
     * @return void
     */
    addCallback: function (callback) {
        this.callbacks.push(callback);
    },
    /**
     * Responds to all added callbacks 
     * 
     * @return void
     */
    respondCallbacks: function () {
        for (var i=0, l = this.callbacks.length; i < l; i++) {
            this.callbacks[i](this);
        }
    },
    /**
     * Displays mask overlay for checkout step content element
     * 
     * @return void
     */
    showMask: function () {
        this.mask.show();
        var dimensions = this.content.getDimensions();
        this.mask.setStyle({
            width: dimensions.width + 'px', 
            height: dimensions.height + 'px',
            top: this.content.offsetTop + 'px',
            left: this.content.offsetLeft + 'px',
            opacity: 0.5
        });
    },
    /**
     * Hides diplayed mask
     * 
     * @return void
     */
    hideMask: function () {
        this.mask.hide();
    },
    /**
     * Update content of checkout step content element
     * 
     * @param String htmlContent
     * @return void
     */
    update: function (htmlContent) {
        this.content.update(htmlContent);
        this.bindFields();
    }
});

/**
 * Static checkout class definition, used to syncronize objects 
 * that weren't initialized yet
 * 
 */

/**
 * List of checkout steps that were initialized 
 * before initialization of checkout object itself
 * 
 */
EcomDev.CheckItOut.initialSteps = [];

/**
 * Sets checkout instance to static class,
 * Dispatches checkout object to checkout objects
 *
 * @param EcomDev.CheckItOut instance
 * @return void 
 */
EcomDev.CheckItOut.setInstance = function (instance) {
    this.instance = instance;
    while (this.initialSteps.length) {
        this.instance.addStep(this.initialSteps.shift());
    };
}

/**
 * Adds step to checkout object
 * 
 * @param EcomDev.CheckItOut.Step stepObject
 * @return void 
 */
EcomDev.CheckItOut.addStep = function (stepObject) {
    if (this.instance) {
        this.instance.addStep(stepObject);
    } else {
        this.initialSteps.push(stepObject);
    }
}

/**
 * Checkout login step, handles type of checkout (guest or registered)
 * 
 * 
 */
var LoginStep = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     * 
     * @type String
     */
    code: 'login',
    autoSubmit: false,
    /**
     * Step constructor
     * 
     * @param Function $super parent constructor
     * @param String form
     * @param String saveUrl
     * @return void
     */
    initialize: function ($super, form, saveUrl) {
        this.saveUrl = saveUrl;
        var container = this.findContainer(form);
        $super(container, saveUrl);
    },
    initCheckout: function ($super) {
        $super();
        this.container.down('.popup-trigger').observe('click', this.togglePopUp.bind(this));
        this.container.down('.popup-close').observe('click', this.hidePopUp.bind(this));
        if (this.container.down('.popup-content .messages')) {
            this.showPopUp();
        }
    },
   /**
     * This step is always valid :)
     * 
     * @return Boolean
     */
    isValid: function () {
        return true;
    },
    /**
     * Submits checkout method to the backend
     * 
     * @return void
     */
    setCheckoutMethod: function (method) {
        this.checkout.method = method;
        Element.hide('register-customer-password');
        new Ajax.Request(
            this.saveUrl,
            {method: 'post', onFailure: this.checkout.onFailure, parameters: {method:this.checkout.method}}
        );
    },
    togglePopUp: function () {
        this.container.down('.popup').visible() ?
            this.hidePopUp():
            this.showPopUp();
    },
    showPopUp: function () {
        this.container.down('.popup').show();
    },
    hidePopUp: function () {
        this.container.down('.popup').hide();
    }
});

/**
 * Billing checkout step, used to manage billing address
 * 
 * 
 */
var Billing = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     * 
     * @type String
     */
    code: 'billing',
    autoValidate: false,
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
        $super(container, saveUrl);
    },
    /**
     * Clears address form on load
     * 
     * @param $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        var insertAbove = false;
        if ($('billing:use_for_shipping_yes')) {
            insertAbove = $('billing:use_for_shipping_yes').up('li').previous('li');
            $('billing:use_for_shipping_yes').up('li').remove();
        }
        if ($('billing:use_for_shipping_no')) {
            insertAbove = $('billing:use_for_shipping_no').up('li').previous('li');
            $('billing:use_for_shipping_no').up('li').remove();
        }
        
        if (insertAbove) {
            var element = new Element('li', {
                'class': 'control',
            });
            insertAbove.insert({after:element});
            element.insert(new Element(
                'input', 
                {type:'checkbox', 
                 id: 'billing:use_for_shipping', 
                 value:'1',
                 'class': 'checkbox',
                 name: 'billing[use_for_shipping]',
                 checked: 'checked', 
                 title: this.checkout.config.useForShippingLabel}));
            element.insert(
                new Element('label', {'for': 'billing:use_for_shipping'})
                    .update(this.checkout.config.useForShippingLabel));
            
        }
        
        if ($('register-customer-password')) {
            var registerElement = new Element('li', {
                'class': 'control',
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
        this.newAddress(true);
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
     * Diplays or hides address form
     * 
     * @param Boolean isNew
     * @return void
     */
    newAddress: function(isNew){
        if (isNew) {
            this.resetSelectedAddress();
            Element.show('billing-new-address-form');
        } else {
            Element.hide('billing-new-address-form');
        }
    },
    /**
     * Resets address select to 'new address' value
     * 
     * @return void
     */
    resetSelectedAddress: function(){
        var selectElement = $('billing-address-select')
        if (selectElement) {
            selectElement.value='';
        }
    },
    /**
     * Submits address data, checks 
     * if billing address is the same as shipping 
     * and updates it accordinly
     * 
     * @param Function $super parent method
     * @return void
     */
    submit: function ($super) {
        if ($('billing:use_for_shipping') && $('billing:use_for_shipping').checked) {
            this.checkout.getStep('shipping').submit();
        }
        return $super();
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
                var fieldName = elements[i].indentify().replace(/^billing:/, '');
                elements[i].value = elementValues[fieldName] ? elementValues[fieldName] : '';
                if (fieldName == 'country_id' && billingForm){
                    billingForm.elementChildLoad(elements[i]);
                }
            }
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
        if ($('shipping:same_as_billing') && $('shipping:same_as_billing').checked) {
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
        $('shipping:same_as_billing').checked = flag;
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
            if (window.billingRegionUpdater) {
                billingRegionUpdater.update();
            }
        }
        return $super();
    }
});

/**
 * Shipping address selection checkout step
 * 
 * 
 */
var Shipping = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     * 
     * @type String
     */
    code: 'shipping',
    autoValidate: false,
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
        $super(container, saveUrl);
    },
    /**
     * Clears address form on load
     * 
     * @param $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        $super();
        this.resetSelectedAddress();
        this.newAddress(true);
        $('billing:use_for_shipping').observe('click', function (evt) {
            var element = Event.element(evt);
            this.setSameAsBilling(element.checked);
        }.bind(this));
    
        if ($('billing:use_for_shipping').checked) {
            this.setSameAsBilling(true);
        }
        
        $('shipping:same_as_billing').up('li').remove();
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
     * Diplays or hides address form,
     * unchecks sync chebox if addresses are not the same
     * 
     * @param Boolean isNew
     * @return void
     */
    newAddress: function(isNew){
        if (!this.checkout) {
            return;
        }
        if ($('billing:use_for_shipping').checked && 
            $('billing-address-select') &&  
            $('billing-address-select').value != $('shipping-address-select').value) {
            this.setSameAsBilling(false);
        }
        if (isNew) {
            this.resetSelectedAddress();
            Element.show('shipping-new-address-form');
        } else {
            Element.hide('shipping-new-address-form');
        }
    },
    /**
     * Resets address select to 'new address' value
     * 
     * @return void
     */
    resetSelectedAddress: function(){
        var selectElement = $('shipping-address-select')
        if (selectElement) {
            selectElement.value='';
        }
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
                var fieldName = elements[i].indentify().replace(/^shipping:/, '');
                elements[i].value = elementValues[fieldName] ? elementValues[fieldName] : '';
                if (fieldName == 'country_id' && shippinfForm){
                    shippingForm.elementChildLoad(elements[i]);
                }
            }
        }
    },
    /**
     * Set the same as billing flag for shipping address
     * 
     * @param Boolean flag
     * @return void
     */
    setSameAsBilling: function(flag) {
        $('billing:use_for_shipping').checked = flag;
        if (flag) {
            this.container.hide();
            this.syncWithBilling();
        } else {
            this.container.show();
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
            //$('shipping:country_id').value = $('billing:country_id').value;
            shippingRegionUpdater.update();
            $('shipping:region_id').value = $('billing:region_id').value;
            $('shipping:region').value = $('billing:region').value;
            //shippingForm.elementChildLoad($('shipping:country_id'), this.setRegionValue.bind(this));
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
        $('shipping:region').value = $('billing:region').value;
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
            if (window.shippingRegionUpdater) {
                shippingRegionUpdater.update();
            }
        }
        return $super();
    }
});

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
    /**
     * Checkout step constructor
     * 
     * @param Function $super parent constructor method
     * @return void 
     */
    initialize: function ($super, form, saveUrl) {
        var container = this.findContainer(form);
        $super(container, saveUrl);
        this.addRelation('shipping');
    },
    /**
     * Performs checking of fullfillment of shipping method selection
     * 
     * @param Function $super parent method
     * @return Boolean
     */
    isValid: function ($super) {
        var methods = document.getElementsByName('shipping_method');
        if (methods.length==0) {
            alert(Translator.translate('Your order cannot be completed at this time as there is no shipping methods available for it. Please make neccessary changes in your shipping address.'));
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
        alert(Translator.translate('Please specify shipping method.'));
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
        $super();
        this.checkOneMethod();
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
        var oneMethod = this.content.down('.no-display input[type=radio]');
        if (oneMethod) {
            if (!this.lastSubmitted ||
                this.lastSubmitted.shipping_method != oneMethod.value) {
                oneMethod.checked = false;
            }
            oneMethod.up('.no-display').removeClassName('no-display');
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
            alert(result.message);
            return;
        }
        return $super();
    }
});

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
     * Payment checkout step constructor
     * 
     * @param Function $super parent constructor
     * @param String from 
     * @param String saveUrl
     * @return void
     */
    initialize: function($super, form, saveUrl){
        this.form = form;
        this.saveUrl = saveUrl;
        var container = this.findContainer(form);
        $super(container, saveUrl);
        this.addRelation(['billing', 'shipping_method']);
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
        $super(content);
        var fieldNames = Object.keys(values);
        for (var i=0, l=fieldNames.length; i < l; i++) {
            var field = this.content.down('*[name="' + fieldNames[i] + '"]');
            if (field && !['checkbox', 'radio'].include(field.type)) {
                field.value = values[fieldNames[i]];
            } else if (field) {
                var elements = this.content.select('*[name="' + fieldNames[i] + '"]');
                elements.map(function (element) { 
                    if (element.value == values[fieldNames[i]]) {
                        element.checked = true
                    }
                });
           }
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
        var elements = Form.getElements(this.form);
        var method = null;
        for (var i=0; i<elements.length; i++) {
            if (elements[i].name=='payment[method]') {
                if (elements[i].checked) {
                    method = elements[i].value;
                }
            } else {
                elements[i].disabled = true;
            }
            elements[i].setAttribute('autocomplete','off');
        }
        if (method) this.switchMethod(method);
        this.afterInit();
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
            function (element) { element.addClassName('no-autosubmit'); }
        );
        $super();
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
     * Switches payment method and displays related payment forms
     * 
     * @param String method
     * @return void
     */
    switchMethod: function(method){
        if (this.currentMethod && $('payment_form_'+this.currentMethod)) {
            var form = $('payment_form_'+this.currentMethod);
            form.style.display = 'none';
            var elements = form.select('input', 'select', 'textarea');
            for (var i=0; i<elements.length; i++) elements[i].disabled = true;
        }
        if ($('payment_form_'+method)){
            var form = $('payment_form_'+method);
            form.style.display = '';
            var elements = form.select('input', 'select', 'textarea');
            for (var i=0; i<elements.length; i++) elements[i].disabled = false;
        } else {
            //Event fix for payment methods without form like "Check / Money order"
            document.body.fire('payment-method:switched', {method_code : method});
            this.handleChange({});
        }
        this.currentMethod = method;
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
        var result = this.beforeValidate();
        if (result) {
            return true;
        }
        var methods = document.getElementsByName('payment[method]');
        if (methods.length==0) {
            var errorText = Translator.translate('Your order cannot be completed at this time as there is no payment methods available for it.');
            if (this.container.down('.ajax-error')) {
                Validation.ajaxError(this.container.down('.ajax-error'), errorText)
            } else {
                alert(errorText);
            }
            return false;
        }
        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return $super();
            }
        }
        result = this.afterValidate();
        if (result) {
            return $super();
        }
        var errorText = Translator.translate('Please specify payment method.');
        if (this.container.down('.ajax-error')) {
            Validation.ajaxError(this.container.down('.ajax-error'), errorText)
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
                alert(result.error);
            }
            
            return;
        }
        return $super();
    }
});

/**
 * Order review checkout step, reloads items grid and totals
 * 
 */
var Review = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     * 
     * @type String
     */
    code: 'review',
    autoSubmit: false,
    /**
     * Order review checkout step initalization
     * 
     * @param Function $super parent constructor
     * @param String loadUrl
     * @param String updateElement
     * @param String saveUrl
     * @param String successUrl [used only in onepagecheckout]
     * @param Element agreementsForm [used only in onepagecheckout]
     * @return void
     */
    initialize: function($super, loadUrl, updateElement, saveUrl, successUrl, agreementsForm){
        this.successUrl = successUrl;
        this.agreementsForm = agreementsForm;
        this.loadUrl = loadUrl;
        this.onLoadComplete = this.loadComplete.bind(this);
        this.updateElement = $(updateElement);
        var container = this.findContainer(this.updateElement);
        $super(container, saveUrl);
        this.addRelation(['billing', 'payment', 'shipping', 'shipping_method']);
    },
    /**
     * Loads order review block after checkout initialization
     * 
     * @param Function $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        $super();
        this.load();
    },
    /**
     * Loads order review info block
     * 
     * @return void
     */
    load: function () {
        this.showMask();
        new Ajax.Request(this.loadUrl, {
            method: 'POST',
            onComplete: this.onLoadComplete,
            onFailure: this.checkout.ajaxFailure
        });
    },
    /**
     * Handles load complete, used for hiding the mask
     * and updating inner content
     * 
     * @param Ajax.Response response
     * @return void
     */
    loadComplete: function (response) {
        this.hideMask();
        this.updateElement.update(response.responseText);
    }
});