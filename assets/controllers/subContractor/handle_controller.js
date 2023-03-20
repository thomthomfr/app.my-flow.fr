import { Controller } from '@hotwired/stimulus';
import FormsHelper from "../../helpers/forms";
import Select2Helper from "../../helpers/select2";

export default class extends Controller {
    static targets = [
        'form', 'addServiceForm', 'addServiceModal', 'serviceName', 'servicePrice', 'hiddenServiceId', 'select2', 'select2Job',
        'editCompanyJobsModal', 'editCompanyJobsForm', 'editCompanyJobsFormInput', 'editCompanyProductsFormInput', 'serviceResale',
    ];

    static values = {
        fetchServiceUrl: String,
    }

    connect() {
        this.initValidation();

        if (this.hasSelect2Target) {
            this.initSelect2();
        }

        if (this.hasSelect2JobTarget) {
            this.initSelect2Job();
        }
    }

    initValidation() {
        // validation du formulaire
        this.validator = FormsHelper.validate(this.formTarget, {
            'sub_contractor[firstname]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'sub_contractor[lastname]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'sub_contractor[email]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champ est requis'
                    },
                    emailAddress: {
                        message: 'Cette adresse n\'est pas valide',
                    },
                }
            },
            'sub_contractor[dailyRate]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                },
            },
            'sub_contractor[jobs]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'sub_contractor[billingMethod]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'sub_contractor[gender]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'sub_contractor[cellPhone]': {
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

    addService() {
        this.serviceNameTarget.value = null;
        this.servicePriceTarget.value = null;

        if (this.hasHiddenResaleTarget) {
            this.hiddenResaleTarget.value = null;
        }

        if (this.hasHiddenServiceIdTarget) {
            this.hiddenServiceIdTarget.value = null;
        }

        $(this.addServiceModalTarget).modal('show');

    }

    async editService(event) {
        const serviceId = event.currentTarget.dataset.serviceId;
        const service = await this.fetchService(serviceId);

        this.hiddenServiceIdTarget.value = serviceId;
        $("#"+this.serviceNameTarget.id).val(service.product.id).trigger('change.select2');
        this.servicePriceTarget.value = service.price;

        if (this.hasServiceResaleTarget) {
            this.serviceResaleTarget.value = service.resale;
        }

        $(this.addServiceModalTarget).modal('show');
    }

    async fetchService(serviceId) {
        return fetch(this.fetchServiceUrlValue.replace('999999', serviceId), {
            method: 'GET',
            headers: {
                'Accept':'application/json',
            },
        }).then((res) => {
            return res.json();
        }).then((data) => {
            return data.service;
        });
    }

    initSelect2() {
        this.select2Targets.forEach((elem) => {
            Select2Helper.init(elem, {dropdownParent: $("#"+this.addServiceModalTarget.id)});
        });
    }

    initSelect2Job() {
        this.select2JobTargets.forEach((elem) => {
            Select2Helper.init(elem);
        });
    }

    editCompanyJobs(event) {
        this.editCompanyJobsFormTarget.action = event.currentTarget.dataset.url;
        event.currentTarget.dataset.jobs.split(',').forEach((jobId) => {
             let options = this.editCompanyJobsFormInputTarget.options;

             for (let i = 0; i < options.length; i++) {
                 if (options[i].value == jobId) {
                     options[i].setAttribute('selected', true);
                 }
             }
        });
        event.currentTarget.dataset.products.split(',').forEach((productId) => {
            let options2 = this.editCompanyProductsFormInputTarget.options;

            for (let i = 0; i < options2.length; i++) {
                if (options2[i].value == productId) {
                    options2[i].setAttribute('selected', true);
                }
            }
        });

        this.select2Targets.forEach((elem) => {
            Select2Helper.init(elem);
        });

        $(this.editCompanyJobsModalTarget).modal('show');
    }
}
