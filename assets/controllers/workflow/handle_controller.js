import { Controller } from '@hotwired/stimulus';
import FormsHelper from "../../helpers/forms";

export default class extends Controller {
    static targets = [
        'form', 'addStepCard', 'addStepForm', 'addActionModal', 'hiddenActionStepId', 'addActionForm',
        'addActionNameInput', 'addActionRecipientInput', 'addActionJobInput',
        'hiddenActionId', 'deleteActionButton', 'addStepNameInput', 'addStepCompletionTimeInput', 'addStepCustomerDescriptionInput',
        'addStepSupplierDescriptionInput', 'hiddenStepId', 'deleteStepButton', 'addTriggerContainer', 'addStepManagerInput', 'addStepJobInput',
        'addChildTriggerContainer', 'addActionJobContainer',
    ];

    static values = {
        fetchActionUrl: String,
        deleteActionUrl: String,
        fetchStepUrl: String,
        deleteStepUrl: String,
    }

    connect() {
        this.initFormValidation();
        this.initAddStepFormValidation();
        this.initAddActionFormValidation();

        this.addActionRecipientInputTarget.addEventListener('change', (event) => {
            const select = event.target;
            if (select.value === 'ROLE_SUBCONTRACTOR') {
                this.addActionJobContainerTarget.classList.remove('d-none');
            } else {
                this.addActionJobContainerTarget.classList.add('d-none');
            }
        });

        this.addStepManagerInputTarget.addEventListener('change', (event) => {
            const select = event.target;
            if (select.value === '1') {
                this.addStepCustomerDescriptionInputTargets.forEach((elem) => {
                    elem.classList.add('d-none');
                });
                this.addStepSupplierDescriptionInputTargets.forEach((elem) => {
                    elem.classList.remove('d-none');
                });
            } else {
                this.addStepCustomerDescriptionInputTargets.forEach((elem) => {
                    elem.classList.remove('d-none');
                });
                this.addStepSupplierDescriptionInputTargets.forEach((elem) => {
                    elem.classList.add('d-none');
                });
            }
        });
    }

    initFormValidation() {
        this.formValidator = FormsHelper.validate(this.formTarget, {
            'workflow[name]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'workflow[product]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
        });
    }

    initAddStepFormValidation() {
        this.addStepFormValidator = FormsHelper.validate(this.addStepCardTarget, {
            'workflow_step[name]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'workflow_step[completionTime]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
        });
    }

    initAddActionFormValidation() {
        this.addActionFormValidator = FormsHelper.validate(this.addActionFormTarget, {
            'workflow_action[name]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'workflow_action[recipient]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
        });
    }

    submitForm(event) {
        let that = this;

        // On ne soumet le formulaire que s'il passe la validation
        this.formValidator.validate().then(function (status) {
            if (status == 'Valid') {
                // On active l'animation sur le bouton de submit et on le disabled
                // pour éviter les double click
                event.currentTarget.setAttribute("data-kt-indicator", "on");
                event.currentTarget.disabled = true;

                // On envoie le formulaire
                that.formTarget.requestSubmit();
            }
        });
    }

    addStep() {
        this.addStepNameInputTarget.value = null;
        this.addStepCompletionTimeInputTarget.value = null;
        this.addStepCustomerDescriptionInputTarget.value = null;
        this.addStepSupplierDescriptionInputTarget.value = null;
        this.addStepManagerInputTarget.value = null;
        this.addStepJobInputTarget.value = null;
        this.hiddenStepIdTarget.value = null;
        this.addStepCardTarget.style.top = '-10rem';
        this.addStepCardTarget.classList.toggle('d-none');
        this.addStepCustomerDescriptionInputTargets.forEach((elem) => {
            elem.classList.add('d-none');
        });
        this.addStepSupplierDescriptionInputTargets.forEach((elem) => {
            elem.classList.add('d-none');
        });
    }

    async editStep(event) {
        const stepId = event.currentTarget.dataset.stepId;
        const top = event.currentTarget.dataset.top;
        const step = await this.fetchStep(stepId);

        this.addStepNameInputTarget.value = step.name;
        this.addStepCompletionTimeInputTarget.value = step.completionTime;
        this.addStepCustomerDescriptionInputTarget.value = step.customerDescription;
        this.addStepSupplierDescriptionInputTarget.value = step.supplierDescription;
        this.addStepManagerInputTarget.value = step.manager;

        if (step.manager === 1) {
            this.addStepCustomerDescriptionInputTargets.forEach((elem) => {
                elem.classList.add('d-none');
            });
            this.addStepSupplierDescriptionInputTargets.forEach((elem) => {
                elem.classList.remove('d-none');
            });
        } else {
            this.addStepCustomerDescriptionInputTargets.forEach((elem) => {
                elem.classList.remove('d-none');
            });
            this.addStepSupplierDescriptionInputTargets.forEach((elem) => {
                elem.classList.add('d-none');
            });
        }

        if (step.job) {
            this.addStepJobInputTarget.value = step.job.id;
        } else {
            this.addStepJobInputTarget.value = null;
        }
        this.hiddenStepIdTarget.value = step.id;
        this.deleteStepButtonTarget.classList.remove('d-none');
        this.deleteStepButtonTarget.setAttribute('href', this.deleteStepUrlValue.replace('999999', stepId));
        this.addStepCardTarget.style.top = top;
        this.addStepCardTarget.classList.toggle('d-none');
    }

    async fetchStep(stepId) {
        return fetch(this.fetchStepUrlValue.replace('999999', stepId), {
            method: 'GET',
            headers: {
                'Accept':'application/json',
            },
        }).then((res) => {
            return res.json();
        }).then((data) => {
            return data.step;
        });
    }

    submitAddStepForm(event) {
        let that = this;

        // On ne soumet le formulaire que s'il passe la validation
        this.addStepFormValidator.validate().then(function (status) {
            if (status == 'Valid') {
                // On active l'animation sur le bouton de submit et on le disabled
                // pour éviter les double click
                event.currentTarget.setAttribute("data-kt-indicator", "on");
                event.currentTarget.disabled = true;

                // On envoie le formulaire
                that.addStepFormTarget.requestSubmit();
            }
        });
    }

    addAction(event) {
        this.addActionNameInputTarget.value = null;
        this.addActionJobInputTarget.value = null;
        this.addActionRecipientInputTarget.value = null;
        this.hiddenActionIdTarget.value = null;
        this.deleteActionButtonTarget.classList.add('d-none');
        this.addActionJobContainerTarget.classList.add('d-none');

        this.hiddenActionStepIdTarget.value = event.currentTarget.dataset.stepId;
        var list = $(this.addTriggerContainerTarget);

        if(list != null){
            list.html('');
        }
        $(this.addActionModalTarget).modal('show');
    }

    submitAddActionForm(event) {
        let that = this;

        // On ne soumet le formulaire que s'il passe la validation
        this.addActionFormValidator.validate().then(function (status) {
            if (status == 'Valid') {
                // On active l'animation sur le bouton de submit et on le disabled
                // pour éviter les double click
                event.currentTarget.setAttribute("data-kt-indicator", "on");
                event.currentTarget.disabled = true;

                // On envoie le formulaire
                that.addActionFormTarget.requestSubmit();
            }
        });
    }

    async editAction(event) {
        const stepId = event.currentTarget.dataset.stepId;
        const actionId = event.currentTarget.dataset.actionId;
        const action = await this.fetchAction(stepId, actionId);
        this.addActionJobContainerTarget.classList.add('d-none');

        this.addActionNameInputTarget.value = action.name;

        this.addActionJobInputTarget.value = null;
        if (action.job) {
            this.addActionJobInputTarget.value = action.job.id;
        }

        this.addActionRecipientInputTarget.value = action.recipient;

        if (action.recipient === 'ROLE_SUBCONTRACTOR') {
            this.addActionJobContainerTarget.classList.remove('d-none');
        }

        this.hiddenActionStepIdTarget.value = action.step.id;
        this.hiddenActionIdTarget.value = actionId;
        this.deleteActionButtonTarget.classList.remove('d-none');
        this.deleteActionButtonTarget.setAttribute('href', this.deleteActionUrlValue.replace('999999', stepId).replace('111111', actionId));

        var list = $(this.addTriggerContainerTarget);

        if(list != null){
            list.html('');
        }

        action.triggers.forEach((trigger) => {
            var counter =  list.data('widget-counter') || list.children().length;
            // grab the prototype template
            var newWidget = list.attr('data-prototype');
            // replace the "__name__" used in the id and name of the prototype
            // with a number that's unique to your emails
            // end name attribute looks like name="contact[emails][2]"
            newWidget = newWidget.replace(/__name__/g, counter);

            // create a new list element and add it to the list
            var newElem = $(list.attr('data-widget-tags')).html(newWidget);

            newElem.appendTo(list);

            $('#workflow_action_triggers_'+counter+'_triggerType').change(function() {
                if ($(this).val() == 6 || $(this).val() == 7) {
                    $('body').find("[data-selector='#trigger_child_container_" + (counter-1) + "']").removeClass('d-none');
                } else {
                    $('body').find("[data-selector='#trigger_child_container_" + (counter-1) + "']").addClass('d-none');
                }
            });

            document.getElementById('workflow_action_triggers_'+counter+'_triggerType').value = trigger.triggerType;
            document.getElementById('workflow_action_triggers_'+counter+'_operator').value = trigger.operator;
            document.getElementById('workflow_action_triggers_'+counter+'_timePeriod').value = trigger.timePeriod;

            if (trigger.emailTemplate) {
                document.getElementById('workflow_action_triggers_'+counter+'_emailTemplate').value = trigger.emailTemplate.id;
            }

            document.getElementById('workflow_action_triggers_'+counter+'_operation').value = trigger.operation;

            trigger.childs.forEach((trigger) => {
                var list = $('#trigger_child_container_'+counter);

                var counter2 =  list.data('widget-counter2') || list.children().length;
                // grab the prototype template
                var newWidget = list.attr('data-prototype');
                // replace the "__name__" used in the id and name of the prototype
                // with a number that's unique to your emails
                // end name attribute looks like name="contact[emails][2]"
                newWidget = newWidget.replace(/__name__/g, counter);
                newWidget = newWidget.replace(/__child__/g, counter2);

                // create a new list element and add it to the list
                var newElem = $(list.attr('data-widget-tags')).html(newWidget);

                newElem.appendTo(list);

                document.getElementById('workflow_action_triggers_'+counter+'_childs_'+counter2+'_triggerType').value = trigger.triggerType;
                document.getElementById('workflow_action_triggers_'+counter+'_childs_'+counter2+'_operator').value = trigger.operator;
                document.getElementById('workflow_action_triggers_'+counter+'_childs_'+counter2+'_timePeriod').value = trigger.timePeriod;

                if (trigger.emailTemplate) {
                    document.getElementById('workflow_action_triggers_'+counter+'_childs_'+counter2+'_emailTemplate').value = trigger.emailTemplate.id;
                }

                document.getElementById('workflow_action_triggers_'+counter+'_childs_'+counter2+'_operation').value = trigger.operation;

                // Increase the counter2
                counter2++;
                // And store it, the length cannot be used if deleting widgets is allowed
                list.data('widget-counter2', counter2);
            });

            // Increase the counter
            counter++;
            // And store it, the length cannot be used if deleting widgets is allowed
            list.data('widget-counter', counter);
        });

        $(this.addActionModalTarget).modal('show');

    }

    async fetchAction(stepId, actionId) {
        return fetch(this.fetchActionUrlValue.replace('999999', stepId).replace('111111', actionId), {
            method: 'GET',
            headers: {
                'Accept':'application/json',
            },
        }).then((res) => {
            return res.json();
        }).then((data) => {
            return data.action;
        });
    }

    addTriggerWidget(event) {
        let list = $(event.currentTarget.dataset.selector);

        // Try to find the counter of the list or use the length of the list
        let counter =  list.data('widget-counter') || list.children().length;

        // grab the prototype template
        let newWidget = list.attr('data-prototype');
        // replace the "__name__" used in the id and name of the prototype
        // with a number that's unique to your emails
        // end name attribute looks like name="contact[emails][2]"
        newWidget = newWidget.replace(/__name__/g, counter);

        if (event.currentTarget.dataset.selector.match('child')) {
            newWidget = newWidget.replace(/__child__/g, counter);
        }

        // create a new list element and add it to the list
        let newElem = $(list.attr('data-widget-tags')).html(newWidget);
        newElem.appendTo(list);

        $('#workflow_action_triggers_'+counter+'_triggerType').change(function() {
            if ($(this).val() == 6 || $(this).val() == 7) {
                $('body').find("[data-selector='#trigger_child_container_" + (counter-1) + "']").removeClass('d-none');
            } else {
                $('body').find("[data-selector='#trigger_child_container_" + (counter-1) + "']").addClass('d-none');
            }
        });

        // Increase the counter
        counter++;
        // And store it, the length cannot be used if deleting widgets is allowed
        list.data('widget-counter', counter);
    }
}
