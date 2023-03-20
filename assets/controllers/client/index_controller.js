import { Controller } from 'stimulus';
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

    searchTarif(){
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            var min = parseInt($('#tarif-min').val(), 10);
            var max = parseInt($('#tarif-max').val(), 10);
            var price = parseFloat(data[6]);

            if (
                (isNaN(min) && isNaN(max)) ||
                (isNaN(min) && price <= max) ||
                (min <= price && isNaN(max)) ||
                (min <= price && price <= max)
            ) {
                return true;
            }
            return false;
        });
        $(document).ready(function () {
            var table = $('#kt_datatable_example_1').DataTable();
            $('#tarif-min, #tarif-max').keyup(function () {
                table.draw();
            });
        });
    }
}
