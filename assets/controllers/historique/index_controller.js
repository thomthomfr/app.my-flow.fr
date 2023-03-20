import { Controller } from 'stimulus';
import DatatablesHelper from "../../helpers/datatables";

export default class extends Controller {
    static targets = ['datatable'];

    connect() {
        // initialisation du Datatable
        $('#kt_datatable_mission_historique').dataTable( {
            'pageLength': 5,
            info: false,
            order: [],
            language: {
                'sEmptyTable': 'Aucune donnée disponible dans le tableau',
                'sZeroRecords': 'Aucun élément correspondant trouvé',
            }
        });
    }

    // Fonction de recherche dans toutes les colonnes du datatable
    searchTable(event) {
        this.table.search(event.currentTarget.value).draw();
    }
}
