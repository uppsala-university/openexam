#!/bin/sh
# 
# Result directory maintenance script.
#
# Author: Anders Lövgren
# Date:   2016-04-20

scrdir="/var/www/localhost/apps/openexam-phalcon-svn/phalcon-mvc/script"

# Cleanup old result files:
/usr/bin/php ${scrdir}/openexam.php --result --delete --days=30

# Generate new result files:
/usr/bin/php ${scrdir}/openexam.php --result --create --days=14
