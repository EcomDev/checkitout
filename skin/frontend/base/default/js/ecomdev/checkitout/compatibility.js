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

// Ebizmarts SagePay Suite compatibility
if (typeof window.EbizmartsSagePaySuite !== 'undefined') {
    Event.observe(window, 'load', function () {
        checkout.config.save = review.saveUrl;
        review.nextStep = checkout.submitComplete;
        checkout.submitComplete = function (response) {
            checkout.hideMask();
            review.onSave(response);
        };
    });
}

// Change prototype scripts eval scope to global 
String.prototype.evalScripts = function () {
  return this.extractScripts().map(function(script) { 
     return window.globalEval(script);
  });
};

// Thanks to http://perfectionkills.com/global-eval-what-are-the-options/ for information
// Fix for global scoping of evalScripts
var globalEval = (function() {
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
                    $(this.iframeId).up().insert({bottom: window.currentDirectPostForm});
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
                    $$('body').first().insert({bottom: form});
                    form.absolutize();
                    var placeholder = new Element('li');

                    currentPaymentForm.insert({bottom: placeholder});

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