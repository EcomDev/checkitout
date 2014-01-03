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
     * Indicates availability of custom load
     *
     * @type Boolean
     */
    canHaveCustomLoad: true,

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
     * List of fields, update of which will force auto-submit invalid data
     * 
     * @type Array|Boolean
     */
    autoSubmitInvalidFields: false,
    
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
        this.lastHtml = false;
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
            if (this.checkout) {
                this.checkout.notifyLoading();
            }
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
            if (this.checkout) {
                this.checkout.notifyLoading();
            }
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
     * Validates element value without showing advice of object if it was not changed
     *
     * @return Boolean
     */
    validateElement: function (elm) {
        var classNames = $w(elm.className);
        if (elm.wasChanged) {
            Validation.isOnChange = true;
            var result = Validation.validate(elm);
            Validation.isOnChange = false;
            return result;
        }
        return classNames.all(function(className) {
            var validatorFunction = Validation.get(className);
            Validation.isOnChange = true;
            var result = !Validation.isVisible(elm) || validatorFunction.test(elm.value, elm);
            Validation.isOnChange = false;
            return result;
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
            this.lastChangedElement = element;
            if (element.type && element.type === 'checkbox') {
                this.lastChangedElementValue = element.checked;
            } else {
                this.lastChangedElementValue = element.value;
            }

            if (this.autoValidate && ['change', 'click'].indexOf(evt.type) !== -1) {
                Validation.isOnChange = true;
                Validation.validate(element);
                Validation.isOnChange = false;
            }
        } else {
            this.lastChangedElement = false;
            this.lastChangedElementValue = false;
        }

        if (this.changeInterval) {
            if (evt.type && this.checkLastSubmitted()) {
                return;
            }

            if (this.timeout) {
                clearTimeout(this.timeout);
            }

            this.isChangeTimeout(true);
            this.timeout = setTimeout(this.updater, this.changeInterval);
        } else {
            this.updater();
        }
    },
    checkLastSubmitted: function () {
        return this.lastChangedElement && this.lastSubmittedElement
            && this.lastSubmittedElement === this.lastChangedElement
            && this.lastSubmittedElementValue === this.lastChangedElementValue;
    },
    /**
     * Submits checkout step form values
     *
     * @return void
     */
    submit: function () {
        this._submit(true);
    },
    _submit: function (checkCompatibleMethod) {
        if (this.timeout) {
            clearInterval(this.timeout);
            this.timeout = undefined;
            this.isChangeTimeout(false);
        }

        if (this.isLoading()) {
            return;
        }

        if (this.lastChangedElement) {
            if (this.checkLastSubmitted()) {
                return;
            }
            this.lastSubmittedElement = this.lastChangedElement;
            this.lastSubmittedElementValue = this.lastChangedElementValue;
        } else {
            this.lastSubmittedElement = false;
            this.lastSubmittedElementValue = false;
        }

        if (checkCompatibleMethod && !this.save._isOriginal) {
            // Do custom save operation in case if there are redifinition of any of the methods
            return this.save();
        }

        var isAutosubmitInvalid = this.autoSubmitInvalid;
        if (isAutosubmitInvalid && Object.isArray(this.autoSubmitInvalidFields)) {
            isAutosubmitInvalid = this.lastChangedElement 
                                    && this.lastChangedElement.identify()
                                    && this.autoSubmitInvalidFields.include(this.lastChangedElement.identify())
        }
        
        if (!this.autoValidate || this.isValid(false) || isAutosubmitInvalid) {
            this.lastHash = false;
            this.lastHtml = false;
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
     * Validate compatibility method
     *
     *
     * @returns {Boolean}
     */
    validate: function () {
        return this.isValid();
    },
    /**
     *
     */
    save: function () {
        this._submit(false);
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
                this.lastHtml = result.stepHtml;
            }
        } catch (e) {
            if (window.console) {
                window.console.log(e);
                window.console.log(response.responseText);
            }
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

// Keep save method reference to make sure it is an original instance of it
EcomDev.CheckItOut.Step.prototype.save._isOriginal = true;