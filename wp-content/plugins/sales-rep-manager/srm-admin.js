jQuery(document).ready(function($){
    var rowCount = $('#srm_sales_reps_table tbody tr').length;

    $('#srm_add_row').on('click', function() {
        var template = $('#srm_template_row').html();
        template = template.replace(__INDEX__g, rowCount);
        $('#srm_sales_reps_table tbody').append(template);
        rowCount++;
    });

    $('#srm_sales_reps_table').on('click', '.srm-remove-row', function() {
        $(this).closest('tr').remove();
    });
});
