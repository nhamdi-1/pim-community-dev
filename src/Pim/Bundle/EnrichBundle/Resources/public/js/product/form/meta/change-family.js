'use strict';
/**
 * Change family extension
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'underscore',
        'backbone',
        'pim/form',
        'pim/product-manager',
        'pim/template/product/meta/change-family-modal',
        'pim/common/select2/family',
        'pim/initselect2',
        'bootstrap-modal',
        'jquery.select2'
    ],
    function (
        _,
        Backbone,
        BaseForm,
        ProductManager,
        modalTemplate,
        Select2Configurator,
        initSelect2
    ) {
        return BaseForm.extend({
            tagName: 'i',
            className: 'icon-pencil change-family AknTitleContainer-metaLink',
            modalTemplate: _.template(modalTemplate),
            events: {
                'click': 'showModal'
            },
            render: function () {
                this.delegateEvents();

                return BaseForm.prototype.render.apply(this, arguments);
            },
            showModal: function () {
                var familyModal = new Backbone.BootstrapModal({
                    allowCancel: true,
                    cancelText: _.__('pim_enrich.entity.product.meta.groups.modal.close'),
                    title: _.__('pim_enrich.form.product.change_family.modal.title'),
                    content: this.modalTemplate({
                        product: this.getFormData()
                    })
                });

                familyModal.on('ok', function () {
                    var selectedFamily = familyModal.$('.family-select2').select2('val') || null;

                    this.getFormModel().set('family', selectedFamily);
                    ProductManager.generateMissing(this.getFormData()).then(function (product) {
                        this.getRoot().trigger('pim_enrich:form:change-family:before');

                        this.setData(product);

                        this.getRoot().trigger('pim_enrich:form:change-family:after');
                        familyModal.close();
                    }.bind(this));
                }.bind(this));

                familyModal.open();

                var options = Select2Configurator.getConfig(this.getFormData().family);

                initSelect2.init(familyModal.$('.family-select2'), options).select2('val', []);
            }
        });
    }
);
