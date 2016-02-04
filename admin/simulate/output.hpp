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
// File:   output.hpp
// Date:   4 Feb 2016
// --------------------------------------------------------------------------
//

#ifndef OUTPUT_HPP
#define OUTPUT_HPP

#include <string>
#include <ostream>

//
// Thread safe console output.
//
class Output
{
public:
    typedef pthread_mutex_t Mutex;

    enum Verbosity { LevelDebug, LevelInfo, LevelNotice, LevelError };

    explicit Output();
    Output(bool quiet, bool debug, int verbose);
    ~Output();

    void SetVerbose(int level = 1);
    void SetQuiet(bool enable = true);
    void SetDebug(bool enable = true);

    int  GetVerbose() const;
    bool IsVerbose() const;
    bool IsQuiet() const;
    bool HasDebug() const;

    void Debug(const std::string &message, int) const;
    void Info(const std::string &message, int) const;
    void Notice(const std::string &message, int) const;
    void Error(const std::string &message, int) const;

    void Message(Verbosity verbosity, const std::string &message) const;

    static Output * Instance();         // Get shared instance.
    static void SetInstance(Output *);  // Set shared instance.

    static void Debug(const std::string &message);
    static void Info(const std::string &message);
    static void Notice(const std::string &message);
    static void Error(const std::string &message);

private:
    void Message(const std::string &prefix, const std::string &message, std::ostream &out) const;

    mutable Mutex lock;

    bool quiet;
    bool debug;
    int verbose;

    static Output *instance;
};

inline void Output::SetQuiet(bool enable)
{
    quiet = enable;
}

inline void Output::SetDebug(bool enable)
{
    debug = enable;
}

inline void Output::SetVerbose(int level)
{
    verbose = level;
}

inline int Output::GetVerbose() const
{
    return verbose;
}

inline bool Output::IsVerbose() const
{
    return verbose > 0;
}

inline bool Output::IsQuiet() const
{
    return quiet;
}

inline bool Output::HasDebug() const
{
    return debug;
}

#endif // OUTPUT_HPP
