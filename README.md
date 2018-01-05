# OpenExam Online

OpenExam is an enterprise grade platform for electronic exams dating back to 2010. The online 
version (second generation) provides an web based user interface for teacher to compose exams and 
correct results and for students to conduct exams and retrieve their results. It comes with a 
complete web service API making it easy to integrate with external systems.

The 2.x (second generation) of this system is built on top of the [Phalcon MVC](https://phalconphp.com/en/) 
framework that in addition to being fast, also provides some advanced features utilized by this
project. No client installation is required system end-users, a decent web browser is enough for both 
teachers or students (thus its name openexam-online).

## License

Released under GNU General Public License, version 2 (GPL2).

## Forks and derived work

You are free to fork this project and modify at will, but any derived work must retain original 
license headers in source code and new files added must also be released under GPL2. Any project 
derived from this code base should chose their own unique project name to avoid confusion.

## Requirements

The system requirements are more or less LAMP (Linux, Apache, MySQL and PHP). Most other RDBMS 
supported by PDO should work direct or after implementing the database adapter interface.

For production its recommended to use some cache method (Redis/Xcache) and setting up the system
to run behind a load-balancer (IPVS/Keepalive) using fastCGI (PHP-FPM). See INSTALL and docs in 
the source code for more information.

## Features

The system comes with many features that can be turned on/off or customized by editing the 
configuration files. The best way to explore them is to download the image files from the project 
page (for running inside a virtual machine).

## Employees

Users having the teacher role can create exams. For each exams, other roles can be granted to
co-workers granting them permission to perform specific tasks (contributors, invigilators or
decoders). Each question can have one or more correctors, by default the contributor adding a
question become its corrector unless changes by the exam creator.

* Teachers administrates their own exams which yields zero administration for system managers.
* Automatic grant the teacher role (yielding permission to create exams) to authenticated employees based on LDAP-attributes or membership.
* Support for centralized or distributed collaboration on an exam.
* Export to external tools for answer correction/cheat detection or analyze of exam results.
* Exams can be locked to specific locations. Once opened by a student, it can't be accessed from another computer.

## Administrators

People that is setting up this system has some some features to play with to customize the 
installation to suite their needs. For runtime administration the impersonate feature is useful
as it provides a way to see the exact same view as the end-user.

* Support for sharding (spreading a single database table over multiple servers).
* Audit of all changes in the database state (can be customized) with different destination types (i.e. SQL or HTTP).
* Advanced database and directory service cache (cache on read for all data source).
* Classified logging with multiple targets (file, web or syslog).
* Impersonation as one of the system user for administration.
* Designed for web cluster deployment with nearly parallell scaling due to minimized database I/O.

## Integrators

These are a couple of system features that can be useful for integrating OpenExam Online with
authentication sources, directory services or external systems. The web services might also be
used by end-users for their custom tasks.

* Web services (SOAP, REST and AJAX) making it easy to integrate with external systems or create custom tools and clients.
* Multiple catalog services can be plugged in. Internal catalog service for SAML-authentication.
* Authenticator stack supporting SSO (i.e. SWAMID or CAS) or selecting login service.
* Tasks gives access to system classes for periodical or long time running processes.
* Custom roles for service integration.

## Logotype

The logotype is owned by the OpenExam project that also claims the rights to restrict its 
usage. 

In general the logotype can be freely used without permission, except for your own 
projects including projects forked from https://github.com/openexam. 

Usage as the <u>site logotype</u> is restrained to the official project page or sites managed by 
the OpenExam project.

## Project page

For more information visit [The OpenExam Project](http://openexam.io) page.

