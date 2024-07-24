/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

define([
    'jquery',
    'uiComponent',
    'mage/translate'
], function ($, Component, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            templates: {
                item: '<div class="json__item"><div class="json__key">%KEY%</div><div class="json__value json__value--%TYPE%">%VALUE%</div></div>',
                itemCollapsible: '<label class="json__item json__item--collapsible"><input type="checkbox" class="json__toggle"/><div class="json__key">%KEY%</div><div class="json__value json__value--type-%TYPE%">%VALUE%</div>%CHILDREN%</label>',
                itemCollapsibleOpen: '<label class="json__item json__item--collapsible"><input type="checkbox" checked class="json__toggle"/><div class="json__key">%KEY%</div><div class="json__value json__value--type-%TYPE%">%VALUE%</div>%CHILDREN%</label>'
            },
            collapsible: true,
            data: {},
            createdAt: '',
            element: null
        },

        initialize: function (options, element) {
            this._super(options);
            this.element = element;
            this.data[$t('Created At')] = this.createdAt;

            const parsedHtml = this.parseObject(this.data);
            $(element).html(`${parsedHtml}<pre>${JSON.stringify(options.data)}</pre>`)

            return this;
        },

        createItem: function (key, value, type) {
            let element = this.templates.item.replace('%KEY%', key);

            if (type === 'string') {
                element = element.replace('%VALUE%', '"' + value + '"');
            } else {
                element = element.replace('%VALUE%', value);
            }

            element = element.replace('%TYPE%', type);

            return element;
        },

        createCollapsibleItem: function (key, value, type, children) {
            let tpl = 'itemCollapsible';

            if (this.collapsible) {
                tpl = 'itemCollapsibleOpen';
            }

            let element = this.templates[tpl].replace('%KEY%', key);

            element = element.replace('%VALUE%', type);
            element = element.replace('%TYPE%', type);
            element = element.replace('%CHILDREN%', children);

            return element;
        },

        handleChildren: function (key, value, type) {
            let html = '';

            for (let item in value) {
                let _val = value[item];

                html += this.handleItem(item, _val);
            }

            return this.createCollapsibleItem(key, value, type, html);
        },

        handleItem: function (key, value) {
            const type = typeof value;

            if (typeof value === 'object') {
                return this.handleChildren(key, value, type);
            }

            if (typeof value === 'function') {
                return '';
            }

            return this.createItem(key, value, type);
        },

        parseObject: function (obj) {
            const handledItems = Object.keys(obj).map(function (key) {
                return this.handleItem(key, obj[key]);
            }.bind(this))
            return `<div class="json">${handledItems.join('')}</div>`;
        }
    });
});
