# Executables (local)
DOCKER = docker
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console
PHPUNIT  = $(PHP) vendor/bin/phpunit --display-all
DOCKERCOMPOSE_LINTER_CMD = docker run -t --rm -v ${PWD}:/app zavoloklom/dclint:3.1.0-alpine
DOTENV_LINTER_CMD = docker run -t --rm -v ${PWD}:/app -w /app dotenvlinter/dotenv-linter:4.0.0
HADOLINT_CMD = docker run -t --rm -v ${PWD}:/app hadolint/hadolint:v2.14.0-alpine hadolint
EDITORCONFIG_LINTER_CMD = docker run --rm --volume=${PWD}:/check mstruebing/editorconfig-checker:v3.6.0

# Misc
SHELL := /bin/bash
.DEFAULT_GOAL = help

define sequential_runner
@TOOLS="$(1)"; \
LABEL="$(2)"; \
N=0; for t in $$TOOLS; do N=$$((N+1)); done; \
TMPDIR=$$(mktemp -d); \
SPINNER=('⠋' '⠙' '⠹' '⠸' '⠼' '⠴' '⠦' '⠧' '⠇' '⠏'); \
printf "Running $$N $$LABEL sequentially…\n\n"; \
for tool in $$TOOLS; do \
	printf "  \033[2m○\033[0m  %s\n" "$$tool"; \
done; \
for tool in $$TOOLS; do \
	idx=0; \
	for t in $$TOOLS; do \
		[ "$$t" = "$$tool" ] && break; \
		idx=$$((idx + 1)); \
	done; \
	lines_up=$$((N - idx)); \
	( $(MAKE) --no-print-directory $$tool > "$$TMPDIR/$$tool.log" 2>&1; echo $$? > "$$TMPDIR/$$tool.exit" ) & \
	spin_idx=0; \
	while [ ! -f "$$TMPDIR/$$tool.exit" ]; do \
		spin_char=$${SPINNER[$$((spin_idx % 10))]}; \
		printf "\033[%dA\r\033[2K  \033[33m%s\033[0m  %s\033[%dB\r" "$$lines_up" "$$spin_char" "$$tool" "$$lines_up"; \
		spin_idx=$$((spin_idx + 1)); \
		sleep 0.1; \
	done; \
	exit_code=$$(cat "$$TMPDIR/$$tool.exit"); \
	if [ "$$exit_code" -eq 0 ]; then \
		printf "\033[%dA\r\033[2K  \033[32m✔\033[0m  %s\033[%dB\r" "$$lines_up" "$$tool" "$$lines_up"; \
	else \
		printf "\033[%dA\r\033[2K  \033[31m✘\033[0m  %s\033[%dB\r" "$$lines_up" "$$tool" "$$lines_up"; \
		printf "\n"; \
		cat "$$TMPDIR/$$tool.log"; \
		rm -rf "$$TMPDIR"; \
		exit 1; \
	fi; \
done; \
printf "\n"; \
rm -rf "$$TMPDIR"
endef

define parallel_runner
@TOOLS="$(1)"; \
LABEL="$(2)"; \
N=0; for t in $$TOOLS; do N=$$((N+1)); done; \
TMPDIR=$$(mktemp -d); \
SPINNER=('⠋' '⠙' '⠹' '⠸' '⠼' '⠴' '⠦' '⠧' '⠇' '⠏'); \
printf "Launching $$N $$LABEL in parallel…\n\n"; \
for tool in $$TOOLS; do \
	printf "  \033[33m⏳\033[0m  %s\n" "$$tool"; \
done; \
for tool in $$TOOLS; do \
	( $(MAKE) --no-print-directory $$tool > "$$TMPDIR/$$tool.log" 2>&1; echo $$? > "$$TMPDIR/$$tool.exit" ) & \
done; \
PENDING="$$TOOLS"; \
FAILED=0; \
spin_idx=0; \
while [ -n "$$PENDING" ]; do \
	spin_char=$${SPINNER[$$((spin_idx % 10))]}; \
	NEW_PENDING=""; \
	for tool in $$PENDING; do \
		idx=0; \
		for t in $$TOOLS; do \
			[ "$$t" = "$$tool" ] && break; \
			idx=$$((idx + 1)); \
		done; \
		lines_up=$$((N - idx)); \
		if [ -f "$$TMPDIR/$$tool.exit" ]; then \
			exit_code=$$(cat "$$TMPDIR/$$tool.exit"); \
			if [ "$$exit_code" -eq 0 ]; then \
				printf "\033[%dA\r\033[2K  \033[32m✔\033[0m  %s\033[%dB\r" "$$lines_up" "$$tool" "$$lines_up"; \
			else \
				printf "\033[%dA\r\033[2K  \033[31m✘\033[0m  %s\033[%dB\r" "$$lines_up" "$$tool" "$$lines_up"; \
				FAILED=1; \
			fi; \
		else \
			printf "\033[%dA\r\033[2K  \033[33m%s\033[0m  %s\033[%dB\r" "$$lines_up" "$$spin_char" "$$tool" "$$lines_up"; \
			NEW_PENDING="$$NEW_PENDING $$tool"; \
		fi; \
	done; \
	PENDING="$$NEW_PENDING"; \
	spin_idx=$$((spin_idx + 1)); \
	[ -n "$$PENDING" ] && sleep 0.1; \
done; \
printf "\n"; \
if [ $$FAILED -eq 0 ]; then \
	printf "\033[32mAll $$LABEL passed.\033[0m\n"; \
else \
	for tool in $$TOOLS; do \
		exit_code=$$(cat "$$TMPDIR/$$tool.exit"); \
		if [ "$$exit_code" -ne 0 ]; then \
			printf "\n\033[31m── %s ──────────────────────────────────────────────────\033[0m\n" "$$tool"; \
			cat "$$TMPDIR/$$tool.log"; \
		fi; \
	done; \
fi; \
rm -rf "$$TMPDIR"; \
[ $$FAILED -eq 0 ]
endef

## —— 🎵 🐳 The Symfony-docker Makefile 🐳 🎵 ——————————————————————————————————
.PHONY: help
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Directories and files 📁 ————————————————————————————————————————————————————————————————
.env: ## Create .env files (not phony to check the file)
	touch .env
.env.dev.local: ## Create .env.dev.local files (not phony to check the file)
	cp .env.dev .env.dev.local

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
.PHONY: build
build: ## Builds the Docker images
	$(DOCKER_COMP) build

.PHONY: rebuild
rebuild: ## Re-builds the Docker images (build with no cache)
	${DOCKER_COMP} build --no-cache --pull

.PHONY: start
start: ## Start the project
start: install up vendor cc

.PHONY: up
up: ## Up Docker container
up: up-process up-after

up-process:
	$(DOCKER_COMP) up --wait

up-after:

.PHONY: install
install: ## Install requirements
install: .env .env.dev.local build

.PHONY: stop
stop: ## Stop the project
	$(DOCKER_COMP) down --remove-orphans

.PHONY: destruct
destruct: ## Destruct the project
destruct: stop
	$(DOCKER_COMP) down --remove-orphans --volumes moco.back moco.matomo.gbl php redis web --rmi all

.PHONY: logs
logs: ## Containers logs
	@$(DOCKER_COMP) logs -f -n 0

.PHONY: sh
sh: ## Connect to the PHP container
	@$(PHP_CONT) sh

.PHONY: bash
bash: ## Alias of sh
bash: sh

.PHONY: restart-mocks
restart-mocks: ## Restart Moco mocks
	$(DOCKER_COMP) restart moco.back

## —— Data 💾 ————————————————————————————————————————————————————————————————
.PHONY: data
data: ## Initialize data
data:

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
.PHONY: composer
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

.PHONY: vendor
vendor: ## Install vendors according to the current composer.lock file
	@$(COMPOSER) install --prefer-dist --no-progress --no-interaction
	@$(COMPOSER) clear-cache

.PHONY: updates
updates: ## Updates all composer
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=./
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=tools/deptrac
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=tools/infection
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=tools/jsonlint
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=tools/php-cs-fixer
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=tools/phpmd
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=tools/phpstan
	@$(COMPOSER) update --bump-after-update --with-all-dependencies --optimize-autoloader --working-dir=tools/psalm

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
.PHONY: sf
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

.PHONY: cc
cc: ## Clear the cache
cc:
	@$(SYMFONY) cache:clear --env=dev
	@$(SYMFONY) cache:clear --env=test
	@$(SYMFONY) cache:pool:clear cache.app_version --env=dev
	@$(SYMFONY) cache:pool:clear cache.app_version --env=test

## —— CI 🚀 ———————————————————————————————————————————————————————————————————
.PHONY: all
all: ## Run all checks (infra-quality, code-quality, tests, measures, security)
all:
	$(call parallel_runner,infra-quality code-quality tests measures security,test suites)

.PHONY: a
a: ## Alias of all
a: all

## —— Tests 🧪 ———————————————————————————————————————————————————————————————
.PHONY: tests
tests: ## Execute all tests
tests:
	$(call parallel_runner,tests-unit tests-integration tests-browser,test suites)

.PHONY: t
t: ## Alias of tests
t: tests

.PHONY: tests-unit
tests-unit: ## Execute unit tests
	$(PHPUNIT) tests/src/Unit

.PHONY: tu
tu: ## Alias of tests-unit
tu: tests-unit

.PHONY: tests-integration
tests-integration: ## Execute integration tests
	$(PHPUNIT) tests/src/Integration

.PHONY: ti
ti: ## Alias of tests-integration
ti: tests-integration

.PHONY: tests-browser-chrome
tests-browser-chrome: ## Execute browser tests against Chrome
	PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub PANTHER_BROWSER_NAME=chrome $(PHPUNIT) --cache-directory=.phpunit.cache/chrome tests/src/Browser

.PHONY: tests-browser-firefox
tests-browser-firefox: ## Execute browser tests against Firefox
	PANTHER_SELENIUM_HOST=http://firefox:4444/wd/hub PANTHER_BROWSER_NAME=firefox $(PHPUNIT) --cache-directory=.phpunit.cache/firefox tests/src/Browser

.PHONY: tests-browser
tests-browser: ## Execute browser tests (Chrome + Firefox in parallel)
tests-browser:
	$(call parallel_runner,tests-browser-chrome tests-browser-firefox,browser test suites)

.PHONY: tb
tb: ## Alias of tests-browser
tb: tests-browser

## —— Infra Quality 🏗️ ———————————————————————————————————————————————————————————————
.PHONY: infra-quality
infra-quality: ## Execute all infra quality analyses
infra-quality:
	$(call parallel_runner,docker-compose-linter dockerfile-linter dotenv-linter check-moco-refs,infra-quality checks)

.PHONY: iq
iq: ## Alias of infra-quality
iq: infra-quality

.PHONY: docker-compose-linter
docker-compose-linter: ## Run Docker Compose linter
	$(DOCKERCOMPOSE_LINTER_CMD) -r .

.PHONY: docker-compose-fixer
docker-compose-fixer: ## Run Docker Compose fixer
	$(DOCKERCOMPOSE_LINTER_CMD)  -r . --fix

.PHONY: dockerfile-linter
dockerfile-linter: ## Run Dockerfile linter
	@find .docker -name 'Dockerfile' | while read -r dockerfile; do \
		$(HADOLINT_CMD) "/app/$$dockerfile"; \
	done

.PHONY: dotenv-linter
dotenv-linter: ## Run DotEnv linter
	$(DOTENV_LINTER_CMD) check . -r

.PHONY: dotenv-fixer
dotenv-fixer: ## Run DotEnv fixer
	$(DOTENV_LINTER_CMD) fix . -r --no-backup

## —— Code Quality 🔍 ———————————————————————————————————————————————————————————————
.PHONY: code-quality
code-quality: ## Execute all code quality analyses
code-quality:
	$(call parallel_runner,editorconfig-linter jsonlint validate-autoloader phpcsfixer phpmd psalm phpstan deptrac w3c,code-quality checks)

.PHONY: cq
cq: ## Alias of code-quality
cq: code-quality

.PHONY: editorconfig-linter
editorconfig-linter: ## Execute editorconfig linter
editorconfig-linter:
	$(EDITORCONFIG_LINTER_CMD) editorconfig-checker --exclude=".phar"

.PHONY: jsonlint
jsonlint: ## Execute jsonlint
jsonlint: tools/jsonlint/vendor/bin/jsonlint
	grep -RhoP '"[A-Za-z0-9]+"(?=\s*:)' tests/resources \
		| grep -vE '"[a-z0-9_]+"' \
		| sort -u \
		| tee /dev/stderr \
		| grep . && exit 1 || exit 0
	find tests/resources -type f -name "*.json" \
		-exec $(PHP) tools/jsonlint/vendor/bin/jsonlint {} \;

.PHONY: validate-autoloader
validate-autoloader: ## Execute cmheck on autoloader issues
validate-autoloader:
	@$(COMPOSER) dump-autoload -o --strict-psr --strict-ambiguous --dry-run

.PHONY: phpcsfixer
phpcsfixer: ## Execute PHP CS Fixer "Check"
phpcsfixer: tools/php-cs-fixer/vendor/bin/php-cs-fixer
	@$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer check --diff

.PHONY: phpcsfixer-fix
phpcsfixer-fix: ## Execute PHP CS Fixer "Fix"
phpcsfixer-fix: tools/php-cs-fixer/vendor/bin/php-cs-fixer
	@$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

.PHONY: phpmd
phpmd: ## Execute phpmd
phpmd: tools/phpmd/vendor/bin/phpmd
	@$(PHP) tools/phpmd/vendor/bin/phpmd src,tests text phpmd.ruleset.xml

.PHONY: psalm
psalm: ## Execute psalm
psalm: tools/psalm/vendor/bin/psalm
	@$(PHP_CONT) rm -Rf var/cache/psalm
	$(call parallel_runner,psalm-xml psalm-src-xml,psalm analyses)

.PHONY: psalm-xml
psalm-xml: tools/psalm/vendor/bin/psalm
	@$(PHP) tools/psalm/vendor/bin/psalm -c psalm.xml --no-diff --show-info=false --no-cache --find-unused-psalm-suppress --no-suggestions

.PHONY: psalm-src-xml
psalm-src-xml: tools/psalm/vendor/bin/psalm
	@$(PHP) tools/psalm/vendor/bin/psalm -c psalm-src-only.xml --no-diff --show-info=false --no-cache --find-unused-psalm-suppress --no-suggestions

.PHONY: phpstan
phpstan: ## Execute phpstan analyse
phpstan: tools/phpstan/vendor/bin/phpstan
	@$(PHP) tools/phpstan/vendor/bin/phpstan clear-result-cache
	@$(PHP) tools/phpstan/vendor/bin/phpstan analyse --memory-limit=-1

.PHONY: deptrac
deptrac: ## Execute deptrac analyse
deptrac: tools/deptrac/vendor/bin/deptrac
	@$(PHP) tools/deptrac/vendor/bin/deptrac analyse --report-uncovered --fail-on-uncovered --cache-file=/app/var/cache/deptrac/.deptrac.cache

.PHONY: w3c
w3c: ## Execute w3c
w3c:
	tools/w3c-validate/w3c_validate.sh

## —— Measures 📏 ———————————————————————————————————————————————————————————————
.PHONY: measures
measures: ## Execute all measures tools
measures: clear-build
	$(call sequential_runner,coverage-generate coverage-check infection,measures)

.PHONY: m
m: ## Alias of measures
m: measures

.PHONY: clear-build
clear-build: ## Clear build directory
	@rm -Rf build/coverage*

.PHONY: coverage-generate
coverage-generate: ## Generate PHPUnit coverage data (Xdebug)
coverage-generate: build/coverage/coverage-xml

build/coverage/coverage-xml: ## Generate coverage report
	$(DOCKER_COMP) exec \
		-e XDEBUG_MODE=coverage -T php \
		php vendor/bin/phpunit \
			--exclude-testsuite="Browser Test Suite" \
			--coverage-clover=build/coverage/coverage.xml \
			--coverage-xml=build/coverage/coverage-xml \
			--log-junit=build/coverage/junit.xml

.PHONY: coverage-check
coverage-check: ## Check PHPUnit coverage score (requires build/coverage/coverage-xml)
coverage-check: build/coverage/coverage-xml
	@$(PHP_CONT) php tools/coverage/coverage.php build/coverage/coverage.xml 100 true \
	|| (echo "❌ Coverage check failed, generating HTML report..." && $(MAKE) coverage-html && exit 1)

.PHONY: coverage
coverage: ## Execute PHPUnit Coverage to check the score
coverage: clear-build build/coverage/coverage-xml coverage-check

.PHONY: coverage-html
coverage-html: ## Execute PHPUnit Coverage in HTML
	$(DOCKER_COMP) exec \
		-e XDEBUG_MODE=coverage -T php \
		php vendor/bin/phpunit \
			--exclude-testsuite="Browser Test Suite" \
			--coverage-html=build/coverage/coverage-html

.PHONY: clear-infection-cache
clear-infection-cache:
	@$(PHP_CONT) rm -Rf var/cache/infection

.PHONY: infection
infection: ## Execute all Infection testing
infection: build/coverage/coverage-xml tools/infection/vendor/bin/infection clear-infection-cache
	@$(PHP) tools/infection/vendor/bin/infection --threads=4 --no-progress \
		--skip-initial-tests --coverage=build/coverage \
		--min-msi=100 --min-covered-msi=100 \
		--filter=src

## —— Security 🛡️ ———————————————————————————————————————————————————————————————
.PHONY: security
security: ## Execute all security commands
security:
	$(call parallel_runner,composer-audit composer-audit-tools security-check,security checks)

.PHONY: s
s: ## Alias of security
s: security

.PHONY: composer-audit
composer-audit: ## Execute Composer Audit
composer-audit: c=audit
composer-audit: composer

.PHONY: composer-audit-tools
composer-audit-tools: ## Execute Composer Audit on quality tools
composer-audit-tools:
	@for tool in tools/deptrac tools/infection tools/jsonlint tools/php-cs-fixer tools/phpmd tools/phpstan tools/psalm; do \
		echo "Auditing $$tool..."; \
		$(COMPOSER) audit --working-dir=$$tool; \
	done

.PHONY: security-check
security-check: ## Execute Symfony Security Checker
security-check:
	@$(PHP_CONT) symfony security:check

.PHONY: check-moco-refs
check-moco-refs: ## Check moco file references integrity (no Docker needed)
check-moco-refs:
	@tools/check-moco-refs/check_moco_refs.sh tests/resources/moco/Back/moco.json tests/resources/moco/Back
	@tools/check-moco-refs/check_moco_refs.sh tests/resources/moco/Matomo/moco.json tests/resources/moco/Matomo

## —— Cleaning 🧽 ———————————————————————————————————————————————————————————————
.PHONY: clean-unused-files
clean-unused-files: ## Clean unused mocks files
clean-unused-files:
	tools/clean-unused-files/clean_unused_files.sh tests/resources/moco/Back/responses

.PHONY: clean-moco-routes
clean-moco-routes: ## Clean unused moco routes
clean-moco-routes:
	tools/clean-moco-routes/clean_moco_routes.sh tests/resources/moco/Back/moco.json

.PHONY: clear-caches
clear-caches: ## Clean PHP caches
clear-caches: tools/cachetool/cachetool.phar
	@$(PHP) tools/cachetool/cachetool.phar apcu:cache:clear --cli
	@$(PHP) tools/cachetool/cachetool.phar apcu:cache:clear --fcgi
	@$(PHP) tools/cachetool/cachetool.phar opcache:reset
	@$(PHP) tools/cachetool/cachetool.phar stat:clear

## —— Tools 🔧 ———————————————————————————————————————————————————————————————
tools/php-cs-fixer/vendor/bin/php-cs-fixer: ## Install php-cs-fixer
	@$(COMPOSER) install --working-dir=tools/php-cs-fixer --optimize-autoloader --no-dev

tools/phpmd/vendor/bin/phpmd: ## Install phpmd
	@$(COMPOSER) install --working-dir=tools/phpmd --optimize-autoloader --no-dev

tools/psalm/vendor/bin/psalm: ## Install psalm
	@$(COMPOSER) install --working-dir=tools/psalm --optimize-autoloader --no-dev

tools/phpstan/vendor/bin/phpstan: ## Install phpstan
	@$(COMPOSER) install --working-dir=tools/phpstan --optimize-autoloader --no-dev

tools/deptrac/vendor/bin/deptrac: ## Install deptrac
	@$(COMPOSER) install --working-dir=tools/deptrac --optimize-autoloader --no-dev

tools/infection/vendor/bin/infection: ## Install infection
	@$(COMPOSER) install --working-dir=tools/infection --optimize-autoloader --no-dev

tools/jsonlint/vendor/bin/jsonlint: ## Install jsonlint
	@$(COMPOSER) install --working-dir=tools/jsonlint --optimize-autoloader --no-dev

tools/cachetool/cachetool.phar: ## Install cachetool
	mkdir -p tools/cachetool
	curl -sLO https://github.com/gordalina/cachetool/releases/download/9.2.1/cachetool.phar --output-dir tools/cachetool
	chmod +x tools/cachetool/cachetool.phar

## —— Image 🐳 ———————————————————————————————————————————————————————————————
img-build: ## Build Docker image
	docker build --target php_prod -f ./.docker/php/Dockerfile -t ghcr.io/douzeensemble/pokenini-web:latest .
img-push: ## Push Docker image
	docker push ghcr.io/douzeensemble/pokenini-web:latest
