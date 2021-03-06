'use strict';
/**
 * Edit form
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @author    Filips Alps <filips@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'jquery',
        'underscore',
        'oro/translator',
        'pim/router',
        'routing',
        'oro/messenger',
        'pim/form/common/edit-form',
        'oro/loading-mask',
        'pim/template/mass-edit/form'
    ],
    function (
        $,
        _,
        __,
        router,
        Routing,
        messenger,
        BaseForm,
        LoadingMask,
        template
    ) {
        return BaseForm.extend({
            template: _.template(template),
            currentStep: 'choose',
            events: {
                'click .wizard-action': 'applyAction'
            },

            /**
             * {@inheritdoc}
             */
            initialize: function (meta) {
                this.config = _.extend({}, meta.config);

                BaseForm.prototype.initialize.apply(this, arguments);
            },

            /**
             * {@inheritdoc}
             */
            render: function () {
                var step = this.currentStep === 'choose' ?
                    this.getChooseExtension() :
                    this.getOperationExtension(this.getCurrentOperation());

                var currentStepNumber = 0;
                currentStepNumber = 'configure' === this.currentStep ? 1 : currentStepNumber;
                currentStepNumber = 'confirm' === this.currentStep ? 2 : currentStepNumber;

                this.$el.html(this.template({
                    currentStep: this.currentStep,
                    currentStepNumber: currentStepNumber,
                    currentOperation: this.getCurrentOperation(),
                    label: step.getLabel(),
                    description: step.getDescription(),
                    title: __(this.config.title, {itemsCount: this.getFormData().itemsCount}),
                    __: __,
                    selectLabel: __(this.config.selectLabel),
                    chooseLabel: __(this.config.chooseLabel),
                    configureLabel: __(this.config.configureLabel),
                    confirmLabel: __(this.config.confirmLabel)
                }));

                this.$('.step').empty().append(step.render().$el);

                this.delegateEvents();
            },

            /**
             * Provide the list of operations available
             *
             * @return {array}
             */
            getOperations: function () {
                return _.chain(this.extensions)
                    .filter(function (extension) {
                        return extension.options.config.label !== undefined;
                    }).map(function (extension) {
                        return {
                            code: extension.getCode(),
                            label: extension.getLabel()
                        };
                    }).value();
            },

            /**
             * Get the chose extension
             *
             * @return {object}
             */
            getChooseExtension: function () {
                return _.filter(this.extensions, function (extension) {
                    return extension.targetZone === 'choose';
                })[0];
            },

            /**
             * The the porvided extension as the current one
             *
             * @param {object} operation
             */
            setCurrentOperation: function (operation) {
                var data = this.getFormData();

                data.operation = operation;
                data.jobInstanceCode = this.getOperationExtension(operation).getJobInstanceCode();

                this.setData(data);

                this.render();
            },

            /**
             * Provide the current oparation
             *
             * @return {string}
             */
            getCurrentOperation: function () {
                return this.getFormData().operation;
            },

            /**
             * Get the operation module corresponding to the given parameter
             *
             * @param {string} operationCode
             *
             * @return {object}
             */
            getOperationExtension: function (operationCode) {
                return _.find(this.extensions, (extension) => {
                    return extension.options.config.label !== undefined && extension.getCode() === operationCode;
                });
            },

            /**
             * Apply the action triggered by a dom event
             *
             * @param {Event} event
             */
            applyAction: function (event) {
                switch (event.target.dataset.actionTarget) {
                    case 'grid':
                        router.redirectToRoute(this.config.backRoute);
                        break;
                    case 'choose':
                        this.currentStep = 'choose';
                        this.render();
                        break;
                    case 'configure':
                        var operationView = this.getOperationExtension(this.getCurrentOperation());
                        if ('choose' === this.currentStep) {
                            operationView.reset();
                        }

                        this.currentStep = 'configure';

                        operationView.setReadOnly(false);
                        this.render();
                        break;
                    case 'confirm':
                        var operationView = this.getOperationExtension(this.getCurrentOperation());

                        var loadingMask = new LoadingMask();
                        loadingMask.render().$el.appendTo(this.getRoot().$el).show();
                        operationView.validate().then((isValid) => {
                            if (isValid) {
                                operationView.setReadOnly(true);
                                this.currentStep = 'confirm';
                                this.render();
                            }
                        })
                        .always(() => {
                            loadingMask.hide().$el.remove();
                        });
                        break;
                    case 'validate':
                        var loadingMask = new LoadingMask();
                        loadingMask.render().$el.appendTo(this.getRoot().$el).show();

                        $.ajax({
                            method: 'POST',
                            contentType: 'application/json',
                            url: Routing.generate('pim_enrich_mass_edit_rest_launch'),
                            data: JSON.stringify(this.getFormData())
                        }).then(() => {
                            router.redirectToRoute(this.config.backRoute);

                            messenger.notify(
                                'success',
                                __(
                                    this.config.launchedLabel,
                                    {
                                        operation: this.getOperationExtension(this.getCurrentOperation()).getLabel()
                                    }
                                )
                            );
                        })
                        .always(() => {
                            loadingMask.hide().$el.remove();
                        });

                        break;
                }
            }
        });
    }
);
