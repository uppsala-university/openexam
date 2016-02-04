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
#include <sstream>
#include <unistd.h>

#include "application.hpp"
#include "options.hpp"
#include "output.hpp"

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
    Process(new Task(task));
}

//
// Process single task.
//
void Application::Process(Task *task) const
{
    task->setTaskObserver(this);
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
    }

    Process(stream);
}

//
// Process task definition stream.
//
void Application::Process(std::ifstream &stream)
{
    Start(stream);
    Join();
}

//
// Start all task from input stream.
//
void Application::Start(std::ifstream &stream)
{
    std::string line;
    int index = 0;

    const Options::Start &start = options->GetStart();

    while(std::getline(stream, line)) {
        if(Start(line)) {
            if((index != 0) && (index % start.num == 0)) {
                ::sleep(start.wait);
            }
        }
    }
}

//
// Start task defined by input string (read from file). The task is
// merged with common task options before started. The defined task
// options have precedence.
//
bool Application::Start(const std::string &line)
{
    Task *task = new Task(options->GetTask());
    task->Scan(line);

    return Start(task);
}

//
// Start task in own thread. If successful, the task is inserted in the
// list of running tasks.
//
bool Application::Start(Task *task)
{
    Thread thread;

    if(pthread_create(&thread, 0, Task::Start, task) != 0) {
        perror("pthread_create");
        return false;
    }

    tasks[thread] = task;
    task->setTaskObserver(this);

    return true;
}

//
// Join all running threads (tasks).
//
void Application::Join()
{
    for(TaskIterator it = tasks.begin(); it != tasks.end(); ++it) {
        Join(it->first, it->second);
    }
}

//
// Join thread and collect results.
//
void Application::Join(Application::Thread thread, Task *task)
{
    if(pthread_join(thread, 0) != 0) {
        perror("pthread_join");
    }

    tasks.erase(tasks.find(thread));
}

//
// Collect result from finished task.
//
void Application::Collect(const Task *task)
{

}

void Application::Status(std::string message) const
{
    std::ostringstream ss;
    ss << tasks.size() << " active tasks: " << message;
    Output::Info(ss.str());
}

void Application::OnStatusChange(const Task *task) const
{
    if(task->GetStatus() == Task::Starting) {
        Status(task->GetStudent() + " [" + task->GetActivity() + "]" + "\tparams=" + task->ToString());
    } else {
        Status(task->GetStudent() + " [" + task->GetActivity() + "]");
    }
}
