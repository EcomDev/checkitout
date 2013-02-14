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
        this.update();
    },
    update: function () {
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
        if (this.currentElement !== Event.element(evt)) {
            this.currentElement = Event.element(evt);
        } else if (this.timeout) {
            clearTimeout(this.timeout);
        }

        this.timeout = setTimeout(
            this.handleAction.bind(this, evt),
            1000
        );
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
            this.reset();
        }
    },
    reset: Prototype.K,
    requestChecksum: function (parameters) {
        if (Object.isString(parameters)) {
            return parameters;
        }

        return Object.toJSON(parameters);
    },
    /**
     * Performs update request for saving item action
     *
     * @param Object parameters
     * @return void
     */
    ajaxRequest: function (parameters) {
        if (this.checksum && this.requestChecksum(parameters) == this.checksum) {
            return;
        }

        this.checksum = this.requestChecksum(parameters);
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
        row.input = input;
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
        this.currentRow = row;
        this.ajaxRequest({
            item_id: row.info.item_id,
            qty: input.value
        });
    },
    reset: function () {
        if (this.currentRow) {
            var input = this.currentRow.down('input.qty');
            input.value = this.currentRow.info.qty;
        }
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
            if (this.isInitedHead()) {
                row.insert(new Element('td', {'class':'a-center'})).update('&nbsp;');
            }
            return;
        }
        if (!row.down('.remove-qty')) {
            this.initHeaders();
            var html = this.template.evaluate(row.info);
            var td = new Element('td', {'class':'a-center remove-qty'});
            td.update(html);
            var element = row.insert(td);
            var link = element.down('a');
            link.observe('click', this.onAction);
        }
    },
    isInitedHead: function () {
        return !!this.table.down('thead tr th.remove-item-head');
    },
    /**
     * Adds additional header for product table on review step
     * If any item is allowed for removal
     *
     * @return void
     */
    initHeaders: function () {
        if (!this.isInitedHead()) {
            if (this.table.down('colgroup')) {
                this.table.down('colgroup').insert(new Element('col', {width: '1'}));
            }
            var headers = this.table.select('thead tr');
            var rowSpan = headers.length;
            headers.first().insert(new Element('th', {rowspan: rowSpan, 'class': 'remove-item-head'}).update('&nbsp;'));
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
        var result = response.responseText.evalJSON();
        if(typeof(result.redirect) != "undefined"){
            window.location.href = result.redirect;
            return;
        }
        $super(response);
        if (this.checkout.getStep('shipping_method')) {
            this.checkout.getStep('shipping_method').additionalLoad();
        }
    }
});
