import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['formUser'];

    connect() {
        this.initValidation();
        $('.add-another-collection-widget').click(function (e) {
            let list = $($(this).attr('data-list-selector'));
            let counter = list.data('widget-counter') || list.children().length;

            let newWidget = list.attr('data-prototype');
            newWidget = newWidget.replace(/__name__/g, counter);
            counter++;
            list.data('widget-counter', counter);

            let newElem = $(list.attr('data-widget-tags')).html(newWidget);
            newElem.appendTo(list);
        });
    }


    initValidation() {
        // validation du formulaire
        this.validator = FormValidation.formValidation(
            this.formUserTarget,
            {
                fields: {
                    'user[firstname]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                        }
                    },
                    'user[lastname]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                        }
                    },
                    'user[email]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                        }
                    },
                    'user[cellPhone]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                        },
                    },
                    'user[accountType]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                        }
                    },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5()
                }
            }
        ).on('core.field.invalid', function(event) {
            let id = document.getElementsByName(event)[0].getAttribute('id');
            $($('#'+id).parents().get(0)).find('.invalid-feedback').remove();
        });
    }

    addUserForm(){
        // // $('.add-another-collection-widget').click(function (e) {
        //     let list = $($(this).attr('data-list-selector'));
        //     let counter = list.data('widget-counter') || list.children().length;
        //
        //     let newWidget = list.attr('data-prototype');
        //     newWidget = newWidget.replace(/__name__/g, counter);
        //     counter++;
        //     list.data('widget-counter', counter);
        //
        //     let newElem = $(list.attr('data-widget-tags')).html(newWidget);
        //     newElem.appendTo(list);
        // });
    }

    submitUserForm(event) {
        let that = this;

        // On ne soumet le formulaire que s'il passe la validation
        this.validator.validate().then(function (status) {
            if (status == 'Valid') {
                // On active l'animation sur le bouton de submit et on le disabled
                // pour éviter les double click
                event.currentTarget.setAttribute("data-kt-indicator", "on");
                event.currentTarget.disabled = true;

                // On envoie le formulaire
                that.formUserTarget.submit();
            }
        });
    }
}
