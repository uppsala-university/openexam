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
// File:   result.cpp
// Date:   4 Feb 2016
// --------------------------------------------------------------------------
//

#include "result.hpp"

#include <utility>
#include <limits>

Result::Result()
{
}

//
// Compute mean read/write values based on all accounted transfer sizes.
//
void Result::Compute()
{
    if(stats.find(0) == stats.end()) {
        stats.insert(std::make_pair(0, Transfer()));
    }

    for(Iterator it = stats.begin(); it != stats.end(); ++it) {
        if(it->first == 0) {
            continue;
        }

        Transfer &ta = stats[0];
        Transfer &tc = it->second;

        ta.read.Merge(tc.read);
        ta.write.Merge(tc.write);
    }
}

//
// Merge this object into result.
//
void Result::Merge(Result &result) const
{
    for(ConstIterator it = stats.begin(); it != stats.end(); ++it) {
        // TODO: fix me
    }
}

//
// Account an request record in this object.
//
void Result::Account(int size, Result::What what, const Result::Record &record)
{
    if(what == Read) {
        record.Merge(stats[size].read);
    } else {
        record.Merge(stats[size].write);
    }
}

//
// Return const reference to all statistics.
//
const Result::Statistics & Result::GetStatistics() const
{
    return stats;
}

//
// Return const reference to statistics of size. Use 0 to
// get avarage.
//
const Result::Transfer & Result::GetStatistics(int size) const
{
    return stats.find(size)->second;
}

//
// Return const reference to statistics of size and type.
// Use 0 to get avarage.
//
const Result::Record &Result::GetStatistics(int size, Result::What what) const
{
    if(what == Read) {
        return stats.find(size)->second.read;
    } else {
        return stats.find(size)->second.write;
    }
}

//
// Return true if statistics exist.
//
bool Result::HasStatistics(int size) const
{
    return stats.find(size) != stats.end();
}

//
// Return true if statistics exist.
//
bool Result::HasStatistics(int size, Result::What) const
{
    return stats.find(size) != stats.end(); // read/write always defined.
}

Result::Record::Record()
    : tmin(std::numeric_limits<int>::max()),
      tmax(std::numeric_limits<int>::min()),
      time(0), count(0), failed(0), bytes(0), mean(0)
{
}

//
// Merge this record into argument record.
//
void Result::Record::Merge(Result::Record &record) const
{
    if(tmin < record.tmin) {
        record.tmin = tmin;
    }
    if(tmax > record.tmax) {
        record.tmax = tmax;
    }

    record.time   += time;
    record.count  += count;
    record.failed += failed;
    record.bytes  += bytes;

    record.mean = record.time / record.count;
}
