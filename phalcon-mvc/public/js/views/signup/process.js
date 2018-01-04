/* global baseURL */

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
// File:    process.js
// Created: 2015-03-27 11:48:51
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

// 
// Support for processing signup request.
// 

$(document).ready(function () {
    var signup = (function (tasks) {

        // 
        // Private helper functions:
        // 
        var _finished = function (obj, msg) {
            var status = obj.find("div.exam-status");
            if (msg !== undefined) {
                status.append(" [" + msg + "]");
            } else {
                status.append(" Success!");
            }
            status.removeClass('wait');
            status.addClass('done');

            // 
            // Redirect automatic on last response:
            // 
            if (--tasks === 0) {
                $('#next').show();
                setTimeout(function () {
                    $('#next').trigger('click');
                }, 3000);
            }
        };
        
        var _starting = function (obj) {
            var status = obj.find("div.exam-status");
            status.removeClass('none');
            status.addClass('wait');
        };

        // 
        // The signup object:
        // 
        return {
            // 
            // Grant teacher role.
            // 
            grant: function (obj) {
                _starting(obj);
                ajax(baseURL + 'ajax/signup/insert', {id: ''}, function (data) {
                    if (data === false) {
                        _finished(obj, "This role has already been granted");
                    } else {
                        _finished(obj);
                    }
                });
            },
            // 
            // Clone exam for teacher.
            // 
            clone: function (obj, exam) {
                _starting(obj);
                ajax(baseURL + 'ajax/signup/teacher', {id: exam}, function (data) {
                    if (data === false) {
                        _finished(obj, 'This exam has already been copied');
                    } else {
                        _finished(obj);
                    }
                });
            },
            // 
            // Register student on exam.
            // 
            register: function (obj, exam) {
                _starting(obj);
                ajax(baseURL + 'ajax/signup/student', {id: exam}, function (data) {
                    if (data === false) {
                        _finished(obj, 'Already subscribed on this exam');
                    } else {
                        _finished(obj);
                    }
                });
            }
        };
    }($('div.exam-content-box').length));

    $('div.exam-content-box').each(function () {
        if ($(this).hasClass('register')) {
            signup.register($(this), $(this).attr('exam'));
        } else if ($(this).hasClass('clone')) {
            signup.clone($(this), $(this).attr('exam'));
        } else if ($(this).hasClass('grant')) {
            signup.grant($(this));
        }
    });
});
