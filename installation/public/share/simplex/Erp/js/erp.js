/**
* ERP javascript functions
**/

$(document).ready(function(){
    /**
    * Sidebar state (open/close) handler on hamburger click
    **/
    $('#sidebar-toggle').click(function(){
        var className = 'closed';
        $('#sidebar').toggleClass(className);
        $('#sidebar-toggle').toggleClass('is-active');
        $('main').toggleClass('wide');
        var sideBarClosed = $('#sidebar').hasClass(className) ? true : false;
        setAreaCookie(area,  'sideBarClosed', sideBarClosed)
    });
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
        return confirm($(this).data('confirm'));
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
