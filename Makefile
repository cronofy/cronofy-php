all: test

install_dependencies:
	php composer.phar install

update:
	php composer.phar update

test: install_dependencies
	vendor/bin/phpunit

release:
	@echo 'To release simply tag and push the required version'
