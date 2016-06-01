
// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    counters.js
// Created: 2016-05-27 22:04:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

// 
// The counters object contains one or more counter objects. Each counter object is
// built up on one or more timelines. Each object got a label and description. 
// 
// The parent context is passed into each child object. 
// 

// 
// Colors.
// 
var colors = {
    _curr: 0, _colors: [
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
}

// 
// The timeline object:
// 
function Timeline(key, parent) {
    var _data = [], _max = 20, _parent = parent, _key = key, _colors = colors.next()

    this.label = "";
    this.descr = "";

    this.setKeys = function (keys) {
        this.label = keys.label;
        this.descr = keys.descr;
    }

    this.setData = function (data, index, chart) {
        var insert = data.map(function (d) {
            return d.data[_parent][_key];
        });
        if (_data.length === 0) {
            _data = insert;
        } else {
            _data.shift();
            _data.push(insert);
        }
        if (_data.length > _max) {
            _data = _data.slice(0, _data.length - _max)
        }
        if (chart !== undefined) {
            chart.data.datasets[index].data[_max - 1] = insert.shift();
        }
    };

    this.getData = function (index) {
        return _data[index];
    }

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
            data: _data
        };
    };

    this.setSize = function (max) {
        _max = max;
    };

    this.getSize = function () {
        return _max;
    };
}

// 
// The counter object.
// 
function Counter(key, parent) {
    var _key = key, _parent = parent, _timelines = [], _context, _chart;

    this.label = "";
    this.descr = "";

    function create(label, descr) {
        _parent.append("<div class='counter " + _key + "'><div class='header'><button class='btn btn-default counter " + key + "' title='" + descr + "'>" + label + "</button></div><hr/><canvas class='" + _key + "'/></div>");
        _context = _parent.find("canvas." + _key);

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
                    var timeline = new Timeline(key, _key);
                    timeline.setKeys(keys[key]);
                    _timelines.push(timeline);

            }
        }
    };

    this.setData = function (data) {
        for (var i = 0; i < _timelines.length; ++i) {
            _timelines[i].setData(data, i, _chart);
        }
    };

    this.setSize = function (max) {
        for (var i = 0; i < _timelines.length; ++i) {
            _timelines[i].setSize(max);
        }
    };

    this.update = function () {
        if (_chart === undefined) {
            create(this.label, this.descr);
        } else {
            _chart.update();
        }
    }

    this.remove = function () {
        _chart.destroy();
        _parent.find("div").remove();
        _timelines = [];
    };
}

// 
// The mnitor (counter container) object.
// 
counters = (function () {
    var _url, _limit = 20, _interval = 5, _source = 0, _monitor, _context, _counters = {}, _timer = null, _label, _descr;

    // 
    // Parse keys data. Set label, descr and create counters.
    // 
    function setKeys(keys) {
        for (var key in keys) {
            switch (key) {
                case 'label':
                    _label = keys[key];
                    break;
                case 'descr':
                    _descr = keys[key];
                    break;
                default:
                    add(key, keys[key]);
                    break;
            }
        }
    }

    // 
    // Set data for all counters.
    // 
    function setData(data) {
        for (var key in _counters) {
            _counters[key].setData(data);
            _counters[key].update();
        }
    }

    // 
    // Add counter.
    // 
    function add(name, keys) {
        var counter = new Counter(name, _context);
        counter.setKeys(keys);
        counter.setSize(_limit);
        _counters[name] = counter;
    }

    // 
    // Called on initialize (open).
    // 
    function create(content) {
        setKeys(content.keys);
        setData(content.data);
    }

    // 
    // Called on update.
    // 
    function update(content) {
        setData(content.data);
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

            var url = _url + '/' + _monitor + '?limit=' + _limit + '&keys=1&source=' + _source;
            fetch(url, create);
        },
        // 
        // Close current monitor.
        // 
        close: function () {
            if (_timer) {
                this.stop();
            }

            for (var k in _counters) {
                _counters[k].remove();
            }
            _counters = {};

            if (_context) {
                _context.innerHTML = "";
            }
        },
        reopen: function () {
            this.close();
            this.open(_monitor, _context);
        },
        // 
        // Start all counters.
        // 
        start: function () {
            _timer = setInterval(function () {
                var url = _url + '/' + _monitor + '?limit=1&source=' + _source;
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
        // Set update interval.
        // 
        setInterval: function (interval) {
            if (_interval !== interval) {
                _interval = interval;
                this.restart();
            }
        },
        setSource: function (name) {
            if (_source !== name) {
                _source = name;
                this.reopen();
                this.start();
            }
        }
    }
}());
