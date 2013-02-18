#!/bin/bash

sudo apt-get update -qq
sudo apt-get install -qq libssh2-1-dev libssh2-php
sudo pecl install mongo &> /dev/null
touch .interactive
(sudo pecl install -f ssh2-beta < .interactive) &> /dev/null

cp tests/Gaufrette/Functional/adapters/DoctrineDbal.php.dist tests/Gaufrette/Functional/adapters/DoctrineDbal.php -f
