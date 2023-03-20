import { Controller } from '@hotwired/stimulus';
import autoComplete from "@tarekraafat/autocomplete.js";
import FormsHelper from "../../helpers/forms";
import Cropper from 'cropperjs';

export default class extends Controller {
    static targets = ['form','remise','credit','costOfDiscountedCredit','allClients',
        'emailSubContractor','cropInput','cropImage','cropModal','logoImage', 'allEmails', 'billingMethod'];

    static values = {
        fetchClientsUrl: String,
        companyAddClient: String,
        companyAddSubcontractor: String,
        company: String,
    }


    connect() {
        this.initValidation();
        this.initClientEmailAutocomplete();
        this.initClientEmailAutocomplete2();
        this.checkPerte();

        const that = this;
        
        this.cropInputTargets.forEach((input) => {
            input.addEventListener('change', (e) => {
                const files = e.target.files;
                const done = function (url) {
                    that.cropImageTarget.src = url;
                    $(that.cropModalTarget).modal('show');
                };

                if (files && files.length > 0) {
                    const file = files[0];

                    if (FileReader) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            done(reader.result);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        });

        this.cropModalTarget.addEventListener('shown.bs.modal', function () {
            that.cropper = new Cropper(that.cropImageTarget, {
                aspectRatio: 1,
                viewMode: 3,
            });
        }).addEventListener('hidden.bs.modal', function () {
            that.cropper.destroy();
            that.cropper = null;
        });
    }

    initClientEmailAutocomplete() {
        let that = this;

        const clientAutoComplete = new autoComplete({
            selector: '#clients_emails',
            placeHolder: 'Entrez un email de client...',
            data: {
                src: async (query) => {
                    try {
                        const source = await fetch(this.fetchClientsUrlValue+'?query='+query+'&client=1');
                        const data = await source.json();

                        return data.clients;
                    } catch (error) {
                        return error;
                    }
                },
                keys: ['email'],
                cache: false,
            },
            debounce: 800,

            events: {
                input: {
                    selection: (event) => {
                        const selection = event.detail.selection.value.email;
                        clientAutoComplete.input.value = selection;
                        window.location.assign(this.companyAddClientValue+'?email='+event.detail.selection.value.email+'&company='+this.companyValue);
                    }
                }
            },
            resultsList: {
                element: (list, data) => {
                    if (!data.results.length) {
                        // Create "No Results" message element
                        const message = document.createElement("div");
                        const create = document.createElement("div");
                        // Add class to the created element
                        message.setAttribute("class", "no_result");
                        create.setAttribute("class", "no_result createAndAdd");
                        // Add message text content
                        message.innerHTML = `<span>Aucun résultat trouvé pour "${data.query}"</span>`;
                        create.innerHTML = `<a href="${this.companyAddClientValue+'?email='+data.query+'&company='+this.companyValue}">Créer</a>`;
                        // Append message element to the results list
                        list.prepend(create);
                        list.prepend(message);
                    }
                },
                noResults: true,
            },
            resultItem: {
                highlight: {
                    render: true
                }
            }
        });
    }

    initClientEmailAutocomplete2() {
        let that = this;

        const clientAutoComplete2 = new autoComplete({
            selector: '#clients_emails_2',
            placeHolder: 'Entrez un email de client...',
            data: {
                src: async (query) => {
                    try {
                        const source = await fetch(this.fetchClientsUrlValue+'?query='+query);
                        const data = await source.json();

                        return data.clients;
                    } catch (error) {
                        return error;
                    }
                },
                keys: ['email'],
                cache: false,
            },
            debounce: 800,

            events: {
                input: {
                    selection: (event) => {
                        const selection = event.detail.selection.value.email;
                        clientAutoComplete2.input.value = selection;
                        let emailSubContractor = $('#emailSubContractor').val(clientAutoComplete2.input.value);
                        let emails = this.allEmailsTarget.value;

                        if (emails.indexOf(clientAutoComplete2.input.value) !== -1){
                            $('.hide-billing').addClass('d-none');
                        }else{
                            $('.hide-billing').removeClass('d-none');
                        }
                        $('#emailSubContractor').val(clientAutoComplete2.input.value);
                        $('#createAndAddSubcontractor').modal('show');
                    }
                }
            },
            resultsList: {
                element: (list, data) => {
                    if (!data.results.length) {
                        // Create "No Results" message element
                        const message = document.createElement("div");
                        const create = document.createElement("div");
                        // Add class to the created element
                        message.setAttribute("class", "no_result");
                        create.setAttribute("class", "no_result createAndAdd");
                        create.setAttribute("data-bs-toggle", "modal");
                        create.setAttribute("data-bs-target", "#createAndAddSubcontractor");
                        // Add message text content
                        message.innerHTML = `<span>Aucun résultat trouvé pour "${data.query}"</span>`;
                        create.innerHTML = `<span>Créer</span>`;
                        $('#emailSubContractor').val(data.query);
                        // Append message element to the results list
                        list.prepend(create);
                        list.prepend(message);
                    }
                },
                noResults: true,
            },
            resultItem: {
                highlight: {
                    render: true
                }
            }
        });
    }

    coutCredit(event){
        let value = event.currentTarget.value.replace(',','.');
        let calcul = (value / 100) * 220;
        this.creditTarget.innerHTML = 220 - calcul;
        this.costOfDiscountedCreditTarget.value = 220 - calcul;
    }

    initValidation() {
        this.validator = FormsHelper.validate(this.formTarget, {
            'company[name]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                }
            },
            'company[enabled]': {
                validators: {
                    notEmpty: {
                        message: 'Au moins un champ est requis'
                    },
                }
            },
            'company[CbPayment]': {
                validators: {
                    notEmpty: {
                        message: 'Au moins un champ est requis'
                    },
                }
            },
            'company[contract]': {
                validators: {
                    notEmpty: {
                        message: 'Au moins un champ est requis'
                    },
                },
            },
            'company[customerDiscount]': {
                validators: {
                    notEmpty: {
                        message: 'Ce champs est requis'
                    },
                    between: {
                        min: 0,
                        max: 100,
                        message: 'La valeur doit se situer entre 0 et 100',
                    }
                }
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

    crop() {
        var canvas;

        $(this.cropModalTarget).modal('hide');

        if (this.cropper) {
            canvas = this.cropper.getCroppedCanvas({
                width: 160,
                height: 160,
            });
            const image = new Image();
            image.src = canvas.toDataURL();
            $(this.logoImageTarget).css('background-image', "url('" + image.src + "')");

            canvas.toBlob((blob) => {
                const formData = new FormData();
                formData.append('logo', blob, 'test.jpg');

                fetch(this.cropModalTarget.dataset.uploadUrl, {
                    method: 'POST',
                    body: formData,
                }).then((res) => {
                    return res.ok;
                });
            });
        }
    }

    checkPerte(){
        var url_string = window.location.href;
        var url = new URL(url_string);
        var perte = url.searchParams.get("perte");
        var email = url.searchParams.get("email");
        var product = url.searchParams.get("product");
        var job = url.searchParams.get("job");

        if(perte !== null){
            let emails = this.allEmailsTarget.value;
            let textPerte = document.getElementById('perte');
            textPerte.innerHTML = Math.trunc(perte);
            $('.zone-perte').removeClass('d-none');

            if (emails.indexOf(email) !== -1){
                $('.hide-billing').addClass('d-none');
            }else{
                $('.hide-billing').removeClass('d-none');
            }
            $('#sub_contractor_company_jobs').val(job).trigger('change');
            $('#sub_contractor_company_products').val(product).trigger('change');
            $('#emailSubContractor').val(email);
            $('#createAndAddSubcontractor').modal('show');
        }
    }
}
