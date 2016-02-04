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
// File:   output.cpp
// Date:   4 Feb 2016
// --------------------------------------------------------------------------
//

#include "output.hpp"

#include <iostream>

#ifndef PREFIX_DEBUG
#define PREFIX_DEBUG  "(d)"
#endif
#ifndef PREFIX_INFO
#define PREFIX_INFO   "(i)"
#endif
#ifndef PREFIX_NOTICE
#define PREFIX_NOTICE "(!)"
#endif
#ifndef PREFIX_ERROR
#define PREFIX_ERROR  "(-)"
#endif

Output * Output::instance = 0;

//
// Object member metods:
//
Output::Output()
    : quiet(false), debug(false), verbose(0)
{
    if(pthread_mutex_init(&lock, 0) != 0) {
        perror("pthread_mutex_init");
    }
}

Output::Output(bool quiet, bool debug, int verbose)
    : quiet(quiet), debug(debug), verbose(verbose)
{
    if(pthread_mutex_init(&lock, 0) != 0) {
        perror("pthread_mutex_init");
    }
}

Output::~Output()
{
    if(pthread_mutex_destroy(&lock) != 0) {
        perror("pthread_mutex_destroy");
    }
}

void Output::Debug(const std::string &message, int) const
{
    if(debug && !quiet) {
        Message(PREFIX_DEBUG, message, std::cout);
    }
}

void Output::Info(const std::string &message, int) const
{
    if(verbose > 1 && !quiet) {
        Message(PREFIX_INFO, message, std::cout);
    }
}

void Output::Notice(const std::string &message, int) const
{
    if(verbose && !quiet) {
        Message(PREFIX_NOTICE, message, std::cout);
    }
}

void Output::Error(const std::string &message, int) const
{
    Message(PREFIX_ERROR, message, std::cerr);
}

void Output::Message(Output::Verbosity verbosity, const std::string &message) const
{
    switch(verbosity) {
    case LevelDebug:
        Debug(message, 1);
        break;
    case LevelInfo:
        Info(message, 1);
        break;
    case LevelNotice:
        Notice(message, 1);
        break;
    case LevelError:
        Error(message, 1);
        break;
    }
}

//
// This method is the one that actually output content to console.
//
void Output::Message(const std::string &prefix, const std::string &message, std::ostream &out) const
{
    if(pthread_mutex_lock(&lock) != 0) {
        perror("pthread_mutex_lock");
        return;
    }

    out << "[" << time(0) << ":" << pthread_self() << "] " << prefix << " " << message << std::endl;

    if(pthread_mutex_unlock(&lock) != 0) {
        perror("pthread_mutex_unlock");
        return;
    }
}

//
// Static functions:
//
Output *Output::Instance()
{
    if(!instance) {
        instance = new Output();
    }
    return instance;
}

void Output::SetInstance(Output *newins)
{
    if(instance) {
        delete instance;
    }
    instance = newins;
}

void Output::Debug(const std::string &message)
{
    Instance()->Debug(message, 1);
}

void Output::Info(const std::string &message)
{
    Instance()->Info(message, 1);
}

void Output::Notice(const std::string &message)
{
    Instance()->Notice(message, 1);
}

void Output::Error(const std::string &message)
{
    Instance()->Error(message, 1);
}
