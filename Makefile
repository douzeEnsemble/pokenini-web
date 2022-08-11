# Executables (local)
DOCKER_COMP = docker-compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP_CONT) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        = help build up start down logs sh composer vendor sf cc deploy

## —— 🎵 🐳 The Symfony-docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

install: ## Install requirements
install: build

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	docker-compose build

start: ## Start the project
	docker-compose up -d

stop: ## Stop the project
	docker-compose down --remove-orphans

sh: ## Connect to the PHP FPM container
	@$(PHP_CONT) sh

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

cc: c=c:c ## Clear the cache
cc: sf


## —— Tests 🧪 ———————————————————————————————————————————————————————————————
tests: ## Execute all tests
tests: phpstan phpunit

phpstan: ## Execute phpstan analyse
	@$(PHP_CONT) vendor/bin/phpstan analyse

phpunit: ## Execute unit test
	@$(PHP_CONT) bin/phpunit


## —— Quality 👌 ———————————————————————————————————————————————————————————————
quality: ## Execute all quality analyses
quality: phpcs phpmd psalm

phpcs: ## Execute phpcs
	@$(PHP_CONT) vendor/bin/phpcs
phpcbf: ## Execute phpcbf (code beautifier) /!\ This could edit your code
	@$(PHP_CONT) vendor/bin/phpcbf

phpmd: ## Execute phpmd
	@$(PHP_CONT) vendor/bin/phpmd src,tests text ruleset.xml

psalm: ## Execute psalm
	@$(PHP_CONT) vendor/bin/psalm --show-info=true


## —— Deployment 🚀 ————————————————————————————————————————————————————————————————
deploy: ## Deployment
	rm -Rf ~/tmp/deploy/pokenini-web
	mkdir -p ~/tmp/deploy/pokenini-web
	heroku git:clone -a pokenini-web ~/tmp/deploy/pokenini-web/heroku
	git clone git@github.com:RenaudDouze/pokenini-web.git ~/tmp/deploy/pokenini-web/project
	rm -Rf ~/tmp/deploy/pokenini-web/project/.git
	cp -R ~/tmp/deploy/pokenini-web/project/* ~/tmp/deploy/pokenini-web/heroku/
	cd ~/tmp/deploy/pokenini-web/heroku; \
        git add --all; \
		git commit --allow-empty -m "Deployment"; \
		git push heroku main
	rm -Rf ~/tmp/deploy/pokenini-web

