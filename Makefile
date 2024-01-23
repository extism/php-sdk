.PHONY: test

prepare:
	composer install

test: prepare
	php vendor/bin/phpunit ./tests --display-deprecations --display-warnings