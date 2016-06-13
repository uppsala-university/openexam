// Copyright (C) 2016  Anders Lövgren, BMC Computing Department, Uppsala University
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// --------------------------------------------------------------------------
// Author: Anders Lövgren <anders.lovgren@bmc.uu.se>
//
// File:   options.hpp
// Date:   3 Feb 2016
// --------------------------------------------------------------------------
//

#ifndef OPTIONS_HPP
#define OPTIONS_HPP

#include <iostream>
#include <string>
#include <vector>

#include "task.hpp"

//
// Single command line option.
//
struct Option
{
    Option(std::string args)
    {
        std::string::size_type pos;

        if((pos = args.find('=')) != std::string::npos) {
            key = args.substr(0, pos);
            val = args.substr(pos + 1);
        } else {
            key = args;
            val = "";
        }
    }

    bool HasArgument() const
    {
        return val.length() != 0;
    }

    operator int() const
    {
        return atoi(val.c_str());
    }

    std::string key;
    std::string val;
};

//
// Command line options.
//
class Options
{
public:
    //
    // Client startup options.
    //
    struct Start
    {
        int num;    // Number of client to start.
        int wait;   // Wait period in seconds.

        Start() : num(30), wait(1) {}
        Start(int num, int wait) : num(num), wait(wait) {}
        Start(std::string opt);
    };

    void Parse(int argc, char **argv);
    void Usage() const;
    void Version() const;

    const Task  & GetTask() const;
    const Start & GetStart() const;

    const std::string & GetTaskFile() const;
    bool HasTaskFile() const;

    std::ostream & Dump(std::ostream &out) const;

private:
    std::string program;    // Program name.
    std::string version;    // Program version.
    std::string tasks;      // The task definition file (when multi threaded).
    std::string result;     // Simulation result file.

    Task task;              // Common task options.
    Start start;            // Client startup options.

    int  verbose;           // Be more verbose.
    bool debug;             // Print debug information.
    bool quiet;             // Be quiet.
};

inline const Task & Options::GetTask() const
{
    return task;
}

inline const Options::Start & Options::GetStart() const
{
    return start;
}

inline const std::string &Options::GetTaskFile() const
{
    return tasks;
}

inline bool Options::HasTaskFile() const
{
    return tasks.length() != 0;
}

#endif // OPTIONS_HPP
