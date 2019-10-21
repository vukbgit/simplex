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
});
