#!/bin/sh
#
# Fix permission on database and configure files.
#
# Author: Anders Lövgren
# Date:   2010-02-22

confdir=conf

chmod 640 $confdir/config.inc $confdir/database.conf
setfacl -m u:apache:r $confdir/config.inc $confdir/database.conf
