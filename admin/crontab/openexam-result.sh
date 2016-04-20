#!/bin/sh
# 
# Result directory maintenance script.
#
# Author: Anders Lövgren
# Date:   2016-04-20

script="/var/www/localhost/apps/openexam-phalcon-svn/phalcon-mvc/script/openexam.php"

# Cleanup old result files:
/usr/bin/php $script --result --delete --days=30

# Generate new result files:
/usr/bin/php $script --result --create --days=14
