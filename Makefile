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
