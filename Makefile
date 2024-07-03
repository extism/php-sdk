.PHONY: test

prepare:
	composer install

test: prepare
	php vendor/bin/phpunit ./tests

cscheck:
	vendor/bin/phpcs .

csfix:
	vendor/bin/php-cs-fixer fix .