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

    /**
     * Indicates availability of custom load
     *
     * @type Boolean
     */
    canHaveCustomLoad: false,

    autoSubmit: false,
    /**
     * Order review checkout step initalization
     *
     * @param {Function} $super parent constructor
     * @param {String} loadUrl
     * @param {String} updateElement
     * @param {String} saveUrl
     * @param {String} successUrl [used only in onepagecheckout]
     * @param {Element} agreementsForm [used only in onepagecheckout]
     * @param {String} changeQtyTemplate
     * @param {String} changeQtyUrl
     * @param {String} removeTemplate
     * @param {String} removeUrl
     */
    initialize: function($super, loadUrl, updateElement, saveUrl, successUrl,
                         agreementsForm, changeQtyTemplate,
                         removeTemplate, changeQtyUrl, removeUrl){

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
            if (!this.changeQty) {
                this.changeQty = new ChangeItemQty(this, this.changeQtyTemplate, this.changeQtyUrl);
            } else {
                this.changeQty.update();
            }
        }

        if (this.removeTemplate) {
            if (!this.remove) {
                this.remove = new RemoveItem(this, this.removeTemplate, this.removeUrl);
            } else {
                this.remove.update();
            }
        }
    },
    /**
     * Loads order review block after checkout initialization
     *
     * @param {Function} $super parent method
     * @return void
     */
    initCheckout: function ($super) {
        $super();

        if (!this.checkout.getStep('payment')
            || !this.checkout.getStep('payment').noReviewLoad) {
            this.update(this.checkout.preloadedHtml.review);
        } else {
            this.showMask();
            this.loadedHash = false;
        }
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
     * @param {Ajax.Response} response
     * @return void
     */
    loadComplete: function (response) {
        this.update(response.responseText);
        if (this.checkout) {
            this.checkout.notifyLoading();
        }
    },
    /**
     * Update content of review step
     *
     * @param {String} htmlContent
     * @return void
     */
    update: function (htmlContent) {
        this.hideMask();
        this.loadedHash = this.checkout.stepHash.get(this.code);
        this.wasLoaded = true;
        this.updateContent(htmlContent);
        this.agreementsForm = $(this.agreementsFormId);
        this.isLoading(false);
    },
    /**
     * Updates content of the step
     *
     * @param {String} content
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
