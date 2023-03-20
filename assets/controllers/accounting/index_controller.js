import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['datatable', 'addInvoiceForm', 'addInvoiceModal', 'addTotalCostForm'];

    connect() {

    }

    openInvoiceModal(event){
        this.addInvoiceFormTarget.action = event.currentTarget.dataset.url;
        var myModal = new bootstrap.Modal(document.getElementById('addInvoiceModal'));
        myModal.show();
    }

    changeTotalCost(event){
        this.addTotalCostFormTarget.action = event.currentTarget.dataset.cost;
    }
}
