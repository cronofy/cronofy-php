CURRENT_VERSION:=$(shell jq ".version" -r composer.json)

all: test

install_dependencies:
	php composer.phar install

update:
	php composer.phar update

test: install_dependencies
	vendor/bin/phpunit

release: test
	git push
	git tag $(CURRENT_VERSION)
	git push --tags
