    </main>
</div>

<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables core + Bootstrap 5 styling -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Bootstrap JS bundle (popovers, dropdowns, dismissible alerts) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-init DataTables on any <table class="datatable">
$(function () {
    if (!$.fn.DataTable) return;
    $('table.datatable').each(function () {
        $(this).DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [],
            columnDefs: [
                { orderable: false, targets: 'no-sort' }
            ],
            language: {
                search: 'Filter:',
                lengthMenu: 'Show _MENU_ rows',
                info: 'Showing _START_–_END_ of _TOTAL_',
                infoEmpty: 'No records',
                emptyTable: 'No records in this sheet',
                paginate: { previous: '‹', next: '›' }
            }
        });
    });
});
</script>

</body>
</html>
