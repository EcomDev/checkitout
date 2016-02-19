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
        if (this.checkout.method !== method) {
            this.checkout.method = method;
            Element.hide('register-customer-password');
            new Ajax.Request(
                this.saveUrl,
                {method: 'post', onFailure: this.checkout.onFailure, parameters: {method:this.checkout.method}}
            );
        }
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
