import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['form'];

    connect() {
        this.initValidation();
    }

    initValidation() {
        // validation du formulaire
        this.validator = FormValidation.formValidation(
            this.formTarget,
            {
                fields: {
                    'job[name]': {
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
}
