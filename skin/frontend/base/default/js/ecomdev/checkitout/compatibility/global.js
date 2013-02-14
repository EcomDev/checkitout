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

// Change prototype scripts eval scope to global
String.prototype.evalScripts = function () {
    return this.extractScripts().map(function(script) {
        return window.globalEval(script);
    });
};

// Thanks to http://perfectionkills.com/global-eval-what-are-the-options/ for information
// Fix for global scoping of evalScripts
var globalEval = (function() {            http://pastebin.com/cfEGW4LV
    var isIndirectEvalGlobal = (function(original, Object) {
        try {
            // Does `Object` resolve to a local variable, or to a global, built-in `Object`,
            // reference to which we passed as a first argument?
            return (1,eval)('Object') === original;
        }
        catch(err) {
            // if indirect eval errors out (as allowed per ES3), then just bail out with `false`
            return false;
        }
    })(Object, 123);

    if (isIndirectEvalGlobal) {
        // if indirect eval executes code globally, use it
        return function(expression) {
            return (1,eval)(expression);
        };
    }
    else if (typeof window.execScript !== 'undefined') {
        // if `window.execScript exists`, use it
        return function(expression) {
            return window.execScript(expression);
        };
    }
// otherwise, globalEval is `undefined` since nothing is returned
})();

// Support for payment methods that override review.onSave method of OPC
Event.observe(window, 'load', function () {
    if (window.review && Object.isFunction(review.onSave)) {
        if (checkout.config.save !== review.saveUrl) {
            checkout.config.save = review.saveUrl;
        }

        review.nextStep = checkout.submitComplete;
        checkout.submitComplete = function (response) {
            checkout.hideMask();
            review.onSave(response);
        };
    }
});