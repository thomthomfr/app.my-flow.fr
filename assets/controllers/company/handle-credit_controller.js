import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['formCredit', 'credit', 'mensualite', 'annuite', 'typePack'];

    connect() {
        this.initValidation();
    }


    initValidation() {
        // validation du formulaire
        this.validator = FormValidation.formValidation(
            this.formCreditTarget,
            {
                fields: {
                    'add_credit_company[credit]': {
                        validators: {
                            notEmpty: {
                                message: 'Ce champs est requis'
                            },
                            integer: {
                                message: 'Ce champ doit contenir uniquement des chiffres'
                            }
                        }
                    },
                    'add_credit_company[creditExpiredAt]': {
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

    submitCreditForm(event) {
        let that = this;

        // On ne soumet le formulaire que s'il passe la validation
        this.validator.validate().then(function (status) {
            if (status == 'Valid') {
                // On active l'animation sur le bouton de submit et on le disabled
                // pour éviter les double click
                event.currentTarget.setAttribute("data-kt-indicator", "on");
                event.currentTarget.disabled = true;

                // On envoie le formulaire
                that.formCreditTarget.submit();
            }
        });
    }

    checkTypePack(event){
        if (event.currentTarget.value == 0){
            this.mensualiteTarget.setAttribute('class','d-none');
            this.annuiteTarget.setAttribute('class','d-none');
            this.creditTarget.setAttribute('class', 'd-block');
        }else if(event.currentTarget.value == 1){
            this.annuiteTarget.setAttribute('class','d-none');
            this.creditTarget.setAttribute('class','d-none');
            this.mensualiteTarget.setAttribute('class','d-block');
        }else{
            this.annuiteTarget.setAttribute('class','d-block');
            this.creditTarget.setAttribute('class','d-none');
            this.mensualiteTarget.setAttribute('class','d-none');
        }
    }
}
