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
// File:   result.hpp
// Date:   4 Feb 2016
// --------------------------------------------------------------------------
//

#ifndef RESULT_HPP
#define RESULT_HPP

#include <map>

//
// Collect result.
//
class Result
{
public:
    //
    // Request record.
    //
    struct Record
    {
        float tmin;     // Request time minimum.
        float tmax;     // Request time maximum.
        float time;     // Total time.
        int count;      // Total requests.
        int failed;     // Number of failed requests.
        int bytes;      // Bytes transfered.
        float mean;     // Request mean value (time/count).

        Record();
        void Merge(Record &record) const;   // Merge this into record.
    };

    //
    // Read/write statistics.
    //
    struct Transfer
    {
        Record read;
        Record write;
    };

    //
    // Size vs. transfer rate statistics:
    //
    typedef std::map<int, Transfer> Statistics;
    typedef std::map<int, Transfer>::iterator Iterator;
    typedef std::map<int, Transfer>::const_iterator ConstIterator;

    enum What { Read, Write };

    Result();

    void Compute();                         // Computer avarage etc.
    void Merge(Result &result) const;       // Merge this into result.
    void Account(int size, What what, const Record &record);

    const Statistics & GetStatistics() const;
    const Transfer   & GetStatistics(int size) const;
    const Record     & GetStatistics(int size, What what) const;

    bool HasStatistics(int size) const;
    bool HasStatistics(int size, What what) const;

private:
    Statistics stats;   // Collected statistics.
};

#endif // RESULT_HPP
