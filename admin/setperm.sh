#!/bin/sh
#
# Fix permission on database, cache and configure files.
#
# Author: Anders Lövgren
# Date:   2014-10-15

# Enable for script debug:
# set -x

# Current directory:
cwd="$(dirname $0)"

# The phalcon directory:
root="$(realpath $cwd/../phalcon-mvc)"

# Web server user:
user="apache"

# These directories/files should be writable by the web server:
for d in cache logs schemas/soap; do
  if [ -d $root/$d -o -h $root/$d ]; then
    find $root/$d -type d | while read d; do setfacl -m u:$user:rwx "$d"; done
    find $root/$d -type f | while read f; do setfacl -m u:$user:rw  "$f"; done
  fi
done

# These files contains secrets and should not be world readable:
for f in app/config/config.def app/config/catalog.def; do
  if [ -f $root/$f ]; then
    setfacl -m u:$user:r $root/$f
    chmod 640 $root/$f
  fi
done
