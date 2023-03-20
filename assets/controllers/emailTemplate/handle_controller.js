import { Controller } from '@hotwired/stimulus';
import TinyMCEHelper from "../../helpers/tiny";

export default class extends Controller {
    static targets = ['form','tinyMCE'];

    connect() {
        this.initValidation();
        this.initTinyMCE();
    }

    initValidation() {
        // validation du formulaire
        this.validator = FormValidation.formValidation(
            this.formTarget,
            {
                fields: {
                    'emailTemplate[title]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                        }
                    },
                    'emailTemplate[senderName]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                        }
                    },
                    'emailTemplate[content]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champ est requis'
                            }
                        }
                    },
                    'emailTemplate[active]': {
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

    submitForm(event) {
        let that = this;

        // On ne soumet le formulaire que s'il passe la validation
        this.validator.validate().then(function (status) {
            if (status == 'Valid') {
                // On active l'animation sur le bouton de submit et on le disabled
                // pour éviter les double click
                event.currentTarget.setAttribute("data-kt-indicator", "on");
                event.currentTarget.disabled = true;

                // On envoie le formulaire
                that.formTarget.submit();
            }
        });
    }

    initTinyMCE(){
        TinyMCEHelper.init(this.tinyMCETarget);
    }
}
