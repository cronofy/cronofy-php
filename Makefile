CURRENT_VERSION:=$(shell jq ".version" -r composer.json)

.PHONY: all
all: test

.PHONY: install_composer
install_composer:
	curl -sS https://getcomposer.org/installer | php

.PHONY: install_dependencies
install_dependencies: install_composer
	php composer.phar install

.PHONY: update
update:
	php composer.phar update

.PHONY: test
test: install_dependencies
	vendor/bin/phpunit tests/
	vendor/bin/phpcs tests/ src/Cronofy.php --standard=ruleset.xml

.PHONY: smoke-test
smoke-test:
	source .env && php ./dev-smoke-test.php

.PHONY: ci
ci: test

.PHONY: check_dependencies
check_dependencies:
	@command -v jq >/dev/null || (echo "jq not installed please install via homebrew - 'brew install jq'"; exit 1)

.PHONY: release
release: check_dependencies test
	git push
	git tag $(CURRENT_VERSION)
	git push --tags

.PHONY: init
init:
	./init
