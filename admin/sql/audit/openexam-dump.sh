#!/bin/sh
#
# Simple script that dumps the database scheme to SQL and XML-files.
#
# Author: Anders Lövgren
# Date:   2010-05-17
#

function dump_local()
{
    mysqldump --login-path=local -d    openexam2audit | sed s%'AUTO_INCREMENT=\([0-9]*\)'%'AUTO_INCREMENT=1'%g     > openexam-audit.sql
    mysqldump --login-path=local -d -X openexam2audit | sed s%'Auto_increment="\([0-9]*\)"'%'Auto_increment="1"'%g > openexam-audit.xml
}

function dump_login()
{
    mysqldump -u root -p -d    openexam2audit | sed s%'AUTO_INCREMENT=\([0-9]*\)'%'AUTO_INCREMENT=1'%g     > openexam-audit.sql
    mysqldump -u root -p -d -X openexam2audit | sed s%'Auto_increment="\([0-9]*\)"'%'Auto_increment="1"'%g > openexam-audit.xml
}

if [ -z "$1" ]; then
    login="local"
else 
    login="$1"
fi

case "$login" in
    local)
        dump_local
        ;;
    login)
        dump_login
        ;;
esac
