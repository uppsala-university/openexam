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
#include "application.hpp"

using namespace std;

int main(int argc, char **argv)
{
    try {
        Application app(argc, argv);
        app.Process();
    }
    catch(const Application::Exception &exception)
    {
        std::cerr << exception.message << std::endl;
    }
    catch (const std::exception &exception)
    {
        std::cerr << exception.what() << std::endl;
    }
    catch(...)
    {
        std::cerr << "unknown exception\n";
    }

    return 0;
}
