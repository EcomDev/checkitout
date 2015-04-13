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
 * @copyright  Copyright (c) 2015 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Various utilities used by extension
 *
 */

/**
 * Object replacer
 *
 * Used to replace existent instances of objects
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

/**
 * Introduced new method for elements, that moves it to body
 *
 */
Element.addMethods({
    moveToBody: function (element) {
        Element.remove(element);
        Element.insert(document.body, element);
    }
});

/**
 * Utility to execute actions on dom:loaded event
 *
 *
 */
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
