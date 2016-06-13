#!/bin/sh
#
# Check code compatibility. 
# 
# Download phpcompatinfo.phar from http://php5.laurent-laville.org/compatinfo/ 
# and make it executable in PATH.
# 
# Author: Anders Lövgren
# Date:   2016-03-17

compatinfo=$(which phpcompatinfo 2> /dev/null)
sourceroot="$(dirname $(dirname $(dirname $(realpath $0))))/phalcon-mvc"

if [ -z "$compatinfo" ]; then
  echo "$0: No phpcompatinfo found in PATH"
  exit 1
fi

if ! [ -d "$sourceroot" ]; then
  echo "$0: Source root not found (looking for phalcon-mvc)"
  exit 1
fi

$compatinfo analyser:run $sourceroot
