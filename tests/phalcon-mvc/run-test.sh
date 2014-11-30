#!/bin/sh
#
# Run all unit test. Remember to run 'php phalcon-mvc/script/unittest.php --setup' 
# to insert sample data in database.
#
# Author: Anders Lövgren
# Date:   2014-11-30

cd $(dirname $0)
php /usr/bin/phpunit --bootstrap bootstrap.php --configuration configuration.xml --group globalization,model,core,security,database,render
