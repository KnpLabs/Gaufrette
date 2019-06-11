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
