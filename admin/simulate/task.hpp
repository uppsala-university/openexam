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
// File:   task.hpp
// Date:   3 Feb 2016
// --------------------------------------------------------------------------
//

#ifndef TASK_HPP
#define TASK_HPP

#include <string>

class Options;
class TaskObserver;

class Task
{
    friend class Options;

public:
    //
    // Runtime model.
    //
    enum Mode { Natural, Torture, Custom };

    //
    // Output level.
    //
    enum Level { Debug, Info, Notice, Error };

    //
    // Task status.
    //
    enum Status { Scheduled, Starting, Running, Sleeping, Finished, Cancelled, Stopped };

    Task();
    Task(std::string input);

    void Scan(std::string input);       // Merge in options from string.

    static void * Start(void *arg);     // Start task.
    void Start();                       // Run task.
    void Stop();                        // Stop task.

    std::string ToString() const;       // To string operator.

    //
    // Status information.
    //
    Status GetStatus() const;
    Mode GetMode() const;

    int GetExam() const;
    int GetSleep() const;
    int GetDuration() const;

    const std::string & GetTarget() const;
    const std::string & GetSession() const;
    const std::string & GetStudent() const;
    std::string GetActivity() const;

    bool IsReading() const;
    bool IsWriting() const;
    bool IsDryRun() const;

    //
    // Observers:
    //
    void setTaskObserver(const TaskObserver *observer);

private:
    void SetStatus(Status status);
    void SetStatus(Status status, const std::string &message);
    void OnStatusChange() const;

    const TaskObserver *observer;

    Status status;          // Task status.
    Mode mode;              // Runtime mode.
    int exam;               // Use exam ID.
    int sleep;              // Second to sleep between server requests.
    int duration;           // Duration of simulation.
    std::string target;     // Target server URL (defaults to this app on localhost).
    std::string session;    // Use cookie string for authentication.
    std::string student;    // Run simulation as student user.
    bool read;              // Generate answer read load.
    bool write;             // Generate answer write load.
    bool dry_run;           // Just print what's going to be done.
};

class TaskObserver
{
public:
    virtual void OnStatusChange(const Task *task) const = 0;
};

inline void Task::Stop()
{
    this->SetStatus(Stopped, "Stopping");
}

inline Task::Status Task::GetStatus() const
{
    return status;
}

inline Task::Mode Task::GetMode() const
{
    return mode;
}

inline int Task::GetExam() const
{
    return exam;
}

inline int Task::GetSleep() const
{
    return sleep;
}

inline int Task::GetDuration() const
{
    return duration;
}

inline const std::string &Task::GetTarget() const
{
    return target;
}

inline const std::string &Task::GetSession() const
{
    return session;
}

inline const std::string &Task::GetStudent() const
{
    return student;
}

inline bool Task::IsReading() const
{
    return read;
}

inline bool Task::IsWriting() const
{
    return write;
}

inline bool Task::IsDryRun() const
{
    return dry_run;
}

inline void Task::setTaskObserver(const TaskObserver *observer)
{
    this->observer = observer;
}

inline void Task::OnStatusChange() const
{
    if(observer) {
        observer->OnStatusChange(this);
    }

}

#endif // TASK_HPP
