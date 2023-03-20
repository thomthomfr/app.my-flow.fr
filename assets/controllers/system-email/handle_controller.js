import { Controller } from '@hotwired/stimulus';
import TinyMCEHelper from "../../helpers/tiny";
import FormsHelper from "../../helpers/forms";

export default class extends Controller {
    static targets = ['form','tinyMCE'];

    connect() {
        this.initValidation();
        this.initTinyMCE();
    }

    initValidation() {
        // validation du formulaire
        this.validator = FormsHelper.validate(this.formTarget, {
            'system_email[title]': {
                validators: {
                    notEmpty: {
                        message: 'Merci de remplir ce champ'
                    },
                }
            },
            'system_email[sender]': {
                validators: {
                    notEmpty: {
                        message: 'Merci de remplir ce champ'
                    },
                    email: {
                        message: 'Le format de l\'adresse n\'est pas valide'
                    },
                }
            },
            'system_email[senderName]': {
                validators: {
                    notEmpty: {
                        message: 'Merci de remplir ce champ'
                    },
                }
            },
            'system_email[content]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champ est requis'
                    }
                }
            },
            'system_email[subject]': {
                validators: {
                    notEmpty: {
                        message: 'Merci de remplir ce champ'
                    },
                }
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

    initTinyMCE() {
        TinyMCEHelper.init(this.tinyMCETarget);
    }
}
