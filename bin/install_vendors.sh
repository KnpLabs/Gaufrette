#!/bin/bash

curl -s http://getcomposer.org/installer | php -- --quiet
php composer.phar install --dev
cp tests/Gaufrette/Functional/adapters/DoctrineDbal.php.dist tests/Gaufrette/Functional/adapters/DoctrineDbal.php -f
