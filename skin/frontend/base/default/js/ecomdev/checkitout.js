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

if (typeof window.EcomDev == 'undefined') {
    // Initiliaze namespace if it isn't defined yet
    window.EcomDev = {}; 
}

/**
 * Object replacer if they exists in system
 * 
 * 
 */
EcomDev.Replacer = new (Class.create({
    /**
     * Replaces system object
     * 
     * @return void
     */
    replace: function (realtimeObject, destinationObject, destinationKey, replacement) {
        if (typeof destinationObject[destinationKey] == 'undefined') {
            return;
        }
        this.proxy(realtimeObject, replacement);
        (function (){destinationObject[destinationKey] = replacement; }).defer();
    },
    /**
     * Proxies object methods
     * 
     * @return void
     */
    proxy: function (proxyObject, realObject) {
        for (var key in realObject) {
            if (Object.isFunction(realObject[key])) {
                proxyObject[key] = realObject[key].bind(realObject);
            }
        }
        
        Object.extend(proxyObject, realObject);
    }
}));

Element.addMethods({
    moveToBody: function (element) {
        Element.remove(element);
        Element.insert(document.body, element);
    }
});

EcomDev.DomReady = new (Class.create({
    initialize: function () {
        document.observe('dom:loaded', this.ready.bind(this));
        this.actions = [];
    },
    ready: function () {
        for (var i = 0, l = this.actions.length; i < l; i++) {
            this.actions[i]();
        }
    },
    add: function (callback) {
        this.actions.push(callback);
    }
}))();

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

        if (this.config.useClassForHide) {
            this.useClassForHide = true;
        } else {
            this.useClassForHide = false;
        }

        /**
         * Container html element
         * 
         * @type Element
         */
        this.container = $(config.container);
        this.accordion = {container: this.container}; // Adds compatibility for authorize direct post
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
        this.mask = this.container.up().down('.checkitout-checkout-loading');
        EcomDev.DomReady.add(this.mask.moveToBody.bind(this.mask));

        /**
         * Overlay html element
         * 
         * @type Element
         */        
        this.overlay = this.container.up().down('.checkitout-checkout-overlay');
        EcomDev.DomReady.add(this.overlay.moveToBody.bind(this.overlay));

        /**
         * On reload event handler that binded to checkout object scope
         * 
         * @type Function
         */
        this.onReload = this.handleReload.bind(this);
        
        /**
         * Handle for completion of reloading checkout steps layout
         * 
         * @type Function
         */
        this.onReloadComplete = this.handleRealoadComplete.bind(this);
        
        /**
         * To reload object sequance
         * 
         * @type Array
         */
        this.toReload = [];
        
        this.stepHash = $H(this.config.stepHash);
        // Initialize instace for static class
        EcomDev.CheckItOut.setInstance(this);
        for (var i= 0, v=this.steps.values(), l= v.length; i < l; i ++) {
            v[i].initCheckoutAfter();
        }
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
     * Check that any of the steps is loading
     * 
     * @return Boolean
     */
    isLoading: function () {
        return this.steps.any(function (pair) {
           return pair.value.isLoading();
        })
    },
    /**
     * Check that any of the steps is in change timeout process
     *
     * @return Boolean
     */
    isChangeTimeout: function () {
        return this.steps.any(function (pair) {
            return pair.value.isChangeTimeout();
        })
    },
    /**
     * Notifies submit button on changes in loading process
     *
     *
     */
    notifyLoading: function () {
        var isDisabled = this.isLoading() || this.isChangeTimeout();
        var buttons = this.getSubmitButtons();
        for (var i = 0, l = buttons.length; i < l; i++) {
            if (isDisabled) {
                buttons[i].addClassName('disabled');
            } else {
                buttons[i].removeClassName('disabled');
            }
        }
    },
    /**
     * Returns list of submit buttons
     *
     * @return Enumerable
     */
    getSubmitButtons: function () {
        return $$('button.btn-checkout');
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
        var reloadCallbacks = [];
        this.onlyHashed = (stepObject.lastHash ? stepObject.lastHash : false);
        this.collectReload(stepObject, reloadCallbacks);
        this.onlyHashed = false;
        this.reload();
        this.invokeCallbacks(reloadCallbacks);
    },
    /**
     * Handles reload of particular checkout step object
     * Dispatches changes to the other steps that are in the relation
     * 
     * @return void
     */
    reloadSteps: function (steps) {
        var reloadCallbacks = [];
        for (var i = 0, l = steps.length; i < l; i ++) {
            if (this.getStep(steps[i])) {
                this.collectReload(this.getStep(steps[i]), reloadCallbacks);
            }
        }
        this.reload();
        this.invokeCallbacks(reloadCallbacks);
    },
    /**
     * Collects steps that should be reloaded
     * 
     * @param EcomDev.CheckItOut.Step stepObject
     * @param Array reloadCallbacks
     * @return void
     */
    collectReload: function (stepObject, reloadCallbacks) {
        var steps = this.steps.values();
        
        if (this.onlyHashed !== false) {
            this.stepHash = $H(this.onlyHashed);
        }
        
        for (var i = 0, l = steps.length; i < l; i ++) {
            if (!steps[i].isRelated(stepObject)) {
                continue;
            }
            
            if (steps[i].isValidHash()) {
                continue;
            }

            if (!stepObject.isLoading() && Object.isFunction(steps[i].load)) {
                reloadCallbacks.push(
                    [steps[i], steps[i].load]
                );
            } else if (!stepObject.isLoading()) {
                this.addToReload(steps[i]);
            }
        }
    },
    /**
     * Invoke array of callbacks
     * 
     * @param Array callbacks
     * @return void
     */
    invokeCallbacks: function (callbacks) {
        callbacks = callbacks.uniq();
        for (var i = 0, l = callbacks.length; i < l; i++) {
           callbacks[i][1].call(callbacks[i][0]);
        }
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
            width: this.getMaskWidth() + 'px',
            height: this.getMaskHeight() + 'px',
            top: 0 + 'px',
            left: 0 + 'px',
            opacity: this.config.maskOpacity || 0.5,
            zIndex: 1000
        });
    },
    /**
     * Displays overlay for whole checkout page
     * 
     * @return void
     */
    showOverlay: function (frontElement) {
        var zIndex = frontElement.getStyle('z-index');
        this.overlay.setStyle({
            width: this.getMaskWidth() + 'px',
            height: this.getMaskHeight() + 'px',
            top: 0 + 'px',
            left: 0 + 'px',
            opacity: 0
        });
        this.overlay.style.zIndex = zIndex - 1;
        centerPosition = this.getCenterElementPosition(frontElement);
        frontElement.setStyle({top: '-600px', left: '0px'});
        new Effect.Parallel([
            new Effect.Appear(frontElement, {sync: true }),
            new Effect.Move(frontElement, {sync: true, x: centerPosition.left, y: centerPosition.top, mode: 'absolute'}),
            new Effect.Appear(this.overlay, {sync: true, from: 0, to: this.config.overlayOpacity || 0.5})
        ]);
    },
    /**
     * Get center position for element in the screen 
     * 
     * @param Element element
     * @return void
     */
    getCenterElementPosition: function (element) {
        var elementDimensions = element.getDimensions();
        var scrollOffsets = document.viewport.getScrollOffsets();
        var positionX = Math.ceil(document.viewport.getWidth() / 2 - elementDimensions.width / 2) + scrollOffsets[0];
        var positionY = Math.ceil(document.viewport.getHeight() / 2 - elementDimensions.height / 2) + scrollOffsets[1];
        
        return {top: positionY, left: positionX};
    },
    /**
     * Returns the height of the mask
     * 
     * @return int
     */
    getMaskHeight: function () {
        var height = $$('body').first().getHeight();

        if (height < document.viewport.getHeight()) {
            height = document.viewport.getHeight();
        }

        return height;
    },
    /**
     * Returns the width of the mask
     * 
     * @return int
     */
    getMaskWidth: function () {
        var width = $$('body').first().getWidth();

        if (width < document.viewport.getWidth()) {
            width = document.viewport.getWidth();
        }
        return width;
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
     * Hides displayed overlay
     * 
     * @return void
     */
    hideOverlay: function (frontElement) {
        new Effect.Parallel([
            new Effect.Fade(frontElement, {sync: true}),
            new Effect.Fade(this.overlay, {sync: true,from: 0.5, to: 0})
        ]);
    },
    /**
     * Add steps to reload sequance object
     * 
     * @param EcomDev.CheckItOut.Step stepObject
     * @retun void
     */
    addToReload: function (stepObject) {
        this.toReload.push(stepObject);
        this.toReload = this.toReload.uniq();
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
            if (this.stepHash.get(step.code)) {
                step.loadedHash = this.stepHash.get(step.code);
            }
            step.showMask();
        }
        
        if (steps.length > 0) {
            var request = new Ajax.Request(this.config.reload, {
                parameters: parameters,
                method: 'POST',
                onComplete: this.onReloadComplete
            });
            request.steps = steps;
        }
    },
    /**
     * Reload complete handler, separated into method 
     * for not using local variables form closure, 
     * was conflicts with more then one request in the same time
     * 
     * @param Ajax.Response response 
     * @return void
     */
    handleRealoadComplete: function (response) {
        var steps = response.request.steps;
        var blocks = response.responseText.evalJSON();
        for (var i=0, l = steps.length; i < l; i ++) {
            try {
                if (blocks[steps[i].code]) {
                    steps[i].update(blocks[steps[i].code]);
                }
            } catch (e) {
                alert(e);
            }

            steps[i].hideMask();
       }
       
       delete response.request;
    },
    /**
     * Compatibility with OPC for load waiting overlay
     *
     * @param String step
     * @return void
     */
    setLoadWaiting: function (step) {
        if (!this.currentLoadWaiting && step == false) {
            return;
        } else if (step && !this.getStep(step)) {
            if (this.currentLoadWaiting) {
                this.currentLoadWaiting.hideMask();
            }

            this.currentLoadWaiting = this.getStep(step);
            this.currentLoadWaiting.showMask();
        } else if (step == false) {
            this.currentLoadWaiting.hideMask();
            this.currentLoadWaiting = false;
        }
    },
    /**
     * Submits the placing of the order,
     * Performs validation before sending data to backend
     * 
     * @return void
     */
    submit: function () {
        if (!this.isValid()) {
            return;
        }
        if (this.getStep('confirm')) {
            this.getStep('confirm').show();
        } else {
            this.forcedSubmit();
        }
    },
    /**
     * Submit order without performing validation 
     * and configuration check 
     * 
     * @return void
     */
    forcedSubmit: function () {
        this.showMask();
        if (this.paymentRedirect) { 
            // Support for payment methods,
            // that have own checkout process
            window.location = this.paymentRedirect;
        } else {
            new Ajax.Request(this.config.save, {
                parameters: this.getParameters(),
                method: 'POST',
                onComplete: this.submitComplete.bind(this) 
            });
        }
    },
    /**
     * Check that checkout forms is valid for submitting
     * 
     * @return Boolean
     */
    isValid: function () {
        return !this.isLoading() && !this.steps.any(function (step) {
            if (step[1].ignoreValidationResult) {
                return false;
            }

            return !step[1].isValid();
        });
    },
    /**
     * Retrieves parameters from all the steps
     * 
     * @return Object
     */
    getParameters: function () {
        var steps = this.steps.values();
        var parameters = {};
        for (var i = 0, l = steps.length; i < l; i ++) {
            Object.extend(parameters, steps[i].getValues());
        }
        
        return parameters;
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
            location.href = result.redirect;
            return;
        }
        if (result.success) {
            window.location=this.config.success;
        }
        else{
            var msg = result.error_messages;
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
     * List of ignored key codes for submitting data
     * 
     * @type Array
     */
    ignoredChangeKeys: [
        Event.KEY_TAB, Event.KEY_LEFT, Event.KEY_UP, 
        Event.KEY_RIGHT, Event.KEY_DOWN, Event.KEY_HOME, 
        Event.KEY_END
    ],
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
     *  Automaticaly submit element values, 
     *  even if they are invalid?
     *  
     *  @type Boolean
     */
    autoSubmitInvalid: false,
    /**
     * Automaticaly validate element values?
     * 
     * @type Boolean
     */
    autoValidate: true,
    /**
     * CSS selector for content block that will be reloaded each time for a step
     * 
     * @type String
     */
    contentCssSelector: '.step-content',
    /**
     * Special flag for checkout step to exclude it
     * from overall validation of checkout
     *
     * @type Boolean
     */
    ignoreValidationResult: false,
    /**
     * Flag for loading data information
     *
     * @type Boolean
     */
    _isLoading: false,
    /**
     * Flag for is in change timeout information
     */
    _isChangeTimeout: false,
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
        this.lastHash = false;
        this.loadedHash = false;
        this.elementValidator = this.validateElement.bind(this);
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
        if (this.checkout.stepHash.get(this.code)) {
            this.loadedHash = this.checkout.stepHash.get(this.code); 
        }
        
        this.mask = this.container.down('.step-loading');
        this.content = this.container.down(this.contentCssSelector);
        if (this.content.down('from')) {
            this.content.down('from').observe('submit', function (evt) {
                    Event.stop(evt)
            })
        }
        this.bindFields();
    },
    /**
     * This method is called after all the steps are added to checkout and it is initialized
     *
     * @void
     */
    initCheckoutAfter: Prototype.emptyFunction,
    /**
     * Check that steps is valid or should it be reloded
     * 
     * @return Boolean
     */
    isValidHash: function () {
        if (!this.checkout.stepHash.get(this.code)) {
            return false;
        }
        
        return this.checkout.stepHash.get(this.code) == this.loadedHash;
    },
    /**
     * Checks step in loading or it's already loaded
     * Set flag if the paramter is given
     * 
     * @param Boolean flag 
     * @return Boolean
     */
    isLoading: function (flag) {
        if (typeof flag == 'boolean') {
            this._isLoading = flag;
            this.checkout.notifyLoading();
        }
        return this._isLoading;
    },

    /**
     * Checks step in in change timeout action
     *
     * @param Boolean flag
     * @return Boolean
     */
    isChangeTimeout: function (flag) {
        if (typeof flag == 'boolean') {
            this._isChangeTimeout = flag;
            this.checkout.notifyLoading();
        }
        return this._isChangeTimeout;
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
        return this.content.select('input', 'select', 'textarea').findAll(this.isNotIgnored);
    },
    /**
     * Checks if element should be ignored for elements select
     *
     * @return Boolean
     */
    isNotIgnored: function (elm) {
        return !elm.hasClassName('ignore-element');
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
        if (arguments.length === 0 || arguments[0] === true) {
              return this.content.select('input', 'select', 'textarea')
                      .map(Validation.validate).all();
        }
        
        return this.content.select('input', 'select', 'textarea')
             .map(this.elementValidator).all();
    },
    /**
     * Validates element value without showing advice of object was not changed
     * 
     * @return Boolean
     */
    validateElement: function (elm) {
        var classNames = $w(elm.className);
        if (elm.wasChanged) {
            Validation.validate(elm);
        }
        return classNames.all(function(value) {
            var test = !Validation.isVisible(elm) || Validation.get(value).test($F(elm), elm);
            return test;
        });
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
        if (evt.type) {
            if (this.ignoredChangeKeys.indexOf(evt.keyCode) !== -1) {
                return;
            }
            
            var element = Event.element(evt);
            element.wasChanged = true;
            
            if (this.autoValidate && ['change', 'click'].indexOf(evt.type) !== -1) {
                Validation.validate.defer(element);
            }
        }
        
        if (this.changeInterval) {
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            this.isChangeTimeout(true);
            this.timeout = setTimeout(this.updater, this.changeInterval);
        } else {
            this.updater();
        }
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
            this.isChangeTimeout(false);
        }
        
        if (!this.autoValidate || this.isValid(false) || this.autoSubmitInvalid) {
            this.lastHash = false;
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
        try {
            var result = response.responseText.evalJSON();
            if (result.stepHash) {
                this.lastHash = result.stepHash;
            }
        } catch (e) {
            alert(e);
        }
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
        var content = this.container.down('.step-content');
        var dimensions = content.getDimensions();
        this.mask.setStyle({
            width: dimensions.width + 'px', 
            height: dimensions.height + 'px',
            top: content.offsetTop + 'px',
            left: content.offsetLeft + 'px',
            opacity: 0.5,
            zIndex: 1000
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
     * Placeholder for reinitiazation of containers 
     * 
     * @return void
     */
    reinitContainer: Prototype.K,
    /**
     * Update content of checkout step content element
     * 
     * @param String htmlContent
     * @return void
     */
    update: function (htmlContent) {
        this.__updateContent(htmlContent);
        this.reinitContainer();
        this.bindFields();
    },
    /**
     * Updates html of content element
     * 
     * @param String htmlContent
     * @void
     */
    __updateContent: function (htmlContent) {
        this.content.update(htmlContent);
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
        this.popUp = container.down('.popup');
        this.popUpTriggerBtn = container.down('.popup-trigger');
        this.popUpCloseBtn = container.down('.popup-close');

    },
    /**
     * Adds handles to login button and close button in popup
     * If there is any error message, it will force popup visibility
     * 
     * @return void
     */
    initCheckout: function ($super) {
        $super();
        if (this.checkout.config && this.checkout.config.popUpOutside) {
            EcomDev.DomReady.add(this.popUp.moveToBody.bind(this.popUp));
        }
        this.popUpTriggerBtn.observe('click', this.togglePopUp.bind(this));
        this.popUpCloseBtn.observe('click', this.hidePopUp.bind(this));
        if (this.popUp.down('.messages')) {
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
    /**
     * Toggles popup visibility on the checkout page
     * 
     * @return void
     */
    togglePopUp: function () {
        this.popUp.visible() ?
            this.hidePopUp():
            this.showPopUp();
    },
    /**
     * Shows login window popup
     * 
     * @return void
     */
    showPopUp: function () {
        this.checkout.showOverlay(this.popUp);
    },
    /**
     * Hides login window popup
     */
    hidePopUp: function () {
        this.checkout.hideOverlay(this.popUp);
    }
});

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
            if (result.field && $(this.code + ':' + result.field)) {
                Validation.ajaxError($(this.code + ':' + result.field), result.message);
            }
        }
        return $super(response);
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
        this.checkOneMethod();
        $super();
        this.content.insert({after: this.errorEl});
        this.additionalLoad();
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
            this.isLoading(false);
            alert(result.message);
            return;
        }
        return $super(response);
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
        
        this.form = form;
        this.saveUrl = saveUrl;
        this.parentConstructor = $super;
        this.onOutsideCheckboxClick = this.handleOutsizeCheckboxClick.bind(this);
        this.errorEl = new Element('input', {type: 'hidden', value: 1, name: 'cio_payment_method_error', id: 'payment_method_error', 'class' : 'required-entry ajax-error'});
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
            ('<script type="text/javascript">' + scriptText + "</script>").evalScripts();
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
        if (this.parentConstructor) {
            this.parentConstructor(this.findContainer(this.form), this.saveUrl);
            this.addRelation(['billing', 'shipping_method']);
            this.parentConstructor = false;
        }
        this.beforeInit();
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
    handleOutsizeCheckboxClick: function (evt) {
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
        this.loadedHash = false;
        // Initial payment methods load
        if (this.checkout.getStep('shipping_method')) {
            this.checkout.reloadSteps(['shipping_method']);
        } else {
            this.checkout.reloadSteps(['billing']);
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

        if (!form || form.select('select','input', 'textarea').length == 0) {
            this.handleChange({});
        }

        if (this.currentMethod != method && this.checkout) {
            this.checkout.paymentRedirect = false;
        }

        this.currentMethod = method;
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

        // Support of centinel features
        if (result.update_section
            && this.checkout.getStep(result.update_section.name)) {
            var stepToUpdate = this.checkout.getStep(result.update_section.name);
            if (Object.isFunction(stepToUpdate.updateContent)) {
                stepToUpdate.updateContent(result.update_section.html);
                stepToUpdate.loadedHash = result.stepHash[stepToUpdate.code];
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
                Validation.ajaxError(this.errorEl, result.error);
                this.isLoading(false);
            }
            return;
        }
        return $super(response);
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
    initialize: function($super, loadUrl, updateElement, saveUrl, successUrl, agreementsForm, changeQtyTemplate, removeTemplate, changeQtyUrl, removeUrl){
        this.successUrl = successUrl;
        this.agreementsFormId = agreementsForm;
        if ($(changeQtyTemplate)) {
           this.changeQtyTemplate = new Template($(changeQtyTemplate).innerHTML);
        } 
        if ($(removeTemplate)) {
           this.removeTemplate = new Template($(removeTemplate).innerHTML);
        }
        
        this.changeQtyUrl = changeQtyUrl;
        this.removeUrl = removeUrl;
        this.loadUrl = loadUrl;
        this.onLoadComplete = this.loadComplete.bind(this);
        this.updateElement = $(updateElement);
        var container = this.findContainer(this.updateElement);
        $super(container, saveUrl);
        this.addRelation(['billing', 'payment', 'shipping', 'shipping_method']);
    },
    /**
     * Creates item updaters objects depending on template exsitance
     * 
     * @return void
     */
    updateItems: function (itemsInfo) {
        this.itemsInfo = itemsInfo;
        if (this.changeQtyTemplate) {
            new ChangeItemQty(this, this.changeQtyTemplate, this.changeQtyUrl);
        }
        
        if (this.removeTemplate) {
            new RemoveItem(this, this.removeTemplate, this.removeUrl);
        }
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
        if (!this.checkout.isLoading() || !this.isValidHash() || !this.wasLoaded) {
            this.wasLoaded = true;
            var params = (
                this.checkout.getStep('payment') ?
                    this.checkout.getStep('payment').getValues() :
                    {}
            );
            this.isLoading(true);
            new Ajax.Request(this.loadUrl, {
                method: 'POST',
                parameters: params,
                onComplete: this.onLoadComplete,
                onFailure: this.checkout.ajaxFailure
            });
        }
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
        this.loadedHash = this.checkout.stepHash.get(this.code);
        this.updateContent(response.responseText);
        this.agreementsForm = $(this.agreementsFormId);
        this.isLoading(false);
    },
    /**
     * Updates content of the step
     *
     * @param content
     */
    updateContent: function (content) {
        try {
            var values = Form.serializeElements(this.updateElement.select('input', 'select', 'textarea'), true);
            this.updateElement.update(content);
            var names = Object.keys(values);
            for (var i = 0, l = names.length; i < l; i ++) {
                if (Object.isArray(values[names[i]])) {
                    this.updateElement.select('input[name="' + names[i] + '"]')
                        .each(function (item) { item.checked = values.indexOf(item.value) !== -1} );
                } else {
                    var element = this.updateElement.down('*[name="' + names[i] + '"]');
                    if (element && element.type == 'checkbox') {
                        element.checked = true;
                    } else if (element) {
                        element.value = values[names[i]];
                    }
                }
            }
        } catch (e) {
            if (window.console) {
                window.console.log(e);
                window.console.log(response.responseText);
            }
        }
    },
    /**
     * Emulation of OnePageCheckout save method
     * 
     * @return void
     */
    save: function () {
        this.checkout.submit();
    }
});

/**
 * Base class for actions in review step. 
 * 
 * Uses initializeRow method for rendering item control elements
 * 
 */
var ItemAction = Class.create({
    /**
     * Constructor
     * 
     * @param Review reviewStep
     * @param Template template prototype template
     * @param String url url for submisssion of data
     */
    initialize: function (reviewStep, template, url) {
        this.reviewStep = reviewStep;
        this.template = template;
        this.url = url;
        this.onAction = this.handleAction.bind(this);
        this.onActionDelay = this.handleActionDelay.bind(this);
        this.onComplete = this.handleComplete.bind(this);
        this.checkout = this.reviewStep.checkout;
        this.table = this.reviewStep.container.down('.data-table');
        this.initializeLayout();
    },
    /**
     * Performs update with delay on half a second
     * 
     * @param Event evt
     * @return void
     */
    handleActionDelay: function (evt) {
        this.onAction.delay(0.5, evt);
    },
    /**
     * Finds row element of item in the table 
     * by analyzing event data
     * 
     * @param Event evt
     * @return Element|Boolean
     */
    findRow: function (evt) {
        return Event.element(evt).up('tr');
    },
    /**
     * Performs rednering of the item action from 
     * its templates
     * 
     * @return void
     */
    initializeLayout: function () {
        var itemsInfo = this.reviewStep.itemsInfo;
        var itemRows = this.table.select('tbody > tr');
        for (var i = 0, l = itemsInfo.length; i < l; i++) {
            var row = itemRows[i];
            row.info = itemsInfo[i];
            if (row) {
                this.initializeRow(row);
            }
        }
    },
    /**
     * Handler for completion of item 
     * action update request
     * 
     * @param Ajax.Response
     * @return void
     */
    handleComplete: function (response) {
        this.reviewStep.hideMask();
        var result = response.responseText.evalJSON();
        if (result.success) {
            this.checkout.onlyHashed = (result.stepHash ? result.stepHash : false);
            this.checkout.reloadSteps(['billing', 'shipping']);
            this.checkout.onlyHashed = false;
        } else {
            alert(result.error);
        }
    },
    /**
     * Performs update request for saving item action
     * 
     * @param Object parameters
     * @return void
     */
    ajaxRequest: function (parameters) {
        new Ajax.Request(this.url, {
            parameters: parameters,
            onComplete: this.onComplete,
            onFailure: this.checkout.ajaxFailure
        });
        this.reviewStep.showMask();
    }
});

/**
 * Item Action for changin product qty on review step
 * 
 * 
 */
var ChangeItemQty = Class.create(ItemAction, {
	/**
	 * Draws input element instead of text qty representation 
	 * if qty change is allowed for product
	 * 
	 * @param Object row
	 * @return void
	 */
    initializeRow: function (row) {
        if (!row.info.allow_change_qty) {
           return;
        }
        var html = this.template.evaluate(row.info);
        var element = row.down('td.a-center').update('').insert(html);
        var input = element.down('input'); 
        input.value = row.info.qty;
        input.observe('keyup', this.onActionDelay);
        input.observe('change', this.onAction);
    },
    /**
     * Observes change in input element and 
     * performs actions
     * 
     * @param Event evt
     * @return void
     */
    handleAction: function (evt) {
        if (!this.validateValue(evt, true)) {
            return;
        } 
        var row = this.findRow(evt); 
        var input = row.down('input.qty');
        
        this.ajaxRequest({
            item_id: row.info.item_id, 
            qty: input.value
        });
    },
    /**
     * Validates value for matching decimal in qty field.
     * If autoFix is specified then it will return old value 
     * if specified is invalid
     * 
     * @param Event evt
     * @param Boolean autoFix
     * @return Boolean
     */
    validateValue: function (evt, autoFix) {
        var row = this.findRow(evt); 
        var input = row.down('input.qty');
        input.value = input.value.replace(/[^0-9\.]/g, '');
        var parsed = parseFloat(input.value);
        if (isNaN(parsed) || parsed <= 0) {
           if (autoFix) {
              input.value = row.info.qty;
           }
           return false;
        }
        return true;
    },
    /**
     * Performs delayed update only if value is valid
     *
     * @param Function $super parent method
     * @param Event evt
     * @return void
     */
    handleActionDelay: function ($super, evt) {
        if (!this.validateValue(evt, false)) {
            return;
        }
        $super(evt);
    }
});

/**
 * Item action for removing products from order
 * 
 */
var RemoveItem = Class.create(ItemAction, {
    /**
     * Renders "Delete Icon" only if item is allowed for removal,
     * There will be no element if it is a last item in the order
     * 
     * @param Object row
     * @return void
     */
    initializeRow: function (row) {
        if (!row.info.allow_remove) {
           if (this.initedHeaders) {
               row.insert(new Element('td', {'class':'a-center'})).update('&nbsp;');
           }
           return;
        }
        this.initHeaders();
        var html = this.template.evaluate(row.info);
        var td = new Element('td', {'class':'a-center'});
        td.update(html);
        var element = row.insert(td);
        var link = element.down('a'); 
        link.observe('click', this.onAction);
    },
    /**
     * Adds additional header for product table on review step 
     * If any item is allowed for removal
     * 
     * @return void
     */
    initHeaders: function () {
        if (!this.initedHeaders) {
            this.initedHeaders = true;
            if (this.table.down('colgroup')) {
                this.table.down('colgroup').insert(new Element('col', {width: '1'}));
            }
            var headers = this.table.select('thead tr');
            var rowSpan = headers.length;
            headers.first().insert(new Element('th', {rowspan: rowSpan}).update('&nbsp;'));
            var totals = this.table.select('tfoot tr');
            for (var i = 0, l = totals.length; i < l; i ++) {
                totals[i].insert(new Element('td').update('&nbsp;'));
            }
        }
    },
    /**
     * Performs removal of item by ajax request 
     * 
     * @param Event evt
     * @return void
     */
    handleAction: function (evt) {
        Event.stop(evt);
        var row = this.findRow(evt);
        this.ajaxRequest({item_id: row.info.item_id})
    },
    /**
     * Handler for completion of item 
     * action update request
     * 
     * @param Ajax.Response
     * @return void
     */
    handleComplete: function ($super, response) {
        $super(response);
        if (this.checkout.getStep('shipping_method')) {
            this.checkout.getStep('shipping_method').additionalLoad();
        }
    }
});

/**
 * Additional checkout step for confirming order
 * 
 * Shows a popup window with order details
 * 
 */
var ConfirmPopUp = Class.create(EcomDev.CheckItOut.Step, {
    /**
     * Checkout step unique code
     * 
     * @type String
     */
    code: 'confirm',
    // No autosumit on element change
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
    initialize: function($super, windowElement, loadUrl){
        this.loadUrl = loadUrl;
        this.onLoadComplete = this.loadComplete.bind(this);
        this.windowElement = $(windowElement);
        var container = this.findContainer(this.windowElement.down('.step-content'));
        $super(container, '');

        this.onLoad = this.loadComplete.bind(this);
        this.onConfirm = this.save.bind(this);
        this.onCancel = this.cancel.bind(this);

    },
    /**
     * Moves confirmation window outside of the container
     *
     * @param $super
     */
    initCheckout: function ($super) {
        $super();
        if (this.checkout.config && this.checkout.config.popUpOutside) {
            EcomDev.DomReady.add(this.container.moveToBody.bind(this.container));
        }
    },
    /**
     * It is always valid step :)
     * 
     * @return Boolean
     */
    isValid: function () {
        return true;
    },
    /**
     * Starts step data loading, 
     * displays mask over the page
     * 
     *  @return void
     */
    show: function () {
        this.checkout.showMask();

        this.load();
    },
    /**
     * Perofroms ajax request for retriving step html view
     * 
     * @return void
     */
    load: function () {
        new Ajax.Updater(this.container.down('.step-content'), this.loadUrl, {
            onComplete: this.onLoad,
            parameters: this.checkout.getParameters(),
            method: 'POST'
        });
    },
    /**
     * Handler for completed order configration view request 
     * 
     * @return void
     */
    loadComplete: function (response) {
        this.checkout.hideMask();
        this.checkout.showOverlay(this.windowElement);
        this.container.down('button.confirm').observe('click', this.onConfirm);
        this.container.down('button.cancel').observe('click', this.onCancel);
    },
    /**
     * Saves order details 
     * 
     * @return void
     */
    save: function () {
        this.checkout.hideOverlay(this.windowElement);
        this.checkout.showMask();
        this.checkout.forcedSubmit();
    },
    cancel: function () {
        this.checkout.hideOverlay(this.windowElement);
    }
});
