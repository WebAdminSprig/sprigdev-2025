jQuery(document).ready(function($) {
    // Add new sales rep row
    $('.add-row').click(function() {
        var newRow = $('.empty-row.screen-reader-text').clone(true);
        newRow.removeClass('empty-row screen-reader-text');
        newRow.insertBefore('#sales-reps-table tbody>tr:last');
        return false;
    });

    // Remove sales rep row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        return false;
    });
});
