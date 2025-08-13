require('datatables.net');
require('datatables.net-bs5');
require('datatables.net-fixedcolumns-bs5')

import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
// import 'datatables.net-responsive-bs4/css/responsive.bootstrap4.css';

$.extend(true, $.fn.dataTable.defaults, {
    'pageLength': 20,
    'lengthChange': false,
    'info': false,
    'scrollX': true,
    'language': $.extend(
        require('datatables.net-translations/French').default,
        {
            info: "Eléments _START_ à _END_ sur _TOTAL_",
        }
    ),
})