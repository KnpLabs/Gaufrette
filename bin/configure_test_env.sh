#!/bin/bash

sudo apt-get install -qq libssh2-1-dev libssh2-php
pecl install mongo &> /dev/null
touch .interactive
(pecl install -f ssh2 < .interactive)

cp tests/Gaufrette/Functional/adapters/DoctrineDbal.php.dist tests/Gaufrette/Functional/adapters/DoctrineDbal.php -f
