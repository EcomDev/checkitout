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
 * Base JavaScript classes of checkitout
 */

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
         * Current checkout method
         *
         * @type {string}
         */
        this.method = this.config.method;

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
         * To reload object sequence
         *
         * @type Array
         */
        this.toReload = [];

        this.stepHash = $H(this.config.stepHash);
        this.stepHtml = false;
        this.preloadedHtml = this.config.preloadedHtml;

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
        this.stepHtml = (stepObject.lastHtml ? stepObject.lastHtml : false);
        this.collectReload(stepObject, reloadCallbacks);
        this.onlyHashed = false;
        this.reload();
        this.invokeCallbacks(reloadCallbacks);
        this.stepHtml = false;
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

            if (this.stepHtml && this.stepHtml[steps[i].code] !== null) {
                continue;
            }

            if (!stepObject.isLoading()
                && steps[i].canHaveCustomLoad
                && Object.isFunction(steps[i].load)) {
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

        if (this.stepHtml) {
            var stepCodes = Object.keys(this.stepHtml);
            for (var i=0, l=stepCodes.length; i<l; i++) {
                if (this.getStep(stepCodes[i])
                    && this.stepHtml[stepCodes[i]] !== null
                    && this.stepHtml[stepCodes[i]] !== false
                    && this.stepHash.get(stepCodes[i]) !== this.getStep(stepCodes[i]).loadedHash) {
                    this.getStep(stepCodes[i]).update(this.stepHtml[stepCodes[i]]);
                    this.getStep(stepCodes[i]).loadedHash = this.stepHash.get(stepCodes[i]);
                }
            }
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
        if (this.isLoading() || this.isChangeTimeout()) {
            return false;
        }
        // Run full validation of all steps
        var steps = this.steps.values();
        var result = true;
        for (var i=0,l=steps.length; i < l; i++) {
            if (steps[i].ignoreValidationResult) {
                continue;
            }

            result = steps[i].isValid() && result;
        }

        return result;
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