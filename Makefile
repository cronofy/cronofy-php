all: test

install_dependencies:
	php composer.phar install

update:
	php composer.phar update

test: install_dependencies
	vendor/bin/phpunit

release:
	git push
	git tag $(cat composer.json | jq .version -r)
	git push --tags
