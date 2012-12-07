#!/bin/bash

sudo apt-get install libssh2-1-dev libssh2-php &> /dev/null
pecl install mongo &> /dev/null
touch .interactive
(pecl install -f ssh2 < .interactive) &> /dev/null
#echo "extension=mongo.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
#echo "extension=ssh2.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
cp tests/Gaufrette/Functional/adapters/DoctrineDbal.php.dist tests/Gaufrette/Functional/adapters/DoctrineDbal.php -f
