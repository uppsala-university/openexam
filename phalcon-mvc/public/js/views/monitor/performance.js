
// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    performance.js
// Created: 2016-05-27 22:04:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

// 
// The counters object contains an array of counter objects. Each counter object is
// built up on one or more timelines. Each object got a label and description. 
// 
// The parent context is passed into each child object. The counter class is responsible
// for initialize the drawing canvas.
// 

// 
// Colors.
// 
var colors = {
    _curr: 0,
    _colors: [
        [
            "rgb(255, 64, 0)", "rgb(255, 64, 0)", "rgb(255, 64, 0)",
            "rgb(255, 255, 255)", "rgb(255, 64, 0)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(0, 255, 255)", "rgb(0, 255, 255)", "rgb(0, 255, 255)",
            "rgb(255, 255, 255)", "rgb(0, 255, 255)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(191, 255, 0)", "rgb(191, 255, 0)", "rgb(191, 255, 0)",
            "rgb(255, 255, 255)", "rgb(191, 255, 0)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(128, 255, 0)", "rgb(128, 255, 0)", "rgb(128, 255, 0)",
            "rgb(255, 255, 255)", "rgb(128, 255, 0)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(255, 255, 0)", "rgb(255, 255, 0)", "rgb(255, 255, 0)",
            "rgb(255, 255, 255)", "rgb(255, 255, 0)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(0, 191, 255)", "rgb(0, 191, 255)", "rgb(0, 191, 255)",
            "rgb(255, 255, 255)", "rgb(0, 191, 255)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(255, 128, 0)", "rgb(255, 128, 0)", "rgb(255, 128, 0)",
            "rgb(255, 255, 255)", "rgb(255, 128, 0)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(0, 255, 0)", "rgb(0, 255, 0)", "rgb(0, 255, 0)",
            "rgb(255, 255, 255)", "rgb(0, 255, 0)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(255, 0, 255)", "rgb(255, 0, 255)", "rgb(255, 0, 255)",
            "rgb(255, 255, 255)", "rgb(255, 0, 255)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(0, 255, 191)", "rgb(0, 255, 191)", "rgb(0, 255, 191)",
            "rgb(255, 255, 255)", "rgb(0, 255, 191)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(128, 0, 255)", "rgb(128, 0, 255)", "rgb(128, 0, 255)",
            "rgb(255, 255, 255)", "rgb(128, 0, 255)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(0, 128, 255)", "rgb(0, 128, 255)", "rgb(0, 128, 255)",
            "rgb(255, 255, 255)", "rgb(0, 128, 255)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(191, 0, 255)", "rgb(191, 0, 255)", "rgb(191, 0, 255)",
            "rgb(255, 255, 255)", "rgb(191, 0, 255)", "rgb(192, 255, 255)"
        ],
        [
            "rgb(255, 0, 128)", "rgb(255, 0, 128)", "rgb(255, 0, 128)",
            "rgb(255, 255, 255)", "rgb(255, 0, 128)", "rgb(192, 255, 255)"
        ]
    ],
    next: function () {
        if (this._curr >= this._colors.length) {
            this._curr = 0;
        }
        return this._colors[this._curr++];
    }
};

// 
// Natural numbers sequence generator.
// 
var natnum = (function () {
    var id = 0;
    return function () {
        return ++id;
    };
})();

// 
// The timeline object.
// 
function Timeline(addr, key, parent) {
    var _last, _max = 20, _colors = colors.next(), _delta = false;

    this.label = "";
    this.descr = "";

    // 
    // Apply delta mode on insert array.
    // 
    function delta(insert, last) {
        var length = insert.length, next, prev;

        if (last === undefined) {
            prev = insert[0];
            last = insert[length - 1];
        } else {
            prev = last;
            last = insert[length - 1];
        }

        for (var i = 0; i < length; ++i) {
            next = insert[i];
            insert[i] = insert[i] - prev;
            prev = next;
        }

        return last;
    }

    this.setKeys = function (keys) {
        this.label = keys.label;
        this.descr = keys.descr;
    };

    this.setData = function (data, index, chart) {
        // 
        // Extract data for this timeline (based on key + addr):
        // 
        var insert = data.map(function (d) {
            if (d.addr === addr) {
                return d.data[parent][key];
            }
        }).filter(function (d) {
            return d !== undefined;
        });

        if (_delta) {
            _last = delta(insert, _last);
        }

        var prev = chart.data.datasets[index].data.concat(insert);
        var size = prev.length;

        if (size > _max) {
            prev.splice(0, size - _max);
        }

        chart.data.datasets[index].data = prev;
    };

    this.getDataSet = function () {
        return {
            label: this.label,
            fill: false,
            backgroundColor: _colors[0],
            borderColor: _colors[1],
            pointBorderColor: _colors[2],
            pointBackgroundColor: _colors[3],
            pointHoverBackgroundColor: _colors[4],
            pointHoverBorderColor: _colors[5],
            data: []
        };
    };

    this.setSize = function (max) {
        _max = max;
    };

    this.getSize = function () {
        return _max;
    };

    this.setDelta = function (enable) {
        _delta = enable;
    };
}

// 
// The counter object.
// 
function Counter(addr, key, parent) {
    var _timelines = [], _context, _chart, _unique = natnum();

    this.label = "";
    this.descr = "";

    function create(label, descr) {
        parent.append("<div class='counter " + key + "'><div class='header'><button id='" + _unique + "' class='btn btn-default counter' title='" + descr + "'>" + label + "</button></div><hr/><canvas class='" + key + "' id='" + _unique + "'/><div class='counter-date'></div><div class='counter-host'>" + addr + "</div></div>");
        _context = parent.find("canvas#" + _unique);

        // 
        // On counter clicked:
        // 
        $("button#" + _unique).click(function () {
            counters.setCounter(key);
            counters.reopen();
            counters.start();
        });

        var datasets = [];
        for (var set in _timelines) {
            datasets.push(_timelines[set].getDataSet());
        }

        _chart = new Chart(_context, {
            type: 'line',
            data: {
                labels: new Array(_timelines[0].getSize() - 1),
                datasets: datasets
            },
            options: {}
        });
    }

    function addTimeline(type, keys) {
        var timeline = new Timeline(addr, type, key);
        timeline.setKeys(keys[type]);
        _timelines.push(timeline);
    }

    this.setKeys = function (keys) {
        for (var key in keys) {
            switch (key) {
                case 'label':
                    this.label = keys[key];
                    break;
                case 'descr':
                    this.descr = keys[key];
                    break;
                default:
                    addTimeline(key, keys);
            }
        }

        create(this.label, this.descr);
    };

    this.setData = function (data) {
        for (var i = 0; i < _timelines.length; ++i) {
            _timelines[i].setData(data, i, _chart);
        }
    };

    this.setSize = function (max) {
        _chart.data.labels = new Array(max);
        for (var i = 0; i < _timelines.length; ++i) {
            _timelines[i].setSize(max);
        }
    };

    this.setDelta = function (enable) {
        for (var i = 0; i < _timelines.length; ++i) {
            _timelines[i].setDelta(enable);
        }
    };

    this.setLast = function (date) {
        $(".counter-date").html(date.toString());
    };

    this.update = function () {
        _chart.update();
    };

    this.remove = function () {
        _chart.destroy();
        _timelines = [];
        parent.find("div").remove();
    };
}

// 
// The monitor (counter container) object.
// 
var counters = (function () {
    var _url, _limit = 20, _interval = 5, _source = '', _counter = '', _milestone = '', _monitor, _context, _counters = [], _timer = null, _delta = true, _last;

    // 
    // Check if counters need to be updated.
    // 
    function redraw(data) {
        if (data === undefined) {
            _last = new Date();
            return true;
        }
        if (_last === undefined) {
            _last = new Date(data.time.split(" ").join("T"));
            return true;
        }

        var time = new Date(data.time.split(" ").join("T"));

        if (_last < time) {
            _last = time;
            return true;
        }

        return false;   // No update of chart needed.
    }

    // 
    // Reset internal state.
    // 
    function reset() {
        if (_counters.length > 0) {
            _counters = [];
        }
        if (_context) {
            _context.innerHTML = "";
        }
        if (_last) {
            _last = null;
        }
    }

    // 
    // Add counter.
    // 
    function add(addr, name, keys) {
        var counter = new Counter(addr, name, _context);

        counter.setKeys(keys);
        counter.setSize(_limit);
        counter.setDelta(_delta);
        _counters.push(counter);

        return counter;
    }

    // 
    // Called on initialize (open).
    // 
    function create(content) {
        var keys = content.keys, data = content.data, used = {};

        for (var i = 0; i < data.length; ++i) {
            for (var name in data[i].data) {
                var addr = data[i].addr;
                if (used[addr] === undefined) {
                    used[addr] = {};
                }
                if (used[addr][name] === undefined) {
                    used[addr][name] = add(addr, name, keys[name]);
                }
            }
        }

        for (var i = 0; i < _counters.length; ++i) {
            _counters[i].setData(data);
            _counters[i].update();
        }
    }

    // 
    // Called on update.
    // 
    function update(content) {
        var data = content.data, size = data.length;

        if (redraw(data[size - 1])) {
            for (var i = 0; i < _counters.length; ++i) {
                _counters[i].setData(data);
                _counters[i].update();
                _counters[i].setLast(_last);
            }
        }
    }

    // 
    // Pass response from URL to callback.
    // 
    function fetch(url, callback) {
        $.ajax({
            type: "GET",
            url: url,
            success: function (resp) {
                callback(JSON.parse(resp));
            }
        });
    }

    return {
        // 
        // Open new monitor.
        // 
        open: function (monitor, context) {
            _monitor = monitor;
            _context = context;

            var url = _url + '/' + _monitor + '/' + _counter + '?limit=' + _limit + '&keys=1&source=' + _source + '&milestone=' + _milestone;
            fetch(url, create);
        },
        // 
        // Close current monitor.
        // 
        close: function () {
            if (_timer) {
                this.stop();
            }

            for (var i = 0; i < _counters.length; ++i) {
                _counters[i].remove();
            }

            reset();
        },
        // 
        // Reopen monitor using current settings.
        // 
        reopen: function () {
            this.close();
            this.open(_monitor, _context);
        },
        // 
        // Start all counters.
        // 
        start: function () {
            _timer = setInterval(function () {
                var url = _url + '/' + _monitor + '/' + _counter + '?limit=1&source=' + _source + '&milestone=' + _milestone;
                fetch(url, update);
            }, _interval * 1000);
        },
        // 
        // Stop all counters.
        // 
        stop: function () {
            clearInterval(_timer);
            _timer = null;
        },
        // 
        // Restart all counters.
        // 
        restart: function () {
            this.stop();
            this.start();
        },
        // 
        // Check if timer is running.
        // 
        running: function () {
            return _timer !== null;
        },
        // 
        // Set base URL (excluding the monitor name).
        // 
        setUrl: function (url) {
            _url = url;
        },
        // 
        // Set number of performance events to fetch.
        // 
        setLimit: function (num) {
            _limit = num;
        },
        // 
        // Set delta mode.
        // 
        setDelta: function (enable) {
            if (enable !== undefined) {
                _delta = enable;
            }
        },
        // 
        // Set update interval.
        // 
        setInterval: function (interval) {
            if (_interval !== interval) {
                _interval = interval;
                this.restart();
            }
        },
        // 
        // Set source name.
        // 
        setSource: function (name) {
            if (name === undefined) {
                name = '';
            }
            if (_source !== name) {
                _source = name;
            }
        },
        // 
        // Set counter name.
        // 
        setCounter: function (name) {
            if (name === undefined) {
                name = '';
            }
            if (_counter !== name) {
                _counter = name;
            }
        },
        // 
        // Increment milestone (zoom out).
        // 
        incrementMilestone: function () {
            var
                    accept = ['', 'minute', 'hour', 'day', 'week', 'month', 'year'],
                    index = accept.indexOf(_milestone),
                    endpos = accept.length - 1;

            if (index === endpos) {
                return false;
            } else {
                _milestone = accept[++index];
                return index !== endpos;
            }
        },
        // 
        // Decrement milestone (zoom in).
        // 
        decrementMilestone: function () {
            var
                    accept = ['', 'minute', 'hour', 'day', 'week', 'month', 'year'],
                    index = accept.indexOf(_milestone),
                    endpos = 0;

            if (index === endpos) {
                return false;
            } else {
                _milestone = accept[--index];
                return index !== endpos;
            }
        },
        // 
        // Get current milestone.
        // 
        getMilestone: function() {
            return _milestone;
        }
    };
}());
