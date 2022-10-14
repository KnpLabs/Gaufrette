#!/bin/sh

set -o allexport # export all defined variables

source .env # load dev parameters
#
#
#DIR=$(dirname $0)/..
#
#cd $DIR

make require-all
composer install
vendor/bin/phpunit
