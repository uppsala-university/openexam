#!/bin/sh
#
# Simple script that dumps the database scheme to SQL and XML-files.
#
# Author: Anders Lövgren
# Date:   2010-05-17
#

mysqldump -u root -p -d openexam | sed s%'AUTO_INCREMENT=\([0-9]*\)'%'AUTO_INCREMENT=1'%g > openexam.sql
mysqldump -u root -p -d -X openexam | sed s%'Auto_increment="\([0-9]*\)"'%'Auto_increment="1"'%g > openexam.xml
