import { Controller } from '@hotwired/stimulus';
import autoComplete from "@tarekraafat/autocomplete.js";

export default class extends Controller {
    static targets = ['modal', 'modalSubContractor', 'editSubcontractorModal', 'editSubcontractorForm'];

    static values = {
        fetchClientsUrl: String,
    }

    connect() {
        this.initClientEmailAutocomplete();
        this.initSubContractorEmailAutocomplete();
    }

    openModal() {
        const myModal = new bootstrap.Modal(this.modalTarget);
        myModal.show();
    }
    openModalSubContractor() {
        const myModal = new bootstrap.Modal(this.modalSubContractorTarget);
        myModal.show();
    }

    openEditSubcontractorModal(event) {
        this.editSubcontractorFormTarget.action = event.currentTarget.dataset.url;
        const myModal = new bootstrap.Modal(this.editSubcontractorModalTarget);
        myModal.show();
    }

    initClientEmailAutocomplete() {
        const clientAutoComplete = new autoComplete({
            selector: '#add_mission_contact_user',
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

            events: {
                input: {
                    selection: (event) => {
                        document.getElementById('add_mission_contact_user').value = event.detail.selection.value.email;
                    }
                }
            },
            resultsList: {
                element: (list, data) => {
                    if (!data.results.length) {
                        // Create "No Results" message element
                        const message = document.createElement("div");
                        // Add class to the created element
                        message.setAttribute("class", "no_result");
                        // Add message text content
                        message.innerHTML = `<span>Aucun résultat trouvé pour "${data.query}"</span>`;
                        // Append message element to the results list
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

    initSubContractorEmailAutocomplete() {
        const clientAutoComplete = new autoComplete({
            selector: '#add_mission_sub_contractor_user',
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

            events: {
                input: {
                    selection: (event) => {
                        document.getElementById('add_mission_sub_contractor_user').value = event.detail.selection.value.email;
                    }
                }
            },
            resultsList: {
                element: (list, data) => {
                    if (!data.results.length) {
                        // Create "No Results" message element
                        const message = document.createElement("div");
                        // Add class to the created element
                        message.setAttribute("class", "no_result");
                        // Add message text content
                        message.innerHTML = `<span>Aucun résultat trouvé pour "${data.query}"</span>`;
                        // Append message element to the results list
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
}
