/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    supermenu.js
// Created: 2015-02-17 02:22:48
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$(document).ready(function () {
    $('#login-menu,#help-menu,#tools-menu > li,#tasks-menu > li').bind('mouseover', openSubMenu);
    $('#login-menu,#help-menu,#tools-menu > li,#tasks-menu > li').bind('mouseout', closeSubMenu);

    function openSubMenu() {
        $(this).find('ul').css('visibility', 'visible');
    }

    function closeSubMenu() {
        $(this).find('ul').css('visibility', 'hidden');
    }

});