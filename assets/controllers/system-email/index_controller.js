import { Controller } from '@hotwired/stimulus';
import DatatablesHelper from "../../helpers/datatables";

export default class extends Controller {
    static targets = ['datatable'];

    connect() {
        // initialisation du Datatable
        this.table = DatatablesHelper.init(this.datatableTarget);
    }

    // Fonction de recherche dans toutes les colonnes du datatable
    searchTable(event) {
        this.table.search(event.currentTarget.value).draw();
    }
}
