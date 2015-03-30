
// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    supermenu.js
// Created: 2015-02-17 02:22:48
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$(document).ready(function () {
    $('.login-menu > li').bind('mouseover', openSubMenu);
    $('.login-menu > li').bind('mouseout', closeSubMenu);

    function openSubMenu() {
        $(this).find('ul').css('visibility', 'visible');
    }

    function closeSubMenu() {
        $(this).find('ul').css('visibility', 'hidden');
    }

});