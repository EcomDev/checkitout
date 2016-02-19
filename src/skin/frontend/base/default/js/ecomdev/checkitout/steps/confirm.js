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
