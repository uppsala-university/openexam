
/* global baseURL */

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    diagnostics.js
// Created: 2016-06-08 22:14:06
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

var diagnostics = (function () {
    var _timer = null, _details = 0, _interval = 30;

    // 
    // Show authenticator status details.
    // 
    function auth(parent, content) {
        var html = "<div class='title'>Authentication</div>";

        for (var s in content) {
            html += "<div><span class='head'> Service " + s + "</span></div>";
            for (var p in content[s]) {
                html += "<div><span class='sect'> Plugin " + p.toUpperCase() + "</span></div>";
                if (content[s][p].working) {
                    html += "<div><span class='item'> Working: " + content[s][p].working + "</span></div>";
                } else {
                    html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Working: " + content[s][p].working + "</span></div>";
                    failure();
                }
                for (var h in content[s][p].online) {
                    if (content[s][p].online[h]) {
                        html += "<div><span class='item'> Online: " + h + "</span></div>";
                    } else {
                        html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Offline: " + h + "</span></div>";
                        failure();
                    }
                }
            }
        }

        parent.append("<div class='card'>" + html + "</div>");
    }

    // 
    // Show database status details.
    // 
    function database(parent, content) {
        var html = "<div class='title'>Database</div>";

        for (var s in content) {
            html += "<div><span class='head'> Connection " + s + "</span></div>";

            if (content[s].working) {
                html += "<div><span class='item'> Working: " + content[s].working + "</span></div>";
            } else {
                html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Working: " + content[s].working + "</span></div>";
                failure();
            }
            for (var h in content[s].online) {
                if (content[s].online[h]) {
                    html += "<div><span class='item'> Online: " + h + "</span></div>";
                } else {
                    html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Offline: " + h + "</span></div>";
                    failure();
                }
            }
        }

        parent.append("<div class='card'>" + html + "</div>");
    }

    // 
    // Show catalog service details.
    // 
    function catalog(parent, content) {
        var html = "<div class='title'>Catalog</div>";

        for (var d in content) {
            html += "<div><span class='head'> User domain " + d + "</span></div>";
            for (var s in content[d]) {
                html += "<div><span class='sect'> Service " + s.toUpperCase() + "</span></div>";
                if (content[d][s].working) {
                    html += "<div><span class='item'> Working: " + content[d][s].working + "</span></div>";
                } else {
                    html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Working: " + content[d][s].working + "</span></div>";
                    failure();
                }
                for (var h in content[d][s].online) {
                    if (content[d][s].online[h]) {
                        html += "<div><span class='item'> Online: " + h + "</span></div>";
                    } else {
                        html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Offline: " + h + "</span></div>";
                        failure();
                    }
                }
            }
        }

        parent.append("<div class='card'>" + html + "</div>");
    }

    // 
    // Show web server status details.
    // 
    function web(parent, content) {
        var html = "<div class='title'>Web Server</div>";
        var head = {frontend: "Frontend", backend: "Backend", balancer: "Load Balancer"};

        for (var s in content) {
            html += "<div><span class='head'>" + head[s] + "</span></div>";

            if (content[s].working) {
                html += "<div><span class='item'> Working: " + content[s].working + "</span></div>";
            } else {
                html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Working: " + content[s].working + "</span></div>";
                failure();
            }
            for (var h in content[s].online) {
                if (content[s].online[h]) {
                    html += "<div><span class='item'> Online: " + h + "</span></div>";
                } else {
                    html += "<div><span class='item failed'><i class='fa fa-arrow-right'></i> Offline: " + h + "</span></div>";
                    failure();
                }
            }
        }

        parent.append("<div class='card'>" + html + "</div>");
    }

    // 
    // Called on failure.
    // 
    function failure() {
        if (_timer !== null) {
            stop_update();
            summary(false);
        }
    }

    // 
    // Show test summary as success or failure depending on input.
    // 
    function summary(success) {
        $("#summary").show();

        if (success) {
            $("button.details").html("Show Details");
            $("#status-success").show();
            $("#status-failure").hide();
        } else {
            $("#status-success").hide();
            $("#status-failure").show();
            failure();
        }
    }

    // 
    // Show test details.
    // 
    function details(content) {
        var context = $("#details").removeClass('hide');
        $("button.details").html("Hide Details");

        context.empty();        // Cleanup any child elements

        if (content.auth !== undefined) {
            auth(context, content.auth);
        }
        if (content.database !== undefined) {
            database(context, content.database);
        }
        if (content.catalog !== undefined) {
            catalog(context, content.catalog);
        }
        if (content.web !== undefined) {
            web(context, content.web);
        }
    }

    // 
    // Display fetched data. We are either display the status (failure or
    // success) or test details.
    // 
    function display(content) {
        if (typeof (content.status) === "boolean") {
            summary(content.status);
        } else {
            details(content.status);
        }
    }

    // 
    // Fetch data using requested details level (0 => dynamic, 4 => full):
    // 
    function fetch() {
        $.ajax({
            type: "GET",
            url: baseURL + '/utility/monitor/health?details=' + _details,
            success: function (resp) {
                display(JSON.parse(resp));
            },
            error: function (err) {
                $("#ajax_loader").hide();

                var target = $(".mbox");
                target.html(err.responseText);
                target.removeClass('hide');
            }
        });
    }

    // 
    // Start automatic update:
    // 
    function start_update() {
        fetch(0);
        $("button.timer").html("Pause Update");
        _timer = setInterval(function () {
            fetch();
        }, _interval * 1000);
    }

    // 
    // Stop automatic update:
    // 
    function stop_update() {
        clearInterval(_timer);
        $("button.timer").html("Resume Update");
        _timer = null;
    }

    // 
    // Return public interface:
    // 
    return {
        // 
        // Start automatic updates.
        // 
        start: function () {
            summary(true);
            start_update();
        },
        // 
        // Stop automatic updates.
        // 
        stop: function () {
            stop_update();
        },
        // 
        // Immediate refresh content.
        // 
        update: function () {
            fetch();
        },
        // 
        // Check if timer is running.
        // 
        isRunning: function () {
            return _timer !== null;
        },
        // 
        // Set update interval in sec.
        // 
        setInterval: function (interval) {
            _interval = interval;
        },
        // 
        // Enable/disable details mode.
        // 
        useDetails: function (enable) {
            if (enable) {
                _details = 4;
            } else {
                _details = 0;
            }
        },
        // 
        // Get details mode.
        // 
        hasDetails: function () {
            return _details !== 0;
        }
    };

    // 
    // Always start in automatic update mode:
    // 
    start_update();
}());
        