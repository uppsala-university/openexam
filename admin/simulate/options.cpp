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
// File:   options.cpp
// Date:   3 Feb 2016
// --------------------------------------------------------------------------
//

#include <iostream>

#include "options.hpp"
#include "output.hpp"

Options::Start::Start(std::string opt)
{
    std::string::size_type pos;

    if((pos = opt.find(':')) != std::string::npos) {
        num  = atoi(opt.substr(0, pos).c_str());
        wait = atoi(opt.substr(pos).c_str());
    } else {
        num  = atoi(opt.c_str());
        wait = 1;
    }
}

//
// Parse command line options.
//
void Options::Parse(int argc, char **argv)
{
    program = argv[0];
    version = "1.0";

    for(int i = 1; i < argc; ++i) {
        Option option(argv[i]);

        if(option.key == "--tasks") {
            tasks = option.val;
        } else if(option.key == "--result") {
            result = option.val;
        } else if(option.key == "--target") {
            task.target = option.val;
        } else if(option.key == "--exam") {
            task.exam = option;
        } else if(option.key == "--session") {
            task.session = option.val;
        } else if(option.key == "--student") {
            task.student = option.val;
        } else if(option.key == "--natural") {
            task.mode = Task::Natural;
        } else if(option.key == "--torture") {
            task.mode = Task::Torture;
        } else if(option.key == "--read") {
            task.read = true;
        } else if(option.key == "--write") {
            task.write = true;
        } else if(option.key == "--start") {
            start = Start(option.val);
        }  else if(option.key == "--sleep") {
            task.sleep = option;
        } else if(option.key == "--duration") {
            task.duration = option;
        } else if(option.key == "--help" ||
                  option.key == "-h") {
            Usage();
            exit(0);
        } else if(option.key == "--version" ||
                  option.key == "-V") {
            Version();
            exit(0);
        } else if(option.key == "--defaults" ||
                  option.key == "-D") {
            Dump(std::cout);
            exit(0);
        } else if(option.key == "--dry-run") {
            task.dry_run = true;
        } else if(option.key == "--verbose" ||
                  option.key == "-v") {
            verbose++;
        } else if(option.key == "--debug" ||
                  option.key == "-d") {
            debug = true;
        } else if(option.key == "--quiet" ||
                  option.key == "-q") {
            quiet = true;
        } else {
            std::cerr << program << ": Unknown option '" << option.key << "', see --help\n";
            exit(1);
        }
    }

    if(debug) {
        Dump(std::cout);
    }
    if(quiet) {
        verbose = 0;
        debug = 0;
    }

    Output *newins = new Output(quiet, debug, verbose);
    Output::SetInstance(newins);

}

void Options::Usage() const
{
    std::cout
            << program << " - Run exam simulation\n"
            << "\n"
            << "Usage:\n"
            << "  " << program << " --tasks=clients.def [--start=30:2] [options...]\n"
            << "  " << program << " --exam=num --session=id [--natural|--torture|--read|--write] [options...]\n"
            << "\n"
            << "Options:\n"
            << "  --tasks=file        : Task definition file (runs multi-threaded)\n"
            << "  --result=file       : Simulation result file.\n"
            << "  --target=url        : Target server URL (defaults to this app on localhost).\n"
            << "  --exam=id           : Use exam ID.\n"
            << "  --session=str       : Use cookie string for authentication.\n"
            << "  --student=user      : Run simulation as student user.\n"
            << "  --natural           : Run normal user interface simulation.\n"
            << "  --torture           : Run in torture mode (no sleep).\n"
            << "  --read              : Generate answer read load.\n"
            << "  --write             : Generate answer write load.\n"
            << "  --start=num:wait    : Client startup options.\n"
            << "  --sleep=sec         : Second to sleep between server requests.\n"
            << "  --duration=sec      : Duration of simulation.\n"
            << "  --help,-h           : Show this casual help.\n"
            << "  --version,-V        : Show program version.\n"
            << "  --defaults,-D       : Show default options.\n"
            << "  --verbose,-v        : Be more verbose.\n"
            << "  --debug,-d          : Print debug information\n"
            << "  --dry-run           : Just print whats going to be done.\n"
            << "  --quite,-q          : Be quiet.\n"
            << "\n"
            << "Examples:\n"
            << "  # Run multi-threaded simulation (start 20 clients with 3 sec interval):\n"
            << "  " << program << " --tasks=file.def --start=20:3\n"
            << "\n"
            << "  # Run natural single client simulation for default duration:\n"
            << "  " << program << " --natural --exam=999 --session=abcd1234 --target=http://localhost/openexam\n"
            << "\n"
            << "Copyright (C) 2016 Anders Lövgren, BMC Computing Department, Uppsala University\n";

}

void Options::Version() const
{
    std::cout << program << " " << version << std::endl;
}

std::ostream & Options::Dump(std::ostream &out) const
{
    return out
            << "\nRuntime -> {\n"
            << "   Tasks:\t"    << tasks << std::endl
            << "   Result:\t"   << result << std::endl
            << "   Start:\t"    << start.num << " (num), " << start.wait << " (wait)" << std::endl
            << "   Sleep:\t"    << task.sleep << std::endl
            << "   Duration:\t" << task.duration << std::endl
            << "}\n"
            << "\nTask -> {\n"
            << "   Target:\t"   << task.target << std::endl
            << "   Exam:\t"     << task.exam << std::endl
            << "   Session:\t"  << task.session << std::endl
            << "   Student:\t"  << task.student << std::endl
            << "   Mode:\t"     << task.mode << std::endl
            << "   Read:\t"     << task.read << std::endl
            << "   Write:\t"    << task.write << std::endl
            << "   Dry Run:\t"  << task.dry_run << std::endl
            << "}\n"
            << "\nCommon -> {\n"
            << "   Verbose:\t"  << verbose << std::endl
            << "   Debug:\t"    << debug << std::endl
            << "   Quiet:\t"    << quiet << std::endl
            << "}\n\n";
}
