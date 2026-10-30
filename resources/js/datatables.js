import DataTable from 'datatables.net-dt';

// Configuración de 04 §8. La tabla llega completa del servidor: sin JS queda la tabla HTML.
const es = {
  search: 'Buscar',
  lengthMenu: '_MENU_ filas por página',
  info: 'Mostrando _START_ a _END_ de _TOTAL_',
  infoEmpty: 'Sin resultados',
  infoFiltered: '(filtrado de _MAX_)',
  zeroRecords: 'Ningún registro coincide con la búsqueda',
  emptyTable: 'Sin registros',
  paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
  aria: {
    orderable: 'Ordenar por esta columna',
    orderableReverse: 'Invertir el orden',
    paginate: { first: 'Primera página', previous: 'Página anterior', next: 'Página siguiente', last: 'Última página' },
  },
};

document.querySelectorAll('table[data-datatable]').forEach((table) => {
  new DataTable(table, {
    language: es,
    pageLength: 10,
    lengthMenu: [10, 25, 50],
    order: [[1, 'asc']],
    layout: { topStart: 'pageLength', topEnd: 'search', bottomStart: 'info', bottomEnd: 'paging' },
    columnDefs: [{ targets: [0, -1], orderable: false, searchable: false }], // imagen y acciones
  });
  // 2.3.8 fija aria-label="pagination" en el <nav> sin opción de idioma; el nav se crea una sola vez.
  table.closest('.dt-container')?.querySelector('.dt-paging nav')?.setAttribute('aria-label', 'Paginación');
});
