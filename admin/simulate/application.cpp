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
// File:   application.cpp
// Date:   4 Feb 2016
// --------------------------------------------------------------------------
//

#include <fstream>

#include "application.hpp"
#include "options.hpp"

Application::Application(int argc, char **argv)
{
    options = new Options;
    options->Parse(argc, argv);
}

Application::~Application()
{
    delete options;
}

//
// Process current options.
//
void Application::Process()
{
    if(options->HasTaskFile()) {
        Process(options->GetTaskFile());
    } else {
        Process(options->GetTask());
    }
}

//
// Process single task.
//
void Application::Process(const Task &task) const
{
    Task tt(task);
    Process(&tt);
}

//
// Process single task.
//
void Application::Process(Task *task) const
{
    task->Start();
}

//
// Process task definition file.
//
void Application::Process(const std::string &file)
{
    std::ifstream stream(file, std::ios::in);

    if(!stream) {
        throw Exception("Failed open " + file);
    } else {
        Process(stream);
    }
}

//
// Process task definition stream.
//
void Application::Process(std::ifstream &stream)
{
    std::string line;
    pthread_t thread;
    int index = 0;

    const Options::Start &start = options->GetStart();

    while(std::getline(stream, line)) {
        Task *task = new Task(options->GetTask());
        task->Scan(line);

        if(pthread_create(&thread, 0, Task::Start, task) != 0) {
            throw Exception("Failed create thread");
        } else {
            tasks[thread] = task;
        }
    }

    for(TaskIterator it = tasks.begin(); it != tasks.end(); ++it) {
        pthread_join(it->first, 0);
        delete it->second;
    }
}
