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
#include "output.hpp"

Task::Task()
    : observer(0), status(Scheduled), mode(Custom), sleep(10), duration(300), dry_run(false)
{
    target = "http://localhost/openexam";
}

Task::Task(std::string input)
    : observer(0), status(Scheduled), mode(Custom), sleep(10), duration(300), dry_run(false)
{
    target = "http://localhost/openexam";
    Scan(input);
}

//
// Scan additional task options from string.
//
void Task::Scan(std::string input)
{
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
        } else if(option.key == "dry-run") {
            dry_run = true;
        }
    }
}

//
// The thread start function.
//
void *Task::Start(void *arg)
{
    Task *task = static_cast<Task *>(arg);
    task->Start();
    return task;
}

//
// Start task.
//
void Task::Start()
{
    int endtime = time(0) + duration;

    SetStatus(Starting, "Starting");

    while(time(0) < endtime && status != Stopped) {
        SetStatus(Running, "Running");
        if(sleep) {
            SetStatus(Sleeping, "Sleeping");
            ::sleep(sleep);
        }
    }

    if(status == Stopped) {
        SetStatus(Cancelled, "Cancelled");
    } else {
        SetStatus(Finished, "Finished");
    }
}

//
// Get object representation.
//
std::string Task::ToString() const
{
    std::ostringstream ss;

    std::boolalpha(ss);

    ss << "{student="  << student
       << ",session="  << session
       << ",exam="     << exam
       << ",mode="     << mode
       << ",read="     << read
       << ",write="    << write
       << ",status="   << status
       << ",sleep="    << sleep
       << ",duration=" << duration
       << ",target="   << target
       << "}";

    return ss.str();
}

std::string Task::GetActivity() const
{
    switch(status) {
    case Cancelled:
        return "cancelled";
    case Finished:
        return "finished";
    case Running:
        return "running";
    case Scheduled:
        return "scheduled";
    case Sleeping:
        return "sleeping";
    case Stopped:
        return "stopped";
    case Starting:
        return "starting";
    default:
        return "unknown";
    }
}

void Task::SetStatus(Task::Status status)
{
    this->status = status;
    OnStatusChange();
}

void Task::SetStatus(Task::Status status, const std::string &message)
{
    this->status = status;
    OnStatusChange();
    Output::Debug(message);
}
