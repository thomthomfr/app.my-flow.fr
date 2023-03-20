import { Controller } from 'stimulus';
import FormsHelper from "../../helpers/forms";

export default class extends Controller {
    static targets = ['form'];

    connect() {
        this.initValidation();
    }

    initValidation() {
        // validation du formulaire
        this.validator = FormsHelper.validate(this.formTarget, {
            fields: {
                'client[firstname]': {
                    validators: {
                        notEmpty: {
                            message: 'Ce champs est requis'
                        },
                    }
                },
                'client[lastname]': {
                    validators: {
                        notEmpty: {
                            message: 'Ce champs est requis'
                        },
                    }
                },
                'client[email]': {
                    validators: {
                        notEmpty: {
                            message: 'Ce champ est requis'
                        },
                        emailAddress: {
                            message: 'Cette adresse n\'est pas valide',
                        },
                    }
                },
                'client[cellPhone]': {
                    validators: {
                        notEmpty: {
                            message: 'Ce champs est requis'
                        },
                        phone: {
                            country: 'FR',
                            message: 'Ce numéro de téléphone n\'est pas valide',
                        },
                    },
                },
                'client[state]': {
                    validators: {
                        notEmpty: {
                            message: 'Ce champs est requis'
                        },
                    }
                },
            }
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
                that.formTarget.requestSubmit();
            }
        });
    }
}
