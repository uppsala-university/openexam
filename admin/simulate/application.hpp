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
// File:   application.hpp
// Date:   4 Feb 2016
// --------------------------------------------------------------------------
//

#ifndef APPLICATION_HPP
#define APPLICATION_HPP

#include <string>
#include <map>

class Options;
class Task;

class Application
{
    typedef std::map<pthread_t, Task *> Tasks;
    typedef std::map<pthread_t, Task *>::iterator TaskIterator;

public:
    struct Exception : public std::exception
    {
        Exception(const std::string &message) : message(message) {}
        std::string message;
    };

    Application(int argc, char **argv);
    ~Application();

    void Process();

    void Process(const Task &) const;
    void Process(Task *) const;

    void Process(const std::string &file);
    void Process(std::ifstream &stream);

private:
    Options *options;
    Tasks tasks;
};

#endif // APPLICATION_HPP
