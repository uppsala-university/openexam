/* global baseURL */

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    chart.js
// Created: 2016-04-28 19:08:24
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$(document).ready(function () {

    // 
    // The organization chart object:
    // 
    var orgChart = (function () {
        var data = {}, options = {
            responsive: true,
            legend: {
                display: false,
                position: 'bottom'
            }
        };

        // 
        // Create color object with RGB composer.
        // 
        function createColor() {
            return {
                r: 64 + Math.floor(Math.random() * 255) % 160,
                g: 64 + Math.floor(Math.random() * 255) % 160,
                b: 64 + Math.floor(Math.random() * 255) % 160,
                compose: function (offset) {
                    return "rgb("
                            + (this.r + offset) + ","
                            + (this.g + offset) + ","
                            + (this.b + offset) + ")";
                }
            };
        }

        // 
        // Set organization title, but don't trigger change event unless it's
        // for an division.
        // 
        function setTitle(title, division) {
            if (division) {
                $("#statistics-organization").text(title).trigger('change', title);
            } else {
                $("#statistics-organization").text(title);
            }
        }

        return {
            setData: function (content) {
                var colors = {bc: [], hc: [], fc: []};
                var duser = [], dexam = [], dname = [];

                for (var i = 0; i < content.children.length; ++i) {
                    var division = content.children[i];
                    var color = createColor();

                    colors.bc.push(color.compose(0));
                    colors.hc.push(color.compose(16));

                    duser.push(division.users);
                    dexam.push(division.exams);
                    dname.push(division.name);
                }

                this.data = {
                    labels: dname,
                    datasets: [
                        {
                            data: dexam,
                            backgroundColor: colors.bc,
                            hoverBackgroundColor: colors.hc
                        },
                        {
                            data: duser,
                            backgroundColor: colors.bc,
                            hoverBackgroundColor: colors.hc
                        }
                    ]
                };

                setTitle(content.name, false);
            },
            render: function (context) {

                var pieChart = new Chart(context, {
                    type: 'pie',
                    data: this.data,
                    options: options
                });

                $("#organization-pie-chart").click(function (evt) {
                    var activePoints = pieChart.getElementAtEvent(evt);
                    if (activePoints !== undefined && activePoints[0] !== undefined) {
                        var division = activePoints[0]._model.label;
                        setTitle(division, true);
                    }
                });
            }
        };
    }());

    (function () {

        // 
        // Send request for organization data and render pie chart.
        //
        $.ajax({
            type: "GET",
            url: baseURL + 'utility/statistics/organization',
            success: function (resp) {
                var context = $("#organization-pie-chart");
                var content = JSON.parse(resp);

                orgChart.setData(content);
                orgChart.render(context);
            }
        });

    }());
});
