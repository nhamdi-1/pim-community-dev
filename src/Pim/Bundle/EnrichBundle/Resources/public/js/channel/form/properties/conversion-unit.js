

/**
 * Module used to display the conversion unit properties of a channel
 *
 * @author    Alexandr Jeliuc <alex@jeliuc.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
import $ from 'jquery'
import _ from 'underscore'
import __ from 'oro/translator'
import BaseForm from 'pim/form'
import FetcherRegistry from 'pim/fetcher-registry'
import template from 'pim/template/channel/tab/properties/conversion-unit'
import UserContext from 'pim/user-context'
import i18n from 'pim/i18n'
import 'jquery.select2'
export default BaseForm.extend({
    className: 'tabsection',
    template: _.template(template),
    catalogLocale: UserContext.get('catalogLocale'),
    config: null,

            /**
             * {@inheritdoc}
             */
    initialize: function (config) {
        this.config = config.config

        BaseForm.prototype.initialize.apply(this, arguments)
    },

            /**
             * {@inheritdoc}
             */
    render: function () {
        if (!this.configured) {
            return this
        }

        $.when(
                    FetcherRegistry.getFetcher('attribute').search({'types': 'pim_catalog_metric'}),
                    FetcherRegistry.getFetcher('measure').fetchAll()
                ).then(function (attributes, measures) {

                    this.$el.html(this.template({
                        conversionUnits: this.getFormData().conversion_units,
                        metrics: attributes,
                        measures: measures,
                        catalogLocale: this.catalogLocale,
                        label: __(this.config.label),
                        fieldBaseId: this.config.fieldBaseId,
                        doNotConvertLabel: __('pim_enrich.form.channel.tab.properties.conversion_unit.do_not_convert'),
                        i18n: i18n
                    }))

                    this.$('.select2').select2().on('change', this.updateState.bind(this))
                    this.renderExtensions()
                }.bind(this))

        return this
    },

            /**
             * Sets new attribute conversion unit on change.
             *
             * @param {Object} event
             */
    updateState: function (event) {
        this.setAttributeConversionUnit(
                    event.currentTarget.id.replace(this.config.fieldBaseId, ''),
                    event.currentTarget.value
                )

    },

            /**
             * Sets specified conversion unit settings into form model.
             *
             * @param {String} attribute
             * @param {String} value
             */
    setAttributeConversionUnit: function (attribute, value) {
        var data = this.getFormData()

        if (_.isEmpty(data.conversion_units)) {
            data.conversion_units = {}
        }

        if (value !== 'no_conversion') {
            data.conversion_units[attribute] = value
        } else {
            delete data.conversion_units[attribute]
        }

        this.setData(data)
    }
})

