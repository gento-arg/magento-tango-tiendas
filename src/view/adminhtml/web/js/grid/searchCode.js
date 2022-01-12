/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

define([
    'Magento_Ui/js/form/components/insert-listing',
    'uiRegistry'
], function (Component, registry) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initModules: function () {
            this._super();
            let currentSku = this.source.get('data.product.tango_sku');

            registry.get(this.externalListingName + '.listing_top.name', (component) => {
                component.apply(currentSku)
            });
            return this;
        },

        selectSku: function (skuCode) {
            registry.get(this.provider).set('data.product.tango_sku', skuCode)
            registry.get(this.externalListingName + '.listing_top.name').apply(skuCode);
        }
    });
});
