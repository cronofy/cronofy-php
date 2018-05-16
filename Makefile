CURRENT_VERSION:=$(shell jq ".version" -r composer.json)

all: test

install_dependencies:
	php composer.phar install

update:
	php composer.phar update

test: install_dependencies
	vendor/bin/phpunit
	vendor/bin/phpcs tests/ cronofy.php --standard=ruleset.xml

check_dependencies:
	@command -v jq >/dev/null || (echo "jq not installed please install via homebrew - 'brew install jq'"; exit 1)

release: check_dependencies test
	git push
	git tag $(CURRENT_VERSION)
	git push --tags
