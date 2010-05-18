#!/bin/sh
#
# Simple script that dumps the database scheme to SQL and XML-files.
#
# Author: Anders Lövgren
# Date:   2010-05-17
#

mysqldump -u root -p -d openexam2 > openexam.sql
mysqldump -u root -p -d -X openexam2 > openexam.xml
