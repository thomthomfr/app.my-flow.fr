import dt from 'datatables.net';
import '../theme/src/js/vendors/plugins/datatables.init';

export default class DatatablesHelper {
    static init = (elem) => {
        return $(elem).DataTable({
            info: false,
            order: [],
            language: {
                'sEmptyTable': 'Aucune donnée disponible dans le tableau',
                'sZeroRecords': 'Aucun élément correspondant trouvé',
            }
        });
    }
}
