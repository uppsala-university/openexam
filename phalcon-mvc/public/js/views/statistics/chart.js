
// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
                var duser = [], dexam = [], dname = []

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
