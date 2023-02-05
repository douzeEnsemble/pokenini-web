# Executables (local)
DOCKER_COMP = docker-compose

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
.PHONY        : help build up start down logs sh composer vendor sf cc tests quality measures

## —— 🎵 🐳 The Symfony-docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

install: ## Install requirements
install: build start waitup stop

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	docker-compose build

start: ## Start the project
	docker-compose up -d

stop: ## Stop the project
	docker-compose down --remove-orphans

sh: ## Connect to the PHP FPM container
	@$(PHP_CONT) sh

waitup:
	while ! $(PHP_CONT) /usr/local/bin/docker-healthcheck; do \
		sleep 1; \
	done
	echo 'Wait is over'

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: ## Clear the cache
	@$(SYMFONY) cache:clear --env=dev
	@$(SYMFONY) cache:clear --env=test

## —— Tests 🧪 ———————————————————————————————————————————————————————————————
tests: ## Execute all tests
tests: phpunit

phpunit: ## Execute tests with PHPUnit
	@$(PHP_CONT) bin/phpunit

testsunit: ## Execute unit tests
	@$(PHP_CONT) bin/phpunit tests/Unit

testsfunctional: ## Execute functional tests
	@$(PHP_CONT) bin/phpunit tests/Functional

testsbrowser: ## Execute browser tests
	@$(PHP_CONT) bin/phpunit tests/Browser

## —— Quality 👌 ———————————————————————————————————————————————————————————————
quality: ## Execute all quality analyses
quality: phpcs phpmd psalm phpstan

phpcs: ## Execute phpcs
	@$(PHP_CONT) vendor/bin/phpcs
phpcbf: ## Execute phpcbf (code beautifier) /!\ This could edit your code
	@$(PHP_CONT) vendor/bin/phpcbf

phpmd: ## Execute phpmd
	@$(PHP_CONT) vendor/bin/phpmd src,tests text ruleset.xml

psalm: ## Execute psalm
	@$(PHP_CONT) vendor/bin/psalm --show-info=false

phpstan: ## Execute phpstan analyse
	@$(PHP_CONT) vendor/bin/phpstan analyse --memory-limit=-1

## —— Measures 📏 ———————————————————————————————————————————————————————————————
measures: ## Execute all measures tools
measures: coverage infection

coverage: ## Execute PHPUnit Coverage to check the score
	$(DOCKER_COMP) exec -e XDEBUG_MODE=coverage -T php php bin/phpunit --exclude-group="browser-testing" --coverage-clover=coverage.xml
	@$(PHP_CONT) php tests/tools/coverage.php coverage.xml 100 true

htmlcoverage: ## Execute PHPUnit Coverage in HTML
	$(DOCKER_COMP) exec -e XDEBUG_MODE=coverage -T php php bin/phpunit --exclude-group="browser-testing" --coverage-html=tests/coverage
html=tests/coverage

infection: ## Execute Infection (Mutation testing)
	@$(PHP) vendor/bin/infection --threads=4 --show-mutations --min-msi=100 --min-covered-msi=100 --logger-html='tests/mutation/index.html'