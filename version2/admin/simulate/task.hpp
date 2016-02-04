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
// File:   task.hpp
// Date:   3 Feb 2016
// --------------------------------------------------------------------------
//

#ifndef TASK_HPP
#define TASK_HPP

#include <string>

class Options;

class Task
{
    friend class Options;

public:
    //
    // Runtime model.
    //
    enum Mode { Natural, Torture, Custom };

    //
    // Output level.
    //
    enum Level { Debug, Info, Notice, Error };

    Task();
    Task(std::string input);

    void Scan(std::string input);

    static void * Start(void *arg);     // Task runner.
    void Start();                       // Task runner.

    bool Output(Level level) const;     // Check requested output level.
    void Output(Level level, const std::string &message) const;

private:
    std::string target;     // Target server URL (defaults to this app on localhost).
    Mode mode;              // Runtime mode.
    int exam;               // Use exam ID.
    int sleep;              // Second to sleep between server requests.
    int duration;           // Duration of simulation.
    std::string session;    // Use cookie string for authentication.
    std::string student;    // Run simulation as student user.
    bool read;              // Generate answer read load.
    bool write;             // Generate answer write load.
    int  verbose;           // Be more verbose.
    bool debug;             // Print debug information.
    bool dry_run;           // Just print what's going to be done.
    bool quiet;             // Be quiet.
};

#endif // TASK_HPP
