#!/bin/bash

sudo apt-get install libssh2-1-dev libssh2-php &> /dev/null
sudo pecl install mongo &> /dev/null
touch .interactive
(sudo pecl install -f ssh2 < .interactive) &> /dev/null

cp tests/Gaufrette/Functional/adapters/DoctrineDbal.php.dist tests/Gaufrette/Functional/adapters/DoctrineDbal.php -f
