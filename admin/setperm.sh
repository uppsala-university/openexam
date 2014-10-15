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

# These directories should be writable by the web server:
for d in cache logs schemas/soap; do
  if [ -d $root/$d ]; then
    setfacl -m u:$user:rwx $root/$d
  fi
done

# These files contains secrets and should not be world readable:
for f in app/config/config.def; do
  if [ -f $root/$f ]; then
    setfacl -m u:$user:r $root/$f
    chmod 640 $f
  fi
done
