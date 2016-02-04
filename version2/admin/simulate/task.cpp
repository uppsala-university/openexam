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
// File:   task.cpp
// Date:   3 Feb 2016
// --------------------------------------------------------------------------
//

#include <iostream>
#include <sstream>
#include <ctime>
#include <unistd.h>

#include "task.hpp"
#include "options.hpp"

namespace {
    pthread_mutex_t lock = PTHREAD_MUTEX_INITIALIZER;
}

Task::Task()
    : mode(Custom), sleep(10), duration(300),
      verbose(false), debug(false), dry_run(false), quiet(false)
{
    target = "http://localhost/openexam";
}

Task::Task(std::string input)
    : mode(Custom), sleep(10), duration(300),
      verbose(false), debug(false), dry_run(false), quiet(false)
{
    target = "http://localhost/openexam";
    Scan(input);
}

void Task::Scan(std::string input)
{
    std::string::size_type pos;
    std::istringstream ss(input);
    std::string token;

    while(std::getline(ss, token, ',')) {
        Option option(token);

        if(option.key == "target") {
            target = option.val;
        } else if(option.key == "mode") {
            if(option.val == "natural") {
                mode = Natural;
            } else if(option.val == "custom") {
                mode = Custom;
            } else if(option.val == "torture") {
                mode = Torture;
            }
        } else if(option.key == "exam") {
            exam = option;
        } else if(option.key == "sleep") {
            sleep = option;
        } else if(option.key == "duration") {
            duration = option;
        } else if(option.key == "session") {
            session = option.val;
        } else if(option.key == "student") {
            student = option.val;
        } else if(option.key == "read") {
            read = true;
        } else if(option.key == "write") {
            write = true;
        } else if(option.key == "verbose") {
            verbose++;
        } else if(option.key == "debug") {
            debug = true;
        } else if(option.key == "dry-run") {
            dry_run = true;
        } else if(option.key == "quite") {
            quiet = true;
        }
    }

    if(quiet) {
        verbose = 0;
        debug = 0;
    }
}

void *Task::Start(void *arg)
{
    Task *task = static_cast<Task *>(arg);
    task->Start();
    return task;
}

void Task::Start()
{
    int endtime = time(0) + duration;

    Output(Debug, "Starting");
    Output(Debug, session);

    while(time(0) < endtime) {
        Output(Debug, "Running");
        if(sleep) {
            ::sleep(sleep);
        }
    }

    Output(Debug, "Finished");
}

//
// Filter output.
//
bool Task::Output(Level level) const
{
    if(quiet) {
        return level >= Error;
    } else if(level == Debug) {
        return debug;
    } else if(level == Info) {
        return verbose > 1;
    } else  {
        return verbose;
    }
}

//
// Filter output.
//
void Task::Output(Task::Level level, const std::string &message) const
{
    if(pthread_mutex_lock(&lock) != 0) {
        perror("pthread_mutex_lock");
        return;
    }

    if(Output(level)) {
        if(level != Error) {
            std::cout << "[" << pthread_self() << "]: " << message << std::endl;
        } else {
            std::cerr << "[" << pthread_self() << "]: " << message << std::endl;
        }
    }

    if(pthread_mutex_unlock(&lock) != 0) {
        perror("pthread_mutex_unlock");
        return;
    }
}

