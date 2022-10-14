.DEFAULT_GOAL := help

PHP_VERSION ?= 7.2
export ROOT_DIR=${PWD}

#
### DOCKER
# --------
#

.PHONY: dev
docker.dev: ## Prepare the env file before running docker
	cp .env.dist .env

.PHONY: build
docker.build: ## Build the PHP docker image
	docker-compose build php${PHP_VERSION}

.PHONY: install-deps
docker.deps: ## Install dependencies
	docker/run-task php${PHP_VERSION} composer install

.PHONY: install-all-deps
docker.all-deps: docker.deps ## Install dependencies
	docker/run-task php${PHP_VERSION} composer require --no-update \
		aws/aws-sdk-php:^3.158 \
		google/apiclient:^2.12 \
		doctrine/dbal:^3.4 \
		league/flysystem:^1.0 \
		microsoft/azure-storage-blob:^1.0 \
		phpseclib/phpseclib:^2.0 \
		mongodb/mongodb:^1.1 \
		async-aws/simple-s3:^0.1.1

.PHONY: tests
docker.tests: ## Run tests
	docker/run-task php${PHP_VERSION} bin/tests

.PHONY: php-cs-compare
docker.php-cs-compare: ## Run CS fixer (dry run)
	docker/run-task php${PHP_VERSION} vendor/bin/php-cs-fixer fix \
		--diff \
		--dry-run \
		--show-progress=none \
		--verbose

.PHONY: php-cs-fix
docker.php-cs-fix: ## Run CS fixer
	docker/run-task php${PHP_VERSION} vendor/bin/php-cs-fixer fix

#
### LOCAL TASKS
# -------
#

remove-phpspec: ## Remove adapter specs (allows you to run test suite without adapters deps)
	rm spec/Gaufrette/Adapter/AsyncAwsS3Spec.php
	rm spec/Gaufrette/Adapter/AwsS3Spec.php
	rm spec/Gaufrette/Adapter/GoogleCloudStorageSpec.php
	rm spec/Gaufrette/Adapter/DoctrineDbalSpec.php
	rm spec/Gaufrette/Adapter/FlysystemSpec.php
	rm -r spec/Gaufrette/Adapter/AzureBlobStorage
	rm spec/Gaufrette/Adapter/GridFSSpec.php
	rm spec/Gaufrette/Adapter/PhpseclibSftpSpec.php

require-all-legacy: # kept for compatibility with the old CI config, to be removed at some point
	composer require --no-update \
		aws/aws-sdk-php:^3.158 \
		google/apiclient:^2.12 \
		doctrine/dbal:^3.4 \
		league/flysystem:^1.0 \
		microsoft/azure-storage-blob:^1.0 \
		phpseclib/phpseclib:^2.0 \
		mongodb/mongodb:^1.1


require-all: require-all-legacy ## Install all dependencies for adapters
	composer require --no-update async-aws/simple-s3:^1.0

.PHONY: bc-check
bc-check: ## Check for backward compatibility change
	docker run -v ${ROOT_DIR}:/app --rm nyholm/roave-bc-check

.PHONY: clear
clear: ## Remove not versioned files
	rm -rf vendor/ composer.lock

test.phpstan: ## Run phpstan analysis
	php vendor/bin/phpstan analyze --memory-limit 1G

#
### OTHERS
# --------
#

help: SHELL=/bin/bash
help: ## Dislay this help
	@IFS=$$'\n'; for line in `grep -h -E '^[a-zA-Z_#-]+:?.*?## .*$$' $(MAKEFILE_LIST)`; do if [ "$${line:0:2}" = "##" ]; then \
	echo $$line | awk 'BEGIN {FS = "## "}; {printf "\n\033[33m%s\033[0m\n", $$2}'; else \
	echo $$line | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'; fi; \
	done; unset IFS;
	@echo ""
	@echo "Hint: use 'make command PHP_VERSION=X.X' to specify the PHP version with docker commands."
.PHONY: help
