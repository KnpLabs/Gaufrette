PHP_VERSION ?= 7.2

.PHONY: dev
dev:
	cp .env.dist .env

.PHONY: build
build:
	docker-compose build php${PHP_VERSION}

.PHONY: install-deps
install-deps:
	docker/run-task php${PHP_VERSION} composer install

.PHONY: tests
tests:
	docker/run-task php${PHP_VERSION} bin/tests

.PHONY: clear-deps
clear-deps:
	rm -rf vendor/ composer.lock

.PHONY: php-cs-compare
php-cs-compare:
	docker/run-task php${PHP_VERSION} vendor/bin/php-cs-fixer fix \
		--diff \
		--dry-run \
		--show-progress=none \
		--verbose

.PHONY: php-cs-fix
php-cs-fix:
	docker/run-task php${PHP_VERSION} vendor/bin/php-cs-fixer fix

remove-phpspec:
	rm spec/Gaufrette/Adapter/AsyncAwsS3Spec.php
	rm spec/Gaufrette/Adapter/AwsS3Spec.php
	rm spec/Gaufrette/Adapter/OpenCloudSpec.php
	rm spec/Gaufrette/Adapter/GoogleCloudStorageSpec.php
	rm spec/Gaufrette/Adapter/DoctrineDbalSpec.php
	rm spec/Gaufrette/Adapter/FlysystemSpec.php
	rm -r spec/Gaufrette/Adapter/AzureBlobStorage
	rm spec/Gaufrette/Adapter/GridFSSpec.php
	rm spec/Gaufrette/Adapter/PhpseclibSftpSpec.php

require-all-legacy:
	composer require --no-update \
		aws/aws-sdk-php:^3.158 \
		rackspace/php-opencloud:^1.9.2 \
		google/apiclient:^1.1.3 \
		doctrine/dbal:^2.3 \
		league/flysystem:^1.0 \
		microsoft/azure-storage-blob:^1.0 \
		phpseclib/phpseclib:^2.0 \
		mongodb/mongodb:^1.1 \
		symfony/event-dispatcher:^4.4


require-all: require-all-legacy
	composer require --no-update async-aws/simple-s3:^0.1.1

