# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
ifeq (${CI}, true)
PHP_CONT = $(DOCKER_COMP) exec -T php
else
PHP_CONT = $(DOCKER_COMP) exec php
endif

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP_CONT) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help certs build start up install stop sh composer vendor sf cc tests phpunit testsunit testsfunctional testsbrowser quality phpcs phpcbf phpmd psalm phpstan measures coverage htmlcoverage infection setenv

## —— 🎵 🐳 The Symfony-docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Directories and files 🐳 ————————————————————————————————————————————————————————————————
KEY_FILE := ./docker/apache/ssl/cert-key.pem
CERT_FILE := ./docker/apache/ssl/cert.pem

certs: ## Create ssl files
certs: docker/apache/ssl/cert.pem
	@if [ ! -e $(KEY_FILE) ] || [ ! -e $(CERT_FILE) ]; then \
	mkcert \
		-key-file ./docker/apache/ssl/cert-key.pem \
		-cert-file ./docker/apache/ssl/cert.pem \
		localhost 127.0.0.1 ::1
	fi

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: setenv ## Builds the Docker images
	${DOCKER_COMP} build

start: install up vendor cc ## ## Start the project

up: setenv ## Up the project
	${DOCKER_COMP} up --wait

install: ## Install requirements
install: setenv certs

stop: setenv ## Stop the project
	${DOCKER_COMP} down --remove-orphans

sh: setenv ## Connect to the PHP FPM container
	@$(PHP_CONT) bash

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: setenv ## Install vendors according to the current composer.lock file
	@$(COMPOSER) install --prefer-dist --no-progress --no-interaction
	@$(COMPOSER) clear-cache

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: setenv ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: setenv ## Clear the cache
	@$(SYMFONY) cache:clear --env=dev
	@$(SYMFONY) cache:clear --env=test

## —— Tests 🧪 ———————————————————————————————————————————————————————————————
tests: ## Execute all tests
tests: phpunit

phpunit: setenv ## Execute tests with PHPUnit
	@$(PHP_CONT) bin/phpunit

testsunit: setenv ## Execute unit tests
	@$(PHP_CONT) bin/phpunit tests/Unit

testsfunctional: setenv ## Execute functional tests
	@$(PHP_CONT) bin/phpunit tests/Functional

testsbrowser: setenv ## Execute browser tests
	@$(PHP_CONT) bin/phpunit tests/Browser

## —— Quality 👌 ———————————————————————————————————————————————————————————————
quality: ## Execute all quality analyses
quality: phpcs phpmd psalm phpstan

phpcs: setenv ## Execute phpcs
	@$(PHP_CONT) vendor/bin/phpcs
phpcbf: setenv ## Execute phpcbf (code beautifier) /!\ This could edit your code
	@$(PHP_CONT) vendor/bin/phpcbf

phpmd: setenv ## Execute phpmd
	@$(PHP_CONT) vendor/bin/phpmd src,tests text ruleset.xml

psalm: setenv ## Execute psalm
	@$(PHP_CONT) vendor/bin/psalm --show-info=false

phpstan: setenv## Execute phpstan analyse
	@$(PHP_CONT) vendor/bin/phpstan analyse --memory-limit=-1

## —— Measures 📏 ———————————————————————————————————————————————————————————————
measures: ## Execute all measures tools
measures: coverage infection

coverage: setenv## Execute PHPUnit Coverage to check the score
	$(DOCKER_COMP) exec \
		-e XDEBUG_MODE=coverage -T php \
		php bin/phpunit --exclude-group="browser-testing" \
		--coverage-clover=coverage.xml
	@$(PHP_CONT) php tests/tools/coverage.php coverage.xml 100 true

htmlcoverage: setenv ## Execute PHPUnit Coverage in HTML
	$(DOCKER_COMP) exec \
		-e XDEBUG_MODE=coverage -T php \
		php bin/phpunit --exclude-group="browser-testing" \
		--coverage-html=tests/coverage
html=tests/coverage

infection: setenv ## Execute Infection (Mutation testing)
	@$(PHP) vendor/bin/infection --threads=4 --show-mutations \
		--min-msi=100 --min-covered-msi=100 \
		--logger-html='tests/mutation/index.html'

## —— Environement 🛠️ ———————————————————————————————————————————————————————————————
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)
ENV_FILE := .env

.ONESHELL:
setenv: ## Set docker environnements variables
	@if ! grep -q '^USER_ID=' $(ENV_FILE); then \
		echo "USER_ID=$(USER_ID)" >> $(ENV_FILE); \
	else \
		sed -i 's|^USER_ID=.*|USER_ID=$(USER_ID)|' $(ENV_FILE); \
	fi
	@if ! grep -q '^GROUP_ID=' $(ENV_FILE); then \
		echo "GROUP_ID=$(GROUP_ID)" >> $(ENV_FILE); \
	else \
		sed -i 's|^GROUP_ID=.*|GROUP_ID=$(GROUP_ID)|' $(ENV_FILE); \
	fi