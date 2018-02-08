#!/bin/sh
#
# Simple script that dumps the database scheme to SQL and XML-files.
#
# Author: Anders Lövgren
# Date:   2010-05-17
#

declare dbname

function dump_local()
{
    mysqldump --login-path=local -d    $dbname | sed s%'AUTO_INCREMENT=\([0-9]*\)'%'AUTO_INCREMENT=1'%g     > openexam.sql
    mysqldump --login-path=local -d -X $dbname | sed s%'Auto_increment="\([0-9]*\)"'%'Auto_increment="1"'%g > openexam.xml
}

function dump_login()
{
    mysqldump -u root -p -d    $dbname | sed s%'AUTO_INCREMENT=\([0-9]*\)'%'AUTO_INCREMENT=1'%g     > openexam.sql
    mysqldump -u root -p -d -X $dbname | sed s%'Auto_increment="\([0-9]*\)"'%'Auto_increment="1"'%g > openexam.xml
}

if [ -z "$1" ]; then
    login="local"
else 
    login="$1"
fi

if [ -z "$2" ]; then
    dbname="openexam2"
else
    dbname="$2"
fi

case "$login" in
    local)
        dump_local
        ;;
    login)
        dump_login
        ;;
esac
