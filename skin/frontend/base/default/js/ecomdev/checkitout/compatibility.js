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

// Compatibility with payment methods that have onSave method
// fix for save order method
if(typeof EbizmartsSagePaySuite != 'undefined') {
    EbizmartsSagePaySuite.Checkout.prototype.reviewSave = function(transport){

        if((typeof transport) == 'undefined'){
            var transport = {};
        }

        //OSC\\
        if((typeof transport.responseText) == 'undefined' && $('onestepcheckout-form')){

            if(this.isFormPaymentMethod()){
                new Ajax.Request(SuiteConfig.getConfig('global', 'sgps_saveorder_url'),{
                    method:"post",
                    parameters: Form.serialize($('onestepcheckout-form')),
                    onSuccess:function(f){
                        var d = f.responseText.evalJSON();
                        if(d.response_status == 'ERROR'){
                            alert(d.response_status_detail);
                            this.resetOscLoading();
                            return;
                        }

                        setLocation(SuiteConfig.getConfig('form','url'));
                    }
                });
                return;
            }

            if((this.isDirectPaymentMethod() || this.isServerPaymentMethod()) && parseInt(SuiteConfig.getConfig('global','token_enabled')) === 1){
                if((typeof transport.tokenSuccess) == 'undefined'){
                    this.setPaymentMethod();

                    if(!this.isDirectTokenTransaction() && !this.isServerTokenTransaction() && (($('remembertoken-sagepaydirectpro') && $('remembertoken-sagepaydirectpro').checked === true) || ($('remembertoken-sagepayserver') && $('remembertoken-sagepayserver').checked === true))){
                        return;
                    }
                }
            }

            if(parseInt($$('div.onestepcheckout-place-order-loading').length) || (typeof transport.tokenSuccess != 'undefined' && true === transport.tokenSuccess)){

                if(Ajax.activeRequestCount > 1 && (typeof transport.tokenSuccess) == 'undefined'){
                    return;
                }
                var slPayM = this.getPaymentMethod();


                if(slPayM == this.servercode || slPayM == this.directcode){
                    new Ajax.Request(SuiteConfig.getConfig('global', 'sgps_saveorder_url'),{
                        method:"post",
                        parameters: Form.serialize($('onestepcheckout-form')),
                        onSuccess:function(f){
                            this.reviewSave(f);
                            transport.element().removeClassName('grey').addClassName('orange');
                            $$('div.onestepcheckout-place-order-loading').invoke('hide');
                        }.bind(this)
                    });
                    return;
                }else{
                    $('onestepcheckout-form')._submit();
                    return;
                }

            }else{
                return;
            }
        //OSC\\
        }else if((typeof transport.responseText) == 'undefined' && this.getConfig('msform')){
            var ps = $H({
                'payment[method]': 'sagepayserver'
            });

            if($('sagepay_server_token_cc_id')){
                ps.set('payment[sagepay_token_cc_id]', $('sagepay_server_token_cc_id').getValue());
            }

            new Ajax.Request(SuiteConfig.getConfig('global', 'sgps_saveorder_url'),{
                method:"post",
                parameters: ps,
                onSuccess:function(f){
                    this.reviewSave(f);
                }.bind(this)
            });
            return;

        }else{
            try{
                var response = this.evalTransport(transport);
            }catch(notv){
                suiteLogError(notv);
            }
        }

        if((typeof response.response_status != 'undefined') && response.response_status != 'OK' && response.response_status != 'threed' && response.response_status != 'paypal_redirect'){

            this.resetOscLoading();

            this.growlWarn("An error ocurred with Sage Pay:\n" + response.response_status_detail.toString());
            return;
        }

        if(response.response_status == 'paypal_redirect'){
            setLocation(response.redirect);
            return;
        }

        if(this.getConfig('osc') && response.success && response.response_status == 'OK' && (typeof response.next_url == 'undefined')){
            setLocation(SuiteConfig.getConfig('global','onepage_success_url'));
            return;
        }
        //@autor Alexandr Lykhouzov <a.lykhouzov@ecomdev.org>
        //fix to enable redirect to success page after success order placement
        if(response.success && (typeof response.next_url == 'undefined')){
            setLocation(SuiteConfig.getConfig('global','onepage_success_url'));
            return;
        }

        if(!response.redirect || !response.success) {
            this.getConfig('review').nextStep(transport);
            return;
        }

        if(this.isServerPaymentMethod()){

            $('sagepayserver-dummy-link').writeAttribute('href', response.redirect);

            var rbButtons = $('review-buttons-container');

            var lcont = new Element('div',{
                className: 'lcontainer'
            });
            var heit = parseInt(SuiteConfig.getConfig('server','iframe_height'));
            if(Prototype.Browser.IE){
                heit = heit-65;
            }

            var wtype = SuiteConfig.getConfig('server','payment_iframe_position').toString();
            if(wtype == 'modal'){

                var wm = new Control.Modal('sagepayserver-dummy-link',{
                    className: 'modal',
                    iframe: true,
                    closeOnClick: false,
                    insertRemoteContentAt: lcont,
                    height: SuiteConfig.getConfig('server','iframe_height'),
                    width: SuiteConfig.getConfig('server','iframe_width'),
                    fade: true,
                    afterOpen: function(){
                        if(rbButtons){
                            rbButtons.addClassName('disabled');
                        }
                    },
                    afterClose: function(){
                        if(rbButtons){
                            rbButtons.removeClassName('disabled');
                        }
                    }
                });
                wm.container.insert(lcont);
                wm.container.down().setStyle({
                    'height':heit.toString() + 'px'
                    });
                wm.container.down().insert(this.getServerSecuredImage());
                wm.open();

            }else if(wtype == 'incheckout'){

                var iframeId = 'sagepaysuite-server-incheckout-iframe';
                var paymentIframe = new Element('iframe', {
                    'src': response.redirect, 
                    'id': iframeId
                });

                if(this.getConfig('osc')){
                    var placeBtn = $('onestepcheckout-place-order');

                    placeBtn.hide();

                    $('onestepcheckout-form').insert( {
                        after:paymentIframe
                    } );
                    $(iframeId).scrollTo();

                }else{

                    if( (typeof $('checkout-review-submit')) == 'undefined' ){
                        var btnsHtml  = $$('div.content.button-set').first();
                    }else{
                        var btnsHtml  = $('checkout-review-submit');
                    }

                    btnsHtml.hide();
                    btnsHtml.insert( {
                        after:paymentIframe
                    } );

                }

            }

        }else if(this.isDirectPaymentMethod() && (typeof response.response_status != 'undefined') && response.response_status == 'threed'){

            $('sagepaydirectpro-dummy-link').writeAttribute('href', response.redirect);

            var lcontdtd = new Element('div',{
                className: 'lcontainer'
            });
            var dtd = new Control.Modal('sagepaydirectpro-dummy-link',{
                className: 'modal sagepaymodal',
                closeOnClick: false,
                insertRemoteContentAt: lcontdtd,
                iframe: true,
                height: SuiteConfig.getConfig('direct','threed_iframe_height'),
                width: SuiteConfig.getConfig('direct','threed_iframe_width'),
                fade: true,
                afterOpen: function(){

                    if(true === Prototype.Browser.IE){
                        var ie_version = parseFloat(navigator.appVersion.split("MSIE")[1]);
                        if(ie_version<8){
                            return;
                        }
                    }

                    try{
                        var daiv = this.container;

                        if($$('.sagepaymodal').length > 1){
                            $$('.sagepaymodal').each(function(elem){
                                if(elem.visible()){
                                    daiv = elem;
                                    throw $break;
                                }
                            });
                        }

                        daiv.down().down('iframe').insert({
                            before:new Element('div', {
                                'id':'sage-pay-direct-ddada',
                                'style':'background:#FFF'
                            }).update(
                                SuiteConfig.getConfig('direct','threed_after').toString() + SuiteConfig.getConfig('direct','threed_before').toString())
                            });

                    }catch(er){}

                    if(false === Prototype.Browser.IE){
                        daiv.down().down('iframe').setStyle({
                            'height':(parseInt(daiv.down().getHeight())-60)+'px'
                            });
                        daiv.setStyle({
                            'height':(parseInt(daiv.down().getHeight())+57)+'px'
                            });
                    }else{
                        daiv.down().down('iframe').setStyle({
                            'height':(parseInt(daiv.down().getHeight())+116)+'px'
                            });
                    }

                },
                afterClose: function(){
                    if($('sage-pay-direct-ddada')){
                        $('sage-pay-direct-ddada').remove();
                    }
                    $('sagepaydirectpro-dummy-link').writeAttribute('href', '');
                }
            });
            dtd.container.insert(lcontdtd);
            dtd.open();

        }else if(this.isDirectPaymentMethod()){
            new Ajax.Request(SuiteConfig.getConfig('direct','sgps_registertrn_url'),{
                onSuccess:function(f){

                    try{

                        var d=f.responseText.evalJSON();

                        if(d.response_status=="INVALID"||d.response_status=="MALFORMED"||d.response_status=="ERROR"||d.response_status=="FAIL"){
                            this.getConfig('checkout').accordion.openSection('opc-payment');
                            this.growlWarn("An error ocurred with Sage Pay Direct:\n" + d.response_status_detail.toString());
                        }else if(d.response_status == 'threed'){
                            $('sagepaydirectpro-dummy-link').writeAttribute('href', d.url);
                        }

                    }catch(alfaEr){
                        this.growlError(f.responseText.toString());
                    }

                }.bind(this)
            });
        }else{
            this.getConfig('review').nextStep(transport);
            return;
        }
    }
}
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
                    $(this.iframeId).up().insert({
                        bottom: window.currentDirectPostForm
                    });
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
                    $$('body').first().insert({
                        bottom: form
                    });
                    form.absolutize();
                    var placeholder = new Element('li');

                    currentPaymentForm.insert({
                        bottom: placeholder
                    });

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