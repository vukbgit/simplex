/**
* ERP javascript functions
**/

$(document).ready(function(){
    /**
    * table bulk actions
    **/
    //toggle records checkboixes
    $('#toggle-records-ids').change(function(){
        var checked = $(this).prop('checked');
        $("[name='bulk_action_records_ids\[\]']").prop('checked', checked);
    });
    //intercept submit buttons to set action
    $('#bulk-actions-form button[type=submit]').click(function(){
        $('#bulk-actions-form').attr('action', $(this).data('route'));
        let confirmText = $(this).data('confirm');
        return confirmText ? confirm(confirmText) : true;
    });
    /* submit bulk action */
    $('#bulk-actions-form').submit(function(){
        var checkedRecoords = $("[name='bulk_action_records_ids\[\]']:checked");
        if(checkedRecoords.length == 0) {
            $('#bulk-warning').collapse('show');
            return false;
        } else {
            var ids = [];
            checkedRecoords.each(function(){
                ids.push($(this).val());
            });
            $('#bulk_action_records_ids').val(ids.join('|'));
            return true;
        }
    });
});
